<?php

namespace Tests\Feature\Classrooms;

use App\Enums\PostType;
use App\Livewire\Classrooms\Feed;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_posts_a_link_and_the_student_sees_it(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('type', PostType::Link->value)
            ->set('title', 'Referência')
            ->set('url', 'https://example.test/html')
            ->call('publish')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('audit_logs', ['action' => 'post.published']);

        Livewire::actingAs($student)->test(Feed::class, ['classroom' => $classroom])
            ->assertSee('Referência')
            ->assertSeeHtml('href="https://example.test/html"')
            ->assertDontSee('Nova publicação');
    }

    public function test_only_web_addresses_are_accepted_as_links(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('type', PostType::Link->value)
            ->set('title', 'Armadilha')
            ->set('url', 'javascript:alert(1)')
            ->call('publish')
            ->assertHasErrors(['url']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_slide_needs_a_file_or_a_link(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('composing', true)
            ->set('type', PostType::Slide->value)
            ->set('title', 'Aula 1')
            ->call('publish')
            ->assertHasErrors(['attachment'])
            ->assertSee('Envie um arquivo ou informe um link.');
    }

    public function test_attachment_is_private_to_the_classroom(): void
    {
        Storage::fake('local');
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('type', PostType::Slide->value)
            ->set('title', 'Aula 1')
            ->set('attachment', UploadedFile::fake()->create('aula-1.pdf', 100, 'application/pdf'))
            ->call('publish')
            ->assertHasNoErrors();

        $post = Post::sole();

        Storage::disk('local')->assertExists($post->file_path);

        $this->actingAs($student)->get(route('posts.attachment', $post))->assertOk()->assertDownload('aula-1.pdf');
        $this->actingAs(User::factory()->create())->get(route('posts.attachment', $post))->assertNotFound();
        $this->actingAs(User::factory()->teacher()->create())->get(route('posts.attachment', $post))->assertNotFound();
    }

    public function test_executable_files_are_refused(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('title', 'Arquivo')
            ->set('attachment', UploadedFile::fake()->create('virus.exe', 10))
            ->call('publish')
            ->assertHasErrors(['attachment']);
    }

    public function test_removing_a_post_deletes_its_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('classrooms/1/aula.pdf', 'conteúdo');
        $classroom = Classroom::factory()->create();
        $post = Post::factory()->for($classroom)->create(['file_path' => 'classrooms/1/aula.pdf', 'file_name' => 'aula.pdf']);

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->call('removePost', $post->id);

        $this->assertModelMissing($post);
        Storage::disk('local')->assertMissing('classrooms/1/aula.pdf');
    }

    public function test_student_cannot_publish_or_remove(): void
    {
        $classroom = Classroom::factory()->create();
        $post = Post::factory()->for($classroom)->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;

        Livewire::actingAs($student)->test(Feed::class, ['classroom' => $classroom])
            ->set('title', 'Invasão')
            ->call('publish')
            ->assertForbidden();

        Livewire::actingAs($student)->test(Feed::class, ['classroom' => $classroom])
            ->call('removePost', $post->id)
            ->assertForbidden();

        $this->assertModelExists($post);
        $this->assertDatabaseCount('posts', 1);
    }

    public function test_outsider_cannot_open_the_feed(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs(User::factory()->create())->test(Feed::class, ['classroom' => $classroom])
            ->assertNotFound();
    }

    public function test_like_toggles_once_per_student(): void
    {
        $post = Post::factory()->create();
        $student = Enrollment::factory()->for($post->classroom)->create()->user;

        $feed = Livewire::actingAs($student)->test(Feed::class, ['classroom' => $post->classroom]);

        $feed->call('toggleLike', $post->id);
        $this->assertSame(1, $post->likers()->count());

        $feed->call('toggleLike', $post->id);
        $this->assertSame(0, $post->likers()->count());
    }

    public function test_post_from_another_classroom_cannot_be_liked_or_removed(): void
    {
        $classroom = Classroom::factory()->create();
        $otherPost = Post::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->call('toggleLike', $otherPost->id)
            ->assertNotFound();

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->call('removePost', $otherPost->id)
            ->assertNotFound();

        $this->assertModelExists($otherPost);
        $this->assertSame(0, $otherPost->likers()->count());
    }

    public function test_teacher_sees_who_saw_and_who_did_not(): void
    {
        $classroom = Classroom::factory()->create();
        Post::factory()->for($classroom)->create();
        $reader = Enrollment::factory()->for($classroom)->for(User::factory()->state(['name' => 'Quem Leu']))->create()->user;
        Enrollment::factory()->for($classroom)->for(User::factory()->state(['name' => 'Quem Faltou']))->create();

        Livewire::actingAs($reader)->test(Feed::class, ['classroom' => $classroom]);
        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->assertSee('Visto por 1 de 2')
            ->assertSee('Quem Faltou')
            ->assertDontSee('Quem Leu');

        $this->assertDatabaseCount('post_views', 1);
    }

    public function test_feed_filters_by_subject(): void
    {
        $classroom = Classroom::factory()->create();
        $subject = Subject::factory()->for($classroom)->create(['name' => 'HTML']);
        Post::factory()->for($classroom)->create(['title' => 'Da matéria', 'subject_id' => $subject->id]);
        Post::factory()->for($classroom)->create(['title' => 'Sem matéria']);

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('filterSubject', $subject->id)
            ->assertSee('Da matéria')
            ->assertDontSee('Sem matéria');
    }

    public function test_subject_from_another_classroom_is_refused(): void
    {
        $classroom = Classroom::factory()->create();
        $otherSubject = Subject::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('title', 'Aviso')
            ->set('subjectId', (string) $otherSubject->id)
            ->call('publish')
            ->assertHasErrors(['subjectId']);
    }

    public function test_teacher_adds_a_subject_without_duplicates(): void
    {
        $classroom = Classroom::factory()->create();

        $feed = Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('newSubject', 'CSS')
            ->call('addSubject')
            ->assertHasNoErrors();

        $feed->set('newSubject', 'CSS')
            ->call('addSubject')
            ->assertHasErrors(['newSubject'])
            ->assertSee('Esta matéria já existe na turma.');

        $this->assertSame(1, $classroom->subjects()->count());
    }

    public function test_post_text_is_escaped(): void
    {
        $post = Post::factory()->create(['body' => '<script>alert(1)</script>']);

        Livewire::actingAs($post->classroom->teacher)->test(Feed::class, ['classroom' => $post->classroom])
            ->assertDontSeeHtml('<script>alert(1)</script>');
    }

    public function test_archived_classroom_refuses_new_posts(): void
    {
        $classroom = Classroom::factory()->archived()->create();

        Livewire::actingAs($classroom->teacher)->test(Feed::class, ['classroom' => $classroom])
            ->set('title', 'Aviso')
            ->call('publish')
            ->assertHasErrors(['classroom']);
    }
}
