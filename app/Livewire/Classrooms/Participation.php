<?php

namespace App\Livewire\Classrooms;

use App\Actions\RecordAuditLog;
use App\Enums\PostType;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithoutUrlPagination;
use Livewire\WithPagination;

class Participation extends Component
{
    use WithoutUrlPagination, WithPagination;

    #[Locked]
    public Post $post;

    public string $liked = '';

    public string $improve = '';

    public string $summary = '';

    public bool $showResponses = false;

    public function mount(Post $post): void
    {
        Gate::authorize('view', $post->classroom);
        abort_unless($post->type->acceptsResponses(), 404);

        $this->post = $post;

        if ($post->type === PostType::Reflection && $this->user()->isStudent()) {
            $response = $this->responses()->where('user_id', $this->user()->id)->first();
            $this->liked = $response->liked ?? '';
            $this->improve = $response->improve ?? '';
            $this->summary = $response->summary ?? '';
        }
    }

    public function saveReflection(RecordAuditLog $recordAuditLog): void
    {
        $this->authorizeStudent(PostType::Reflection);

        $content = $this->validate([
            'liked' => ['required', 'string', 'max:800'],
            'improve' => ['required', 'string', 'max:800'],
            'summary' => ['required', 'string', 'max:1500'],
        ], [], ['liked' => 'o que você gostou', 'improve' => 'o que pode melhorar', 'summary' => 'resumo']);

        DB::transaction(function () use ($content, $recordAuditLog) {
            $post = $this->lockOpenPost();
            $user = $this->user();
            $alreadyResponded = $this->responses()->where('user_id', $user->id)->exists();

            $this->responses()->upsert([
                'post_id' => $this->post->id,
                'user_id' => $user->id,
                ...$content,
                'created_at' => now(),
                'updated_at' => now(),
            ], ['post_id', 'user_id'], ['liked', 'improve', 'summary', 'updated_at']);

            if (! $alreadyResponded) {
                $recordAuditLog->handle($user, 'post.reflection_submitted', $post, ['title' => $post->title]);
            }
        });

        $this->dispatch('toast', message: 'Reflexão salva.');
    }

    public function vote(int $choice): void
    {
        $this->authorizeStudent(PostType::Poll);

        DB::transaction(function () use ($choice) {
            $post = $this->lockOpenPost();

            abort_unless(array_key_exists($choice, $post->poll_options ?? []), 422);

            $this->responses()->insertOrIgnore([
                'post_id' => $post->id,
                'user_id' => $this->user()->id,
                'choice' => $choice,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function close(RecordAuditLog $recordAuditLog): void
    {
        Gate::authorize('manage', $this->post->classroom);

        DB::transaction(function () use ($recordAuditLog) {
            $post = Post::query()->lockForUpdate()->findOrFail($this->post->id);
            if ($post->closed_at === null) {
                $post->closed_at = now();
                $post->save();
                $recordAuditLog->handle($this->user(), 'post.participation_closed', $post, ['title' => $post->title]);
            }
        });
    }

    public function render(): View
    {
        $post = $this->post->refresh();
        Gate::authorize('view', $post->classroom);

        $canManage = Gate::allows('manage', $post->classroom);
        $canRespond = $this->user()->isStudent() && $post->classroom->isActive() && $post->closed_at === null;
        $isPoll = $post->type === PostType::Poll;
        $showResults = $isPoll && ($canManage || $post->closed_at !== null);
        $responses = $this->responses();
        $own = $this->user()->isStudent() ? (clone $responses)->where('user_id', $this->user()->id)->first() : null;

        return view('livewire.classrooms.participation', [
            'post' => $post,
            'canManage' => $canManage,
            'canRespond' => $canRespond,
            'isPoll' => $isPoll,
            'showResults' => $showResults,
            'own' => $own,
            'totals' => $showResults
                ? (clone $responses)->select('choice')->selectRaw('count(*) as votes')->groupBy('choice')->pluck('votes', 'choice')
                : collect(),
            'responseCount' => ! $isPoll && $canManage ? (clone $responses)->count() : null,
            'reflections' => ! $isPoll && $canManage && $this->showResponses
                ? $responses->join('users', 'users.id', '=', 'post_responses.user_id')
                    ->orderByDesc('post_responses.updated_at')->orderBy('users.id')
                    ->paginate(10, ['post_responses.*', 'users.name'], 'reflections-'.$post->id)
                : null,
        ]);
    }

    private function authorizeStudent(PostType $type): void
    {
        Gate::authorize('view', $this->post->classroom);
        abort_unless($this->user()->isStudent() && $this->post->type === $type, 403);
    }

    private function lockOpenPost(): Post
    {
        $post = Post::query()->lockForUpdate()->findOrFail($this->post->id);
        if ($post->closed_at !== null || ! $post->classroom->isActive()) {
            throw ValidationException::withMessages(['response' => 'Esta participação está encerrada.']);
        }

        return $post;
    }

    private function responses(): Builder
    {
        return DB::table('post_responses')->where('post_id', $this->post->id);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
