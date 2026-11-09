<?php

namespace Tests\Feature\Classrooms;

use App\Actions\Posts\RemovePost;
use App\Actions\RecordAuditLog;
use App\Livewire\Classrooms\Audit;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_sees_class_events_with_details_after_the_original_post_is_removed(): void
    {
        $classroom = Classroom::factory()->create();
        $otherClassroom = Classroom::factory()->create();
        $teacher = $classroom->teacher;
        $post = Post::factory()->for($classroom)->create(['title' => 'Atividade removida de HTML']);
        $otherPost = Post::factory()->for($otherClassroom)->create(['title' => 'Atividade de outra turma']);

        $record = app(RecordAuditLog::class);
        $record->handle($teacher, 'auth.login');
        $record->handle($teacher, 'classroom.updated', $classroom);
        $record->handle($otherClassroom->teacher, 'post.published', $otherPost, ['title' => $otherPost->title]);
        app(RemovePost::class)->handle($teacher, $post);

        $this->actingAs($teacher)->get(route('classrooms.show', ['classroom' => $classroom, 'aba' => 'auditoria']))
            ->assertOk()->assertSee('Auditoria da turma')->assertSee('Atividade removida de HTML')
            ->assertDontSee('Atividade de outra turma')->assertDontSee('Entrou no sistema');

        Livewire::test(Audit::class, ['classroom' => $classroom])
            ->assertSee('Turma editada')->set('category', 'post')->assertDontSee('Turma editada')
            ->assertSee('Removeu do mural');
    }

    public function test_student_and_other_teacher_cannot_read_class_audit(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;
        $otherClassroom = Classroom::factory()->create();

        Livewire::actingAs($student)->test(Audit::class, ['classroom' => $classroom])->assertForbidden();
        Livewire::actingAs($otherClassroom->teacher)->test(Audit::class, ['classroom' => $classroom])->assertStatus(404);
        $this->actingAs($student)->get(route('classrooms.show', ['classroom' => $classroom, 'aba' => 'auditoria']))
            ->assertOk()->assertDontSee('Auditoria da turma');
    }
}
