<?php

namespace App\Actions\Classrooms;

use App\Models\Classroom;

class GenerateEnrollmentCode
{
    /**
     * Letters and digits that are easy to tell apart on a projector.
     */
    private const ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    private const LENGTH = 8;

    public function handle(): string
    {
        do {
            $code = $this->randomCode();
        } while (Classroom::where('enrollment_code', $code)->exists());

        return $code;
    }

    private function randomCode(): string
    {
        $code = '';

        for ($position = 0; $position < self::LENGTH; $position++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }
}
