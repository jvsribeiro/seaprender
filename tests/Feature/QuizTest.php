<?php

namespace Tests\Feature;

use App\Contracts\QuizQuestionGenerator;
use App\Enums\PostType;
use App\Enums\QuizStatus;
use App\Livewire\Quizzes\Editor;
use App\Livewire\Quizzes\Index;
use App\Livewire\Quizzes\Play;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_and_publishes_a_quiz_on_the_feed(): void
    {
        $classroom = Classroom::factory()->create();

        $this->fillEditor($classroom)->call('saveAndPublish')->assertHasNoErrors();

        $quiz = Quiz::sole();

        $this->assertSame(QuizStatus::Published, $quiz->status);
        $this->assertSame(1, $quiz->questions()->count());
        $this->assertSame(PostType::Quiz, Post::sole()->type);
        $this->assertSame($quiz->id, Post::sole()->quiz_id);
    }

    public function test_draft_stays_out_of_the_feed_and_hidden_from_students(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;

        $this->fillEditor($classroom)->call('saveDraft')->assertHasNoErrors();

        $quiz = Quiz::sole();

        $this->assertDatabaseCount('posts', 0);
        $this->actingAs($student)->get(route('quizzes.show', $quiz))->assertNotFound();

        Livewire::actingAs($student)->test(Index::class, ['classroom' => $classroom])
            ->assertDontSee($quiz->title);
    }

    public function test_question_needs_a_prompt_and_filled_options(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Editor::class, ['classroom' => $classroom])
            ->set('title', 'Quiz')
            ->call('saveDraft')
            ->assertHasErrors(['questions.0.prompt', 'questions.0.options.0'])
            ->assertSee('Preencha o campo pergunta.');

        $this->assertDatabaseCount('quizzes', 0);
    }

    public function test_correct_option_must_exist(): void
    {
        $classroom = Classroom::factory()->create();

        $this->fillEditor($classroom)
            ->set('questions.0.correct', 4)
            ->call('saveDraft')
            ->assertHasErrors(['questions.0.correct']);
    }

    public function test_student_cannot_open_the_editor(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($enrollment->user)
            ->get(route('quizzes.create', $enrollment->classroom))
            ->assertForbidden();
    }

    public function test_student_answers_and_gets_the_score(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $questions = $quiz->questions()->get();

        Livewire::actingAs($student)->test(Play::class, ['quiz' => $quiz])
            ->assertDontSee('Explicação secreta')
            ->assertDontSee('Correta')
            ->set("answers.{$questions[0]->id}", 0)
            ->set("answers.{$questions[1]->id}", 0)
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSee('1 de 2')
            ->assertSee('Explicação secreta');

        $this->assertDatabaseHas('quiz_attempts', ['quiz_id' => $quiz->id, 'user_id' => $student->id, 'score' => 1, 'total' => 2]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'quiz.answered']);
    }

    public function test_second_attempt_does_not_change_the_score(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $questions = $quiz->questions()->get();

        QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 0,
            'total' => 2,
            'answers' => [$questions[0]->id => 1, $questions[1]->id => 0],
        ]);

        Livewire::actingAs($student)->test(Play::class, ['quiz' => $quiz])
            ->set("answers.{$questions[0]->id}", 0)
            ->set("answers.{$questions[1]->id}", 1)
            ->call('submit');

        $this->assertSame(0, QuizAttempt::sole()->score);
    }

    public function test_every_question_needs_a_real_answer(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        $questions = $quiz->questions()->get();

        Livewire::actingAs($student)->test(Play::class, ['quiz' => $quiz])
            ->set("answers.{$questions[0]->id}", 0)
            ->set("answers.{$questions[1]->id}", 99)
            ->call('submit')
            ->assertHasErrors(['answers'])
            ->assertSee('Responda todas as perguntas antes de enviar.');

        $this->assertDatabaseCount('quiz_attempts', 0);
    }

    public function test_teacher_cannot_submit_answers(): void
    {
        [$quiz] = $this->publishedQuiz();

        Livewire::actingAs($quiz->classroom->teacher)->test(Play::class, ['quiz' => $quiz])
            ->call('submit')
            ->assertForbidden();
    }

    public function test_outsider_gets_not_found(): void
    {
        [$quiz] = $this->publishedQuiz();

        $this->actingAs(User::factory()->create())->get(route('quizzes.show', $quiz))->assertNotFound();
        $this->actingAs(User::factory()->teacher()->create())->get(route('quizzes.results', $quiz))->assertNotFound();
    }

    public function test_quiz_with_answers_cannot_be_edited(): void
    {
        [$quiz, $student] = $this->publishedQuiz();

        QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $student->id, 'score' => 1, 'total' => 2, 'answers' => []]);

        Livewire::actingAs($quiz->classroom->teacher)->test(Editor::class, ['quiz' => $quiz])
            ->call('saveDraft')
            ->assertSee('Este quiz já tem respostas e não pode mais ser alterado.');
    }

    public function test_results_show_who_did_not_answer(): void
    {
        [$quiz, $student] = $this->publishedQuiz();
        Enrollment::factory()->for($quiz->classroom)->for(User::factory()->state(['name' => 'Quem Faltou']))->create();

        QuizAttempt::create(['quiz_id' => $quiz->id, 'user_id' => $student->id, 'score' => 2, 'total' => 2, 'answers' => []]);

        $this->actingAs($quiz->classroom->teacher)
            ->get(route('quizzes.results', $quiz))
            ->assertOk()
            ->assertSee('1 de 2')
            ->assertSeeInOrder(['Quem Faltou', 'Não respondeu']);
    }

    public function test_ai_generator_is_hidden_until_connected(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Editor::class, ['classroom' => $classroom])
            ->assertSee('A IA ainda não foi conectada.')
            ->set('aiTopic', 'HTML')
            ->call('generateWithAi')
            ->assertNotFound();
    }

    public function test_connected_ai_fills_questions_for_review(): void
    {
        $this->app->instance(QuizQuestionGenerator::class, new class implements QuizQuestionGenerator
        {
            public function isAvailable(): bool
            {
                return true;
            }

            public function generate(string $topic, int $count): array
            {
                return [['prompt' => "Sobre {$topic}", 'options' => ['Sim', 'Não'], 'correct' => 0, 'explanation' => '']];
            }
        });

        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Editor::class, ['classroom' => $classroom])
            ->set('aiTopic', 'HTML')
            ->call('generateWithAi')
            ->assertSet('questions.0.prompt', 'Sobre HTML')
            ->assertCount('questions', 1);

        $this->assertDatabaseCount('quizzes', 0);
    }

    private function fillEditor(Classroom $classroom): Testable
    {
        return Livewire::actingAs($classroom->teacher)->test(Editor::class, ['classroom' => $classroom])
            ->set('title', 'Quiz de HTML')
            ->set('questions.0.prompt', 'Qual tag cria um link?')
            ->set('questions.0.options.0', '<a>')
            ->set('questions.0.options.1', '<p>')
            ->set('questions.0.correct', 0);
    }

    /**
     * @return array{Quiz, User}
     */
    private function publishedQuiz(): array
    {
        $quiz = Quiz::factory()->published()->create();
        QuizQuestion::factory()->for($quiz)->create(['position' => 1, 'correct_option' => 0, 'explanation' => 'Explicação secreta']);
        QuizQuestion::factory()->for($quiz)->create(['position' => 2, 'correct_option' => 1]);

        return [$quiz, Enrollment::factory()->for($quiz->classroom)->create()->user];
    }
}
