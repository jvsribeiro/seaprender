<?php

namespace App\Livewire\Quizzes;

trait EditsQuestions
{
    private const MAX_QUESTIONS = 30;

    private const MAX_OPTIONS = 5;

    /** @var list<array{prompt: string, options: list<string>, correct: int, explanation: string}> */
    public array $questions = [];

    public function addQuestion(): void
    {
        if (count($this->questions) < self::MAX_QUESTIONS) {
            $this->questions[] = ['prompt' => '', 'options' => ['', ''], 'correct' => 0, 'explanation' => ''];
        }
    }

    public function removeQuestion(int $index): void
    {
        unset($this->questions[$index]);

        $this->questions = array_values($this->questions);
    }

    public function addOption(int $index): void
    {
        if (isset($this->questions[$index]) && count($this->questions[$index]['options']) < self::MAX_OPTIONS) {
            $this->questions[$index]['options'][] = '';
        }
    }

    public function removeOption(int $index, int $option): void
    {
        if (! isset($this->questions[$index]) || count($this->questions[$index]['options']) <= 2) {
            return;
        }

        unset($this->questions[$index]['options'][$option]);

        $this->questions[$index]['options'] = array_values($this->questions[$index]['options']);
        $this->questions[$index]['correct'] = 0;
    }

    /** @return array<string, list<string>> */
    private function questionRules(): array
    {
        return [
            'questions' => ['required', 'array', 'min:1', 'max:'.self::MAX_QUESTIONS],
            'questions.*.prompt' => ['required', 'string', 'max:1000'],
            'questions.*.options' => ['required', 'array', 'min:2', 'max:'.self::MAX_OPTIONS],
            'questions.*.options.*' => ['required', 'string', 'max:300'],
            'questions.*.correct' => ['required', 'integer', 'min:0'],
            'questions.*.explanation' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    private function questionLabels(): array
    {
        return [
            'questions' => 'perguntas',
            'questions.*.prompt' => 'pergunta',
            'questions.*.options.*' => 'alternativa',
            'questions.*.correct' => 'alternativa correta',
            'questions.*.explanation' => 'explicação',
        ];
    }
}
