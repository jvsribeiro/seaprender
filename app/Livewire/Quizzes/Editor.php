<?php

namespace App\Livewire\Quizzes;

use App\Actions\Quizzes\PublishQuiz;
use App\Actions\Quizzes\SaveQuiz;
use App\Contracts\QuizQuestionGenerator;
use App\Models\Classroom;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Quiz')]
class Editor extends Component
{
    use EditsQuestions;

    #[Locked]
    public int $classroomId;

    #[Locked]
    public ?int $quizId = null;

    public string $title = '';

    public string $description = '';

    public string $subjectId = '';

    public string $aiTopic = '';

    public function mount(?Classroom $classroom = null, ?Quiz $quiz = null): void
    {
        $classroom = $quiz?->exists ? $quiz->classroom : $classroom;

        Gate::authorize('manage', $classroom);

        $this->classroomId = $classroom->id;

        if (! $quiz?->exists) {
            $this->addQuestion();

            return;
        }

        $this->quizId = $quiz->id;
        $this->title = $quiz->title;
        $this->description = (string) $quiz->description;
        $this->subjectId = (string) $quiz->subject_id;
        $this->questions = $quiz->questions->map(fn (QuizQuestion $question) => [
            'prompt' => $question->prompt,
            'options' => $question->options,
            'correct' => $question->correct_option,
            'explanation' => (string) $question->explanation,
        ])->all();
    }

    public function generateWithAi(QuizQuestionGenerator $generator): void
    {
        Gate::authorize('manage', $this->classroom());

        abort_unless($generator->isAvailable(), 404);

        $this->validate(['aiTopic' => ['required', 'string', 'max:200']], [], ['aiTopic' => 'assunto']);

        $generated = $generator->generate($this->aiTopic, 5);

        $this->questions = array_slice([
            ...array_filter($this->questions, fn (array $question) => $question['prompt'] !== ''),
            ...$generated,
        ], 0, self::MAX_QUESTIONS);

        $this->reset('aiTopic');
    }

    public function saveDraft(SaveQuiz $saveQuiz): void
    {
        $this->persist($saveQuiz);

        session()->flash('toast', 'Rascunho salvo.');

        $this->returnToClassroom();
    }

    public function saveAndPublish(SaveQuiz $saveQuiz, PublishQuiz $publishQuiz): void
    {
        $publishQuiz->handle($this->user(), $this->persist($saveQuiz));

        session()->flash('toast', 'Quiz publicado no mural.');

        $this->returnToClassroom();
    }

    public function render(QuizQuestionGenerator $generator): View
    {
        return view('livewire.quizzes.editor', [
            'classroom' => $this->classroom(),
            'subjects' => $this->classroom()->subjects()->orderBy('name')->get(),
            'aiAvailable' => $generator->isAvailable(),
            'maxOptions' => self::MAX_OPTIONS,
        ]);
    }

    private function persist(SaveQuiz $saveQuiz): Quiz
    {
        $classroom = $this->classroom();

        Gate::authorize('manage', $classroom);

        $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'subjectId' => ['nullable', Rule::exists('subjects', 'id')->where('classroom_id', $classroom->id)],
            ...$this->questionRules(),
        ], [], [
            'title' => 'título',
            'description' => 'descrição',
            'subjectId' => 'matéria',
            ...$this->questionLabels(),
        ]);

        return $saveQuiz->handle(
            $this->user(),
            $classroom,
            $this->quizId ? $classroom->quizzes()->findOrFail($this->quizId) : null,
            [
                'title' => $this->title,
                'description' => $this->description ?: null,
                'subject_id' => $this->subjectId ? (int) $this->subjectId : null,
            ],
            $this->questions,
        );
    }

    private function returnToClassroom(): void
    {
        $this->redirect(route('classrooms.show', [$this->classroomId, 'aba' => 'quizzes']), navigate: true);
    }

    private function classroom(): Classroom
    {
        return Classroom::findOrFail($this->classroomId);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
