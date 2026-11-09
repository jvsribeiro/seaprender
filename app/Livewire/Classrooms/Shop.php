<?php

namespace App\Livewire\Classrooms;

use App\Actions\Classrooms\ManageRewards;
use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Shop extends Component
{
    use WithPagination;

    #[Locked]
    public Classroom $classroom;

    #[Locked]
    public string $operationId;

    public string $studentId = '';

    public string $points = '';

    public string $reason = '';

    public string $rewardTitle = '';

    public string $description = '';

    public string $cost = '';

    public string $stock = '';

    public function mount(Classroom $classroom): void
    {
        $this->classroom = $classroom;
        $this->operationId = (string) Str::uuid();
    }

    public function award(ManageRewards $rewards): void
    {
        Gate::authorize('manage', $this->classroom);

        $data = $this->validate([
            'studentId' => ['required', 'integer', 'min:1'],
            'points' => ['required', 'integer', 'between:1,1000'],
            'reason' => ['required', 'string', 'max:160'],
        ], [], ['studentId' => 'aluno', 'points' => 'pontos', 'reason' => 'motivo']);

        $rewards->award($this->user(), $this->classroom, (int) $data['studentId'], (int) $data['points'], $data['reason'], $this->operationId);

        $this->operationId = (string) Str::uuid();
        $this->reset('studentId', 'points', 'reason');
        $this->dispatch('toast', message: 'Pontos concedidos.');
    }

    public function createReward(ManageRewards $rewards): void
    {
        Gate::authorize('manage', $this->classroom);

        $data = $this->validate([
            'rewardTitle' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'cost' => ['required', 'integer', 'between:1,10000'],
            'stock' => ['required', 'integer', 'between:1,1000'],
        ], [], ['rewardTitle' => 'nome do brinde', 'description' => 'descrição', 'cost' => 'custo', 'stock' => 'estoque']);

        $rewards->create($this->user(), $this->classroom, [
            'title' => $data['rewardTitle'],
            'description' => $data['description'] ?: null,
            'cost' => (int) $data['cost'],
            'stock' => (int) $data['stock'],
        ]);

        $this->reset('rewardTitle', 'description', 'cost', 'stock');
        $this->dispatch('toast', message: 'Brinde disponível na lojinha.');
    }

    public function setRewardEnabled(int $rewardId, bool $enabled, ManageRewards $rewards): void
    {
        $rewards->setEnabled($this->user(), $this->classroom, $rewardId, $enabled);
    }

    public function redeem(int $rewardId, ManageRewards $rewards): void
    {
        $rewards->redeem($this->user(), $this->classroom, $rewardId, $this->operationId);

        $this->operationId = (string) Str::uuid();
        $this->dispatch('toast', message: 'Resgate solicitado.');
    }

    public function settle(int $redemptionId, bool $delivered, ManageRewards $rewards): void
    {
        $rewards->settle($this->user(), $this->classroom, $redemptionId, $delivered);
    }

    public function render(ManageRewards $rewards): View
    {
        Gate::authorize('view', $this->classroom);

        $canManage = Gate::allows('manage', $this->classroom);
        $entries = $rewards->entries($this->classroom, $this->user()->id);
        $classroomId = $this->classroom->id;

        return view('livewire.classrooms.shop', [
            'canManage' => $canManage,
            'canRedeem' => $this->user()->isStudent() && $this->classroom->isActive(),
            'balance' => $canManage ? null : $entries->sum('amount'),
            'entries' => $canManage ? collect() : $entries->latest('id')->limit(15)->get(),
            'students' => $canManage && $this->classroom->isActive() ? User::query()
                ->where('role', UserRole::Student)
                ->whereHas('enrollments', fn ($query) => $query->where('classroom_id', $classroomId)->where('status', EnrollmentStatus::Active))
                ->orderBy('name')->orderBy('id')->get(['id', 'name']) : collect(),
            'catalog' => DB::table('classroom_rewards')->where('classroom_id', $classroomId)
                ->when(! $canManage, fn ($query) => $query->where('enabled', true))
                ->latest('id')->paginate(12, pageName: 'brindes'),
            'redemptions' => DB::table('reward_redemptions as redemptions')
                ->join('classroom_rewards as rewards', 'rewards.id', '=', 'redemptions.classroom_reward_id')
                ->leftJoin('users', 'users.id', '=', 'redemptions.user_id')
                ->where('rewards.classroom_id', $classroomId)
                ->when(! $canManage, fn ($query) => $query->where('redemptions.user_id', $this->user()->id))
                ->orderByRaw("case when redemptions.status = 'pending' then 0 else 1 end")
                ->orderByDesc('redemptions.id')
                ->paginate(15, ['redemptions.*', 'rewards.title', 'users.name'], 'resgates'),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
