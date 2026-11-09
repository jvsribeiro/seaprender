<?php

namespace Tests\Feature;

use App\Enums\EnrollmentStatus;
use App\Enums\PostType;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\QuizAttempt;
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

    public function test_sidebar_progress_counts_only_activities_in_the_students_active_classes(): void
    {
        $student = User::factory()->create();
        $first = Enrollment::factory()->for($student)->create()->classroom;
        $second = Enrollment::factory()->for($student)->create()->classroom;
        $pending = Enrollment::factory()->for($student)->create(['status' => EnrollmentStatus::Pending])->classroom;
        $archived = Classroom::factory()->archived()->create();
        Enrollment::factory()->for($student)->for($archived)->create();

        $quiz = Quiz::factory()->for($first)->published()->create();
        QuizAttempt::create(['user_id' => $student->id, 'quiz_id' => $quiz->id, 'score' => 0, 'total' => 1, 'answers' => []]);
        Post::factory()->for($second)->create(['type' => PostType::Reflection]);
        Quiz::factory()->for($pending)->published()->create();
        Quiz::factory()->for($archived)->published()->create();
        Quiz::factory()->for($second)->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()->assertSeeHtml('value="1" max="2" aria-label="Progresso geral de quizzes e reflexões"');
        $this->actingAs($first->teacher)->get(route('dashboard'))->assertDontSee('Progresso geral de quizzes e reflexões');
    }

    public function test_admin_sees_the_administration_menu(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('dashboard'))
            ->assertSee('Administração')
            ->assertSee('Auditoria');
    }
}
