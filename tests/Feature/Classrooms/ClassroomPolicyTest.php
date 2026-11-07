<?php

namespace Tests\Feature\Classrooms;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ClassroomPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, bool, bool}>
     */
    public static function actors(): array
    {
        return [
            'professor da turma' => ['owner', true, true],
            'outro professor' => ['other teacher', false, false],
            'aluno matriculado' => ['student', true, false],
            'aluno removido' => ['removed student', false, false],
            'aluno de fora' => ['outsider', false, false],
            'administrador' => ['admin', true, true],
        ];
    }

    #[DataProvider('actors')]
    public function test_classroom_abilities_by_actor(string $actor, bool $canView, bool $canManage): void
    {
        $classroom = Classroom::factory()->create();

        $user = match ($actor) {
            'owner' => $classroom->teacher,
            'other teacher' => User::factory()->teacher()->create(),
            'student' => Enrollment::factory()->for($classroom)->create()->user,
            'removed student' => Enrollment::factory()->removed()->for($classroom)->create()->user,
            'outsider' => User::factory()->create(),
            'admin' => User::factory()->admin()->create(),
        };

        $this->assertSame($canView, Gate::forUser($user)->allows('view', $classroom));
        $this->assertSame($canManage, Gate::forUser($user)->allows('manage', $classroom));
    }

    public function test_only_teachers_create_and_only_students_enroll(): void
    {
        $teacher = User::factory()->teacher()->create();
        $student = User::factory()->create();

        $this->assertTrue(Gate::forUser($teacher)->allows('create', Classroom::class));
        $this->assertFalse(Gate::forUser($student)->allows('create', Classroom::class));
        $this->assertTrue(Gate::forUser($student)->allows('enroll', Classroom::class));
        $this->assertFalse(Gate::forUser($teacher)->allows('enroll', Classroom::class));
    }
}
