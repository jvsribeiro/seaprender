<?php

namespace App\Actions\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use stdClass;

class ManageRewards
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function award(User $actor, Classroom $classroom, int $studentId, int $amount, string $reason, string $operationId): void
    {
        Gate::forUser($actor)->authorize('manage', $classroom);

        DB::transaction(function () use ($actor, $classroom, $studentId, $amount, $reason, $operationId) {
            $classroom = $this->lockClassroom($classroom);
            $student = $this->activeStudent($classroom, $studentId);

            if (DB::table('classroom_point_entries')->where('operation_id', $operationId)->exists()) {
                return;
            }

            $this->entry($classroom, $studentId, $actor, $amount, $reason, operationId: $operationId);
            $this->recordAuditLog->handle($actor, 'reward.points_awarded', $classroom, [
                'student' => $student->name,
                'points' => $amount,
                'reason' => $reason,
            ]);
        });
    }

    /**
     * @param  array{title: string, description: ?string, cost: int, stock: int}  $details
     */
    public function create(User $actor, Classroom $classroom, array $details): void
    {
        Gate::forUser($actor)->authorize('manage', $classroom);

        DB::transaction(function () use ($actor, $classroom, $details) {
            $classroom = $this->lockClassroom($classroom);
            DB::table('classroom_rewards')->insert([
                ...$details,
                'classroom_id' => $classroom->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->recordAuditLog->handle($actor, 'reward.created', $classroom, ['title' => $details['title'], 'points' => $details['cost']]);
        });
    }

    public function setEnabled(User $actor, Classroom $classroom, int $rewardId, bool $enabled): void
    {
        Gate::forUser($actor)->authorize('manage', $classroom);

        DB::transaction(function () use ($actor, $classroom, $rewardId, $enabled) {
            $classroom = $this->lockClassroom($classroom);
            $reward = $this->reward($classroom, $rewardId);

            if ((bool) $reward->enabled !== $enabled) {
                DB::table('classroom_rewards')->where('id', $rewardId)->update(['enabled' => $enabled, 'updated_at' => now()]);
                $this->recordAuditLog->handle($actor, 'reward.updated', $classroom, [
                    'title' => $reward->title,
                    'reason' => $enabled ? 'Disponível na lojinha' : 'Brinde pausado',
                ]);
            }
        });
    }

    public function redeem(User $student, Classroom $classroom, int $rewardId, string $operationId): void
    {
        Gate::forUser($student)->authorize('view', $classroom);
        abort_unless($student->isStudent(), 403);

        DB::transaction(function () use ($student, $classroom, $rewardId, $operationId) {
            $classroom = $this->lockClassroom($classroom);
            $this->activeStudent($classroom, $student->id);

            $entries = $this->entries($classroom, $student->id);
            if ((clone $entries)->where('operation_id', $operationId)->exists()) {
                return;
            }

            $reward = $this->reward($classroom, $rewardId);
            if (! $reward->enabled || $reward->stock < 1) {
                throw ValidationException::withMessages(['shop' => 'Esse brinde está indisponível.']);
            }
            if ($entries->sum('amount') < $reward->cost) {
                throw ValidationException::withMessages(['shop' => 'Seu saldo de pontos é insuficiente para este brinde.']);
            }

            $redemptionId = DB::table('reward_redemptions')->insertGetId([
                'classroom_reward_id' => $reward->id,
                'user_id' => $student->id,
                'cost' => $reward->cost,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('classroom_rewards')->where('id', $reward->id)->decrement('stock', 1, ['updated_at' => now()]);
            $this->entry($classroom, $student->id, $student, -$reward->cost, 'Resgate de '.$reward->title, $redemptionId, $operationId);
            $this->recordAuditLog->handle($student, 'reward.redeemed', $classroom, ['title' => $reward->title, 'points' => $reward->cost]);
        });
    }

    public function settle(User $actor, Classroom $classroom, int $redemptionId, bool $delivered): void
    {
        Gate::forUser($actor)->authorize('manage', $classroom);

        DB::transaction(function () use ($actor, $classroom, $redemptionId, $delivered) {
            $classroom = $this->lockClassroom($classroom, activeOnly: false);

            $redemption = DB::table('reward_redemptions as redemptions')
                ->join('classroom_rewards as rewards', 'rewards.id', '=', 'redemptions.classroom_reward_id')
                ->leftJoin('users', 'users.id', '=', 'redemptions.user_id')
                ->where('rewards.classroom_id', $classroom->id)->where('redemptions.id', $redemptionId)
                ->first(['redemptions.*', 'rewards.title', 'users.name']);

            abort_if($redemption === null, 404);
            if ($redemption->status !== 'pending') {
                return;
            }

            DB::table('reward_redemptions')->where('id', $redemptionId)->update([
                'status' => $delivered ? 'delivered' : 'cancelled',
                'updated_at' => now(),
            ]);

            if (! $delivered) {
                DB::table('classroom_rewards')->where('id', $redemption->classroom_reward_id)->increment('stock', 1, ['updated_at' => now()]);
                if ($redemption->user_id !== null) {
                    $this->entry($classroom, $redemption->user_id, $actor, $redemption->cost, 'Estorno de '.$redemption->title, $redemptionId);
                }
            }

            $this->recordAuditLog->handle($actor, $delivered ? 'reward.delivered' : 'reward.cancelled', $classroom, [
                'title' => $redemption->title,
                'student' => $redemption->name,
                'points' => $redemption->cost,
            ]);
        });
    }

    public function entries(Classroom $classroom, int $userId): Builder
    {
        return DB::table('classroom_point_entries')->where('classroom_id', $classroom->id)->where('user_id', $userId);
    }

    private function lockClassroom(Classroom $classroom, bool $activeOnly = true): Classroom
    {
        $locked = Classroom::query()->lockForUpdate()->findOrFail($classroom->id);
        if ($activeOnly && ! $locked->isActive()) {
            throw ValidationException::withMessages(['shop' => 'Reative a turma para usar a lojinha.']);
        }

        return $locked;
    }

    private function activeStudent(Classroom $classroom, int $studentId): User
    {
        $enrollment = $classroom->activeEnrollments()->where('user_id', $studentId)->lockForUpdate()->first();
        $student = $enrollment?->user;
        if ($student?->role !== UserRole::Student) {
            throw ValidationException::withMessages(['studentId' => 'Escolha um aluno matriculado nesta turma.']);
        }

        return $student;
    }

    private function reward(Classroom $classroom, int $rewardId): stdClass
    {
        $reward = DB::table('classroom_rewards')->where('classroom_id', $classroom->id)->where('id', $rewardId)->first();
        abort_if($reward === null, 404);

        return $reward;
    }

    private function entry(Classroom $classroom, int $userId, User $actor, int $amount, string $reason, ?int $redemptionId = null, ?string $operationId = null): void
    {
        DB::table('classroom_point_entries')->insert([
            'classroom_id' => $classroom->id,
            'user_id' => $userId,
            'actor_id' => $actor->id,
            'reward_redemption_id' => $redemptionId,
            'operation_id' => $operationId,
            'amount' => $amount,
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
