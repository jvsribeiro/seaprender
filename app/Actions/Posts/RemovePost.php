<?php

namespace App\Actions\Posts;

use App\Actions\RecordAuditLog;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class RemovePost
{
    public function __construct(private RecordAuditLog $recordAuditLog) {}

    public function handle(User $actor, Post $post): void
    {
        $this->recordAuditLog->handle($actor, 'post.removed', $post, ['title' => $post->title]);

        if ($post->file_path) {
            Storage::disk('local')->delete($post->file_path);
        }

        $post->delete();
    }
}
