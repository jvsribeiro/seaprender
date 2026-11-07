<?php

namespace App\Actions\Posts;

use App\Actions\RecordAuditLog;
use App\Models\Classroom;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class PublishPost
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    /**
     * @param  array<string, mixed>  $attributes
     *
     * @throws ValidationException
     */
    public function handle(User $author, Classroom $classroom, array $attributes, ?UploadedFile $attachment = null): Post
    {
        if (! $classroom->isActive()) {
            throw ValidationException::withMessages(['classroom' => 'Reative a turma para publicar.']);
        }

        if ($attachment) {
            $attributes['file_path'] = $attachment->store("classrooms/{$classroom->id}", 'local');
            $attributes['file_name'] = basename($attachment->getClientOriginalName());
        }

        $post = $classroom->posts()->create([...$attributes, 'author_id' => $author->id]);

        $this->recordAuditLog->handle($author, 'post.published', $post, ['title' => $post->title]);

        return $post;
    }
}
