<?php

namespace App\Livewire\QuestionGames;

use App\Actions\RecordAuditLog;
use App\Livewire\Quizzes\EditsQuestions;
use App\Models\Classroom;
use App\Models\QuestionGame;
use App\Support\ClassroomTheme;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Montar Se Pergunta')]
class Editor extends Component
{
    use EditsQuestions;

    #[Locked]
    public Classroom $classroom;

    #[Locked]
    public ?int $gameId = null;

    public string $title = '';

    public string $topic = '';

    public string $intro = '';

    public string $seconds = '30';

    public string $palette = 'orbit';

    public string $pattern = 'stars';

    public string $base = '#422275';

    public string $accent = '#CE9CFF';

    public function mount(Classroom $classroom, ?QuestionGame $questionGame = null): void
    {
        Gate::authorize('manage', $classroom);
        $this->classroom = $classroom;

        if (! $questionGame?->exists) {
            $this->addQuestion();

            return;
        }

        abort_unless($questionGame->classroom_id === $classroom->id, 404);
        $theme = $questionGame->theme;
        $this->gameId = $questionGame->id;
        $this->title = $questionGame->title;
        $this->topic = (string) $questionGame->topic;
        $this->intro = (string) $questionGame->intro;
        $this->seconds = (string) $questionGame->seconds_per_question;
        $this->palette = $theme['palette'];
        $this->pattern = $theme['pattern'];
        $this->base = $theme['base'];
        $this->accent = $theme['accent'];
        $this->questions = $questionGame->questions;
    }

    public function updatedPalette(string $palette): void
    {
        if (isset(ClassroomTheme::PRESETS[$palette])) {
            $theme = ClassroomTheme::PRESETS[$palette];
            $this->pattern = $theme['pattern'];
            $this->base = $theme['base'];
            $this->accent = $theme['accent'];
        }
    }

    public function save(RecordAuditLog $audit): void
    {
        Gate::authorize('manage', $this->classroom);

        $data = $this->validate([
            'title' => ['required', 'string', 'max:160'],
            'topic' => ['nullable', 'string', 'max:100'],
            'intro' => ['nullable', 'string', 'max:500'],
            'seconds' => ['required', 'integer', 'between:10,120'],
            'palette' => ['required', Rule::in([...array_keys(ClassroomTheme::PRESETS), 'custom'])],
            'pattern' => ['required', Rule::in(array_keys(ClassroomTheme::PATTERNS))],
            'base' => ['exclude_unless:palette,custom', 'required', ClassroomTheme::COLOR_RULE],
            'accent' => ['exclude_unless:palette,custom', 'required', ClassroomTheme::COLOR_RULE],
            ...$this->questionRules(),
            'questions.*' => ['array:prompt,options,correct,explanation'],
            'questions.*.options' => ['required', 'list', 'min:2', 'max:'.self::MAX_OPTIONS],
        ], [], [
            'title' => 'título',
            'topic' => 'tema do conteúdo',
            'intro' => 'introdução',
            'seconds' => 'tempo por pergunta',
            ...$this->questionLabels(),
        ]);

        $questions = [];
        foreach ($data['questions'] as $index => $question) {
            $correct = (int) $question['correct'];
            if (! array_key_exists($correct, $question['options'])) {
                throw ValidationException::withMessages(["questions.{$index}.correct" => 'Marque a alternativa correta.']);
            }

            $questions[] = [
                'prompt' => $question['prompt'],
                'options' => $question['options'],
                'correct' => $correct,
                'explanation' => $question['explanation'] ?? '',
            ];
        }

        DB::transaction(function () use ($audit, $data, $questions) {
            $classroom = Classroom::query()->lockForUpdate()->findOrFail($this->classroom->id);
            Gate::authorize('manage', $classroom);

            if (! $classroom->isActive()) {
                throw ValidationException::withMessages(['title' => 'Reative a turma para salvar jogos.']);
            }

            $game = $this->gameId
                ? QuestionGame::query()->where('classroom_id', $classroom->id)->findOrFail($this->gameId)
                : new QuestionGame(['classroom_id' => $classroom->id]);

            $game->fill([
                'title' => $data['title'],
                'topic' => $data['topic'] ?: null,
                'intro' => $data['intro'] ?: null,
                'seconds_per_question' => (int) $data['seconds'],
                'theme' => ClassroomTheme::resolve($data['palette'], $data['pattern'], $data['base'] ?? null, $data['accent'] ?? null),
                'questions' => $questions,
            ])->save();

            $this->gameId = $game->id;
            $audit->handle(Auth::user(), 'live_quiz.game_saved', $classroom, ['title' => $game->title]);
        });

        session()->flash('toast', 'Jogo salvo no Se Pergunta.');
        $this->redirect(route('classrooms.show', ['classroom' => $this->classroom, 'aba' => 'se-pergunta']), navigate: true);
    }

    public function render(): View
    {
        Gate::authorize('manage', $this->classroom);

        return view('livewire.question-games.editor', [
            'maxOptions' => self::MAX_OPTIONS,
            'palettes' => ClassroomTheme::PRESETS,
            'patterns' => ClassroomTheme::PATTERNS,
            'preview' => ClassroomTheme::resolve($this->palette, $this->pattern, $this->base, $this->accent),
        ]);
    }
}
