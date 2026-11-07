<?php

namespace App\Support;

use App\Contracts\QuizQuestionGenerator;

final class DisabledQuizQuestionGenerator implements QuizQuestionGenerator
{
    public function isAvailable(): bool
    {
        return false;
    }

    /**
     * @return list<array{prompt: string, options: list<string>, correct: int, explanation: string}>
     */
    public function generate(string $topic, int $count): array
    {
        return [];
    }
}
