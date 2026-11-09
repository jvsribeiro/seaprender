<?php

namespace App\Support;

use App\Enums\ClassroomStatus;
use App\Enums\PostType;
use App\Enums\QuizStatus;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

final class ClassroomProgress
{
    /** @return array{completed: int, total: int} */
    public static function forStudent(User $student): array
    {
        $classrooms = $student->enrolledClassrooms()->where('classrooms.status', ClassroomStatus::Active)->select('classrooms.id');
        $quizzes = DB::table('quizzes')
            ->leftJoin('quiz_attempts', fn ($join) => $join->on('quiz_attempts.quiz_id', '=', 'quizzes.id')->where('quiz_attempts.user_id', $student->id))
            ->whereIn('quizzes.classroom_id', $classrooms)->where('quizzes.status', QuizStatus::Published)
            ->selectRaw('count(*) as total, count(quiz_attempts.user_id) as completed')->first();
        $reflections = DB::table('posts')
            ->leftJoin('post_responses', fn ($join) => $join->on('post_responses.post_id', '=', 'posts.id')->where('post_responses.user_id', $student->id))
            ->whereIn('posts.classroom_id', $classrooms)->where('posts.type', PostType::Reflection)
            ->selectRaw('count(*) as total, count(post_responses.user_id) as completed')->first();

        return [
            'completed' => (int) $quizzes->completed + (int) $reflections->completed,
            'total' => (int) $quizzes->total + (int) $reflections->total,
        ];
    }

    /** @return array{quiz: int, reflection: int} */
    public static function totals(Classroom $classroom): array
    {
        return [
            'quiz' => $classroom->quizzes()->where('status', QuizStatus::Published)->count(),
            'reflection' => $classroom->posts()->where('type', PostType::Reflection)->count(),
        ];
    }

    /** @param Builder<User> $learners */
    public static function addCounts(Builder $learners, Classroom $classroom): void
    {
        $learners
            ->withCount(['quizAttempts as quiz_count' => fn ($query) => $query->whereHas('quiz', fn ($quizzes) => $quizzes
                ->where('classroom_id', $classroom->id)->where('status', QuizStatus::Published))])
            ->addSelect(['reflection_count' => self::reflections($classroom)
                ->whereColumn('post_responses.user_id', 'users.id')->selectRaw('count(*)')]);
    }

    public static function reflections(Classroom $classroom): QueryBuilder
    {
        return DB::table('post_responses')->join('posts', 'posts.id', '=', 'post_responses.post_id')
            ->where('posts.classroom_id', $classroom->id)->where('posts.type', PostType::Reflection);
    }
}
