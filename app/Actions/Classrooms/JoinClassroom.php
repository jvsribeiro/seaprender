<?php

namespace App\Actions\Classrooms;

use App\Enums\ClassroomStatus;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class JoinClassroom
{
    private const MAX_FAILED_ATTEMPTS_PER_MINUTE = 10;

    public function __construct(private EnrollInClassroom $enrollInClassroom) {}

    /**
     * @throws ValidationException
     */
    public function handle(User $user, string $code): Enrollment
    {
        $throttleKey = "join-classroom:{$user->id}";

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_FAILED_ATTEMPTS_PER_MINUTE)) {
            throw ValidationException::withMessages(['code' => 'Muitas tentativas. Aguarde um minuto e tente de novo.']);
        }

        $classroom = Classroom::where('enrollment_code', Str::upper(trim($code)))
            ->where('status', ClassroomStatus::Active)
            ->first();

        if (! $classroom) {
            RateLimiter::hit($throttleKey);

            throw ValidationException::withMessages(['code' => 'Código inválido. Confira com o professor.']);
        }

        return $this->enrollInClassroom->handle($user, $classroom);
    }
}
