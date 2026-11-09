<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['classroom_id', 'title', 'topic', 'intro', 'seconds_per_question', 'theme', 'questions'])]
#[Hidden(['questions'])]
class QuestionGame extends Model
{
    protected function casts(): array
    {
        return [
            'theme' => 'array',
            'questions' => 'array',
        ];
    }
}
