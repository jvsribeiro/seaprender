<?php

namespace App\Support;

final class ClassroomDisplayName
{
    public static function abbreviate(string $name): string
    {
        $parts = str($name)->squish()->explode(' ')
            ->reject(fn (string $part) => $part === '' || str_contains($part, '@'))
            ->values();

        if ($parts->isEmpty()) {
            return 'Aluno(a)';
        }

        return $parts->count() === 1 ? $parts->first() : $parts->first().' '.mb_substr($parts->last(), 0, 1).'.';
    }
}
