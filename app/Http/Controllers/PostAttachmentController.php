<?php

namespace App\Http\Controllers;

use App\Actions\RecordAuditLog;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PostAttachmentController extends Controller
{
    public function __invoke(Request $request, Post $post, RecordAuditLog $recordAuditLog): StreamedResponse
    {
        Gate::authorize('view', $post->classroom);

        abort_unless($post->file_path && Storage::disk('local')->exists($post->file_path), 404);

        $recordAuditLog->handle($request->user(), 'post.downloaded', $post, ['title' => $post->title]);

        return Storage::disk('local')->download($post->file_path, $post->file_name);
    }
}
