<?php

namespace Tests\Feature;

use App\Enums\PostType;
use App\Livewire\Classrooms\Feed;
use App\Livewire\QuestionGames\Editor;
use App\Livewire\QuestionGames\Library;
use App\Livewire\Quizzes\LiveRoom;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\LiveQuiz;
use App\Models\QuestionGame;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestionGameTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_a_themed_game_and_students_play_from_one_mural_invitation(): void
    {
        $classroom = Classroom::factory()->create();
        $host = $classroom->teacher;
        $student = Enrollment::factory()->for($classroom)->create()->user;
        $otherClassroom = Classroom::factory()->for($host, 'teacher')->create();

        Livewire::actingAs($host)->test(Editor::class, ['classroom' => $classroom])
            ->set('title', 'Missão das frações')
            ->set('topic', 'Matemática')
            ->set('intro', 'Compare as partes de um todo.')
            ->set('palette', 'custom')->set('pattern', 'waves')
            ->set('base', '#224466')->set('accent', '#FCB852')
            ->set('seconds', '20')
            ->set('questions.0.prompt', 'Qual fração representa a metade?')
            ->set('questions.0.options', ['1/2', '1/3'])
            ->set('questions.0.explanation', 'Uma de duas partes iguais.')
            ->set('questions.0.correct', 3)->call('save')->assertHasErrors(['questions.0.correct'])
            ->set('questions.0.correct', 0)->call('save')->assertHasNoErrors();

        $game = QuestionGame::query()->sole();
        $this->assertSame('#224466', $game->theme['base']);
        $this->get(route('question-games.edit', ['classroom' => $classroom, 'questionGame' => $game]))
            ->assertOk()->assertSee('Editar jogo');
        $this->get(route('question-games.edit', ['classroom' => $otherClassroom, 'questionGame' => $game]))->assertNotFound();

        Livewire::actingAs($student)->test(Library::class, ['classroom' => $classroom])->assertDontSee($game->title)
            ->call('play', $game->id, true)->assertForbidden();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            Livewire::actingAs($host)->test(Library::class, ['classroom' => $classroom])
                ->call('play', $game->id, true)->assertHasNoErrors();
        }

        $room = LiveQuiz::query()->sole();
        $this->assertSame($game->id, $room->question_game_id);
        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseHas('posts', ['live_quiz_id' => $room->id, 'type' => PostType::LiveInvitation->value]);

        $game->update(['title' => 'Outro nome para a próxima rodada']);
        Livewire::actingAs($student)->test(Feed::class, ['classroom' => $classroom])
            ->assertSee('Missão das frações')->assertSee('Entrar na partida');
        $this->actingAs(User::factory()->create())->get(route('quizzes.live', $room))->assertNotFound();

        Livewire::actingAs($student)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('join')->assertSee('Você entrou.')->assertDontSee('Uma de duas partes iguais.');

        $this->freezeSecond();
        Livewire::actingAs($host)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->assertSee('Tela cheia')->assertSee('Ligar som')->assertSeeHtml('data-classroom-pattern="waves"')
            ->call('publishToMural')->assertSee('Ver publicação no mural')
            ->call('advance', 0, LiveQuiz::LOBBY)->assertSee('Prepare-se!')->assertDontSee('Qual fração representa a metade?')
            ->call('advance', 0, LiveQuiz::COUNTDOWN)->assertSee('Prepare-se!');

        Livewire::actingAs($student)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('answer', 0, 0)->assertHasErrors(['room'])->assertSee('Aguarde o fim da contagem para responder.');
        $this->assertDatabaseCount('live_quiz_answers', 0);

        $this->travel(LiveQuiz::COUNTDOWN_SECONDS)->seconds();
        $this->assertEquals(now()->addSeconds(20), $room->refresh()->question_ends_at);

        Livewire::actingAs($student)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->assertSee('Qual fração representa a metade?')->assertDontSee('Resposta correta')
            ->call('answer', 0, 0)->assertSee('Resposta registrada.');

        Livewire::actingAs($host)->test(LiveRoom::class, ['liveQuiz' => $room])
            ->call('advance', 0, LiveQuiz::QUESTION)->assertSee('Uma de duas partes iguais.')
            ->call('advance', 0, LiveQuiz::REVEAL)->assertSee('Pódio da partida');

        Livewire::actingAs($student)->test(LiveRoom::class, ['liveQuiz' => $room])->assertSee('100 pontos');
        $this->assertDatabaseCount('posts', 1);
        $this->assertDatabaseCount('quiz_attempts', 0);
    }
}
