<?php

namespace Tests\Feature\Classrooms;

use App\Enums\PostType;
use App\Livewire\Classrooms\Tools;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ToolPublicationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_publishes_draws_and_groups_once_with_short_names(): void
    {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->create(['name' => 'Ana Lima']);
        Enrollment::factory()->for($classroom)->for($student)->create();

        $tools = Livewire::actingAs($classroom->teacher)->test(Tools::class, ['classroom' => $classroom]);
        $tools->call('drawStudent')->assertSee('Ana Lima')
            ->call('publishDraw')->assertSee('Ver sorteio no mural')
            ->call('publishDraw')->assertHasNoErrors();

        $otherStudent = User::factory()->create(['name' => 'Caio Souza']);
        Enrollment::factory()->for($classroom)->for($otherStudent)->create();
        $tools->call('drawGroups')->assertSee('Publicar grupos')
            ->call('publishGroups')->assertSee('Ver grupos no mural');

        $this->assertDatabaseCount('posts', 2);
        $this->assertDatabaseHas('posts', ['type' => PostType::Draw->value, 'body' => 'Ana L. foi sorteado(a).']);

        $this->actingAs($student)->get(route('classrooms.show', $classroom))
            ->assertOk()->assertHeaderContains('Cache-Control', 'no-store')->assertSee('Grupos da aula')->assertSee('Caio S.')
            ->assertDontSee('Caio Souza')->assertDontSee($otherStudent->email);
        $this->actingAs(User::factory()->create())->get(route('classrooms.show', $classroom))->assertNotFound();
    }

    public function test_roster_and_progress_show_completion_below_the_student_name(): void
    {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->create(['name' => 'Ana Lima']);
        Enrollment::factory()->for($classroom)->for($student)->create();
        $quiz = Quiz::factory()->for($classroom)->published()->create();
        QuizAttempt::create(['user_id' => $student->id, 'quiz_id' => $quiz->id, 'score' => 0, 'total' => 1, 'answers' => []]);
        Post::factory()->for($classroom)->create(['type' => PostType::Reflection]);
        Quiz::factory()->for($classroom)->create();

        foreach (['alunos', 'progresso'] as $tab) {
            $this->actingAs($classroom->teacher)->get(route('classrooms.show', ['classroom' => $classroom, 'aba' => $tab]))
                ->assertOk()
                ->assertSeeHtml('value="1" max="2" aria-label="Progresso de Ana Lima"')
                ->assertSee('1 de 2 participações concluídas');
        }

        $this->actingAs($student)->get(route('classrooms.show', ['classroom' => $classroom, 'aba' => 'progresso']))
            ->assertOk()->assertDontSeeHtml('aria-label="Progresso de Ana Lima"');
    }
}
