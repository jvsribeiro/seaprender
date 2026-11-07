<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_student_is_pointed_to_the_catalog(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Procurar turmas')
            ->assertDontSee('Nova turma')
            ->assertDontSee('Usuários');
    }

    public function test_first_steps_follow_real_progress_and_then_disappear(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get(route('dashboard'))->assertSee('1 de 2 concluídos');

        Enrollment::factory()->for($student)->create();

        $this->get(route('dashboard'))->assertDontSee('Primeiros passos');
    }

    public function test_student_sees_only_their_classrooms(): void
    {
        $enrollment = Enrollment::factory()->create();
        $other = Classroom::factory()->create(['name' => 'Outra turma']);

        $this->actingAs($enrollment->user)
            ->get(route('dashboard'))
            ->assertSee($enrollment->classroom->name)
            ->assertDontSee($other->name);
    }

    public function test_teacher_sees_their_numbers(): void
    {
        $classroom = Classroom::factory()->create();
        Enrollment::factory()->count(2)->for($classroom)->create();
        Enrollment::factory()->create();

        $this->actingAs($classroom->teacher)
            ->get(route('dashboard'))
            ->assertSee('Nova turma')
            ->assertSeeInOrder(['Turmas ativas', '2', 'Alunos matriculados'], false);
    }

    public function test_admin_sees_the_administration_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee('Administração')
            ->assertSee('Auditoria');
    }
}
