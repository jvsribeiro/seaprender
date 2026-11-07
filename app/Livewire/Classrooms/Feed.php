<?php

namespace App\Livewire\Classrooms;

use App\Actions\Posts\PublishPost;
use App\Actions\Posts\RemovePost;
use App\Enums\PostType;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Feed extends Component
{
    use WithFileUploads, WithPagination;

    #[Locked]
    public Classroom $classroom;

    public bool $composing = false;

    public string $type = 'announcement';

    public string $title = '';

    public string $body = '';

    public string $url = '';

    public string $dueOn = '';

    public string $subjectId = '';

    /** @var UploadedFile|null */
    public $attachment = null;

    public string $newSubject = '';

    public int $filterSubject = 0;

    public function mount(Classroom $classroom): void
    {
        Gate::authorize('view', $classroom);

        $this->classroom = $classroom;
    }

    public function updatedFilterSubject(): void
    {
        $this->resetPage();
    }

    public function publish(PublishPost $publishPost): void
    {
        Gate::authorize('manage', $this->classroom);

        $this->validate([
            'type' => ['required', Rule::in(array_column(PostType::writable(), 'value'))],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['nullable', 'string', 'max:5000'],
            'url' => ['nullable', 'required_if:type,link', 'url:http,https', 'max:2048'],
            'dueOn' => ['nullable', 'date'],
            'subjectId' => ['nullable', Rule::exists('subjects', 'id')->where('classroom_id', $this->classroom->id)],
            'attachment' => [
                Rule::requiredIf($this->type === PostType::Slide->value && $this->url === ''),
                'nullable',
                'file',
                'mimes:pdf,ppt,pptx,doc,docx,xls,xlsx,txt,png,jpg,jpeg,zip',
                'max:12288',
            ],
        ], [
            'attachment.required' => 'Envie um arquivo ou informe um link.',
        ], [
            'title' => 'título',
            'body' => 'texto',
            'url' => 'link',
            'dueOn' => 'entrega',
            'subjectId' => 'matéria',
            'attachment' => 'arquivo',
        ]);

        $publishPost->handle($this->user(), $this->classroom, [
            'type' => PostType::from($this->type),
            'title' => $this->title,
            'body' => $this->body ?: null,
            'url' => $this->url ?: null,
            'due_on' => $this->dueOn ?: null,
            'subject_id' => $this->subjectId ?: null,
        ], $this->attachment);

        $this->reset('composing', 'type', 'title', 'body', 'url', 'dueOn', 'subjectId', 'attachment');
        $this->resetPage();
        $this->dispatch('toast', message: 'Publicado no mural.');
    }

    public function removePost(int $postId, RemovePost $removePost): void
    {
        Gate::authorize('manage', $this->classroom);

        $removePost->handle($this->user(), $this->classroom->posts()->findOrFail($postId));

        $this->dispatch('toast', message: 'Publicação removida.');
    }

    public function toggleLike(int $postId): void
    {
        Gate::authorize('view', $this->classroom);

        $this->classroom->posts()->findOrFail($postId)->likers()->toggle($this->user()->id);
    }

    public function addSubject(): void
    {
        Gate::authorize('manage', $this->classroom);

        $this->validate([
            'newSubject' => [
                'required',
                'string',
                'max:80',
                Rule::unique('subjects', 'name')->where('classroom_id', $this->classroom->id),
            ],
        ], [
            'newSubject.unique' => 'Esta matéria já existe na turma.',
        ], ['newSubject' => 'matéria']);

        $this->classroom->subjects()->create(['name' => $this->newSubject]);

        $this->reset('newSubject');
    }

    public function removeSubject(int $subjectId): void
    {
        Gate::authorize('manage', $this->classroom);

        $this->classroom->subjects()->findOrFail($subjectId)->delete();

        if ($this->filterSubject === $subjectId) {
            $this->filterSubject = 0;
        }
    }

    public function render(): View
    {
        $user = $this->user();
        $canManage = Gate::allows('manage', $this->classroom);

        $posts = $this->classroom->posts()
            ->with(['author:id,name', 'subject:id,name', ...($canManage ? ['viewers:id'] : [])])
            ->withCount('likers')
            ->withExists(['likers as liked' => fn ($query) => $query->where('users.id', $user->id)])
            ->when($this->filterSubject > 0, fn ($query) => $query->where('subject_id', $this->filterSubject))
            ->latest('id')
            ->paginate(10);

        if ($user->isStudent()) {
            DB::table('post_views')->insertOrIgnore(
                $posts->getCollection()
                    ->map(fn (Post $post) => ['post_id' => $post->id, 'user_id' => $user->id, 'viewed_at' => now()])
                    ->all(),
            );
        }

        return view('livewire.classrooms.feed', [
            'canManage' => $canManage,
            'posts' => $posts,
            'postTypes' => PostType::writable(),
            'subjects' => $this->classroom->subjects()->orderBy('name')->get(),
            'students' => $canManage
                ? $this->classroom->activeEnrollments()->with('user:id,name')->get()->map(fn (Enrollment $enrollment) => $enrollment->user)
                : collect(),
        ]);
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
