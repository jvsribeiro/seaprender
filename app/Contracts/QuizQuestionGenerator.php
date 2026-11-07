<?php

namespace App\Contracts;

interface QuizQuestionGenerator
{
    /**
     * Whether an AI provider is connected.
     */
    public function isAvailable(): bool;

    /**
     * Draft questions about a topic. The teacher reviews them before publishing.
     *
     * @return list<array{prompt: string, options: list<string>, correct: int, explanation: string}>
     */
    public function generate(string $topic, int $count): array;
}
