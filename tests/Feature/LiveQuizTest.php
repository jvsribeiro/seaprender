<?php

namespace Tests\Feature;

use App\Livewire\Classrooms\Progress;
use App\Livewire\Classrooms\Tools;
use App\Livewire\Quizzes\LiveRoom;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\LiveQuiz;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LiveQuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_and_two_students_can_play_a_timed_quiz_until_the_results(): void
    {
        $classroom = Classroom::factory()->create();
        [$player, $secondPlayer] = Enrollment::factory()->for($classroom)->count(2)->create()->pluck('user')->all();
        $quiz = Quiz::factory()->for($classroom)->create();
        QuizQuestion::factory()->for($quiz)->create(['explanation' => 'O gabarito só aparece depois do prazo.']);
        QuizQuestion::factory()->for($quiz)->create(['position' => 2, 'prompt' => 'Qual tag cria um parágrafo?', 'correct_option' => 1]);

        $teacher = $classroom->teacher;
        Livewire::actingAs($teacher)->test(Tools::class, ['classroom' => $classroom])
            ->set('quizId', (string) $quiz->id)->set('seconds', '10')->call('openQuiz')->assertHasNoErrors();

        $room = LiveQuiz::query()->sole();
        Livewire::test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('advance', 0, LiveQuiz::LOBBY)->assertHasErrors(['room']);
        $this->actingAs(User::factory()->create())->get(route('quizzes.live', $room))->assertNotFound();

        foreach ([$player, $secondPlayer] as $learner) {
            Livewire::actingAs($learner)->test(LiveRoom::class, ['liveQuiz' => $room])
                ->call('join')->assertSee('Você entrou.');
        }
        Livewire::test(LiveRoom::class, ['liveQuiz' => $room])->call('advance', 0, LiveQuiz::LOBBY)->assertForbidden();

        $this->freezeSecond();
        Livewire::actingAs($teacher)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('advance', 0, LiveQuiz::LOBBY)->assertSee('Qual tag cria um link?');

        Livewire::actingAs($player)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->assertDontSee('O gabarito só aparece depois do prazo.')
            ->assertDontSee('Resposta correta')
            ->call('answer', 0, 0)->assertHasNoErrors()
            ->call('answer', 0, 1)->assertHasNoErrors();

        $this->travel(11)->seconds();
        Livewire::actingAs($secondPlayer)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->assertSee('O gabarito só aparece depois do prazo.')
            ->call('answer', 0, 0)->assertHasErrors(['room']);

        $this->assertDatabaseCount('live_quiz_answers', 1);
        $this->assertDatabaseHas('live_quiz_answers', ['user_id' => $player->id, 'choice' => 0, 'score' => 100]);

        Livewire::actingAs($teacher)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('advance', 0, LiveQuiz::REVEAL)->assertSee('Qual tag cria um parágrafo?');
        Livewire::actingAs($secondPlayer)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('answer', 1, 1)->assertHasNoErrors();
        Livewire::actingAs($teacher)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('advance', 1, LiveQuiz::QUESTION)->assertSee('Resposta correta')
            ->call('advance', 1, LiveQuiz::REVEAL)->assertSee('Fim de jogo')
            ->assertSee($player->name)->assertSee($secondPlayer->name)->assertSee('100 pts');

        Livewire::actingAs($player)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->assertSee('100 pontos')->assertDontSee($secondPlayer->name);
        Livewire::test(Progress::class, ['classroom' => $classroom])
            ->assertSee('Partidas ao vivo recentes')->assertSee('100 pontos na partida');

        $this->assertDatabaseHas('audit_logs', ['classroom_id' => $classroom->id, 'action' => 'live_quiz.finished']);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }
}
