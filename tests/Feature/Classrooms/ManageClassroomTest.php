<?php

namespace Tests\Feature\Classrooms;

use App\Enums\ClassroomStatus;
use App\Livewire\Classrooms\Form;
use App\Livewire\Classrooms\Show;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ManageClassroomTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_creates_a_classroom_with_an_enrollment_code(): void
    {
        $teacher = User::factory()->teacher()->create();

        Livewire::actingAs($teacher)->test(Form::class)
            ->set('name', 'Programação Web T1')
            ->set('course', 'Técnico em Informática')
            ->set('curricular_unit', 'Desenvolvimento Web')
            ->set('period', 'Noite')
            ->set('starts_on', '2026-02-02')
            ->set('ends_on', '2026-07-10')
            ->call('save')
            ->assertHasNoErrors();

        $classroom = Classroom::sole();

        $this->assertTrue($classroom->teacher->is($teacher));
        $this->assertSame(8, strlen($classroom->enrollment_code));
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $teacher->id, 'action' => 'classroom.created']);
    }

    public function test_classroom_cannot_end_before_it_starts(): void
    {
        Livewire::actingAs(User::factory()->teacher()->create())->test(Form::class)
            ->set('name', 'Turma')
            ->set('course', 'Curso')
            ->set('curricular_unit', 'Unidade')
            ->set('starts_on', '2026-07-10')
            ->set('ends_on', '2026-02-02')
            ->call('save')
            ->assertHasErrors(['ends_on'])
            ->assertSee('O campo término precisa ser igual ou posterior a');

        $this->assertDatabaseCount('classrooms', 0);
    }

    public function test_period_outside_the_list_is_refused(): void
    {
        Livewire::actingAs(User::factory()->teacher()->create())->test(Form::class)
            ->set('period', 'Madrugada')
            ->call('save')
            ->assertHasErrors(['period']);
    }

    public function test_student_cannot_open_the_creation_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('classrooms.create'))
            ->assertForbidden()
            ->assertSee('Acesso negado');
    }

    public function test_teacher_edits_their_classroom(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Form::class, ['classroom' => $classroom])
            ->assertSet('name', $classroom->name)
            ->set('name', 'Novo nome')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Novo nome', $classroom->refresh()->name);
    }

    public function test_another_teacher_gets_not_found_for_the_classroom(): void
    {
        $classroom = Classroom::factory()->create();

        $this->actingAs(User::factory()->teacher()->create());

        $this->get(route('classrooms.show', $classroom))->assertNotFound();
        $this->get(route('classrooms.edit', $classroom))->assertNotFound();
    }

    public function test_teacher_lists_only_their_classrooms(): void
    {
        $classroom = Classroom::factory()->create(['name' => 'Minha turma']);
        Classroom::factory()->create(['name' => 'Turma de outra pessoa']);

        $this->actingAs($classroom->teacher)
            ->get(route('classrooms.index'))
            ->assertSee('Minha turma')
            ->assertDontSee('Turma de outra pessoa');
    }

    public function test_enrolled_student_does_not_see_roster_or_code(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;
        $classmate = Enrollment::factory()->for($classroom)->create()->user;

        $this->actingAs($student)
            ->get(route('classrooms.show', $classroom))
            ->assertOk()
            ->assertSee($classroom->name)
            ->assertDontSee($classmate->email)
            ->assertDontSee($classroom->enrollment_code);
    }

    public function test_student_cannot_run_teacher_actions(): void
    {
        $classroom = Classroom::factory()->create();
        $student = Enrollment::factory()->for($classroom)->create()->user;
        $code = $classroom->enrollment_code;

        Livewire::actingAs($student)->test(Show::class, ['classroom' => $classroom])
            ->call('regenerateCode')
            ->assertForbidden();

        $this->assertSame($code, $classroom->refresh()->enrollment_code);
    }

    public function test_classroom_name_is_escaped(): void
    {
        $classroom = Classroom::factory()->create(['name' => '<script>alert(1)</script>']);

        $this->actingAs($classroom->teacher)
            ->get(route('classrooms.show', $classroom))
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_regenerating_the_code_invalidates_the_old_one(): void
    {
        $classroom = Classroom::factory()->create();
        $code = $classroom->enrollment_code;

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->call('regenerateCode');

        $this->assertNotSame($code, $classroom->refresh()->enrollment_code);
        $this->assertDatabaseHas('audit_logs', ['action' => 'classroom.code_regenerated']);
    }

    public function test_archived_classroom_can_be_reactivated_and_blocks_new_students_meanwhile(): void
    {
        $classroom = Classroom::factory()->create();

        $page = Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->call('toggleArchived');

        $this->assertSame(ClassroomStatus::Archived, $classroom->refresh()->status);

        $page->set('studentName', 'Ana')
            ->set('studentEmail', 'ana@example.test')
            ->call('addStudent')
            ->assertHasErrors(['classroom']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.test']);

        $page->call('toggleArchived');

        $this->assertSame(ClassroomStatus::Active, $classroom->refresh()->status);
    }
}
