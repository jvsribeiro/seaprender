<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\LiveQuizFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['classroom_id', 'question_game_id', 'game_settings', 'title', 'questions', 'seconds_per_question', 'phase', 'question_index', 'question_ends_at'])]
#[Hidden(['questions'])]
class LiveQuiz extends Model
{
    /** @use HasFactory<LiveQuizFactory> */
    use HasFactory;

    public const LOBBY = 'lobby';

    public const QUESTION = 'question';

    public const COUNTDOWN = 'countdown';

    public const COUNTDOWN_SECONDS = 3;

    public const REVEAL = 'reveal';

    public const FINISHED = 'finished';

    protected function casts(): array
    {
        return [
            'questions' => 'array',
            'game_settings' => 'array',
            'question_ends_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function questionOpensAt(): ?CarbonInterface
    {
        return $this->isQuestionGame() ? $this->question_ends_at?->copy()->subSeconds($this->seconds_per_question) : null;
    }

    public function currentPhase(?CarbonInterface $now = null): string
    {
        if ($this->phase !== self::QUESTION) {
            return $this->phase;
        }

        $now ??= now();

        if ($this->questionOpensAt()?->gt($now)) {
            return self::COUNTDOWN;
        }

        if ($this->question_ends_at?->lte($now)) {
            return self::REVEAL;
        }

        return $this->phase;
    }

    public function isQuestionGame(): bool
    {
        return $this->game_settings !== null;
    }
}
