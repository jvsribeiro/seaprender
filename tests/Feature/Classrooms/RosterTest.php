<?php

namespace Tests\Feature\Classrooms;

use App\Enums\EnrollmentStatus;
use App\Enums\UserRole;
use App\Livewire\Classrooms\Show;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RosterTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_the_teacher_can_review_a_pending_request_and_rejection_keeps_access_closed(): void
    {
        $request = Enrollment::factory()->create(['status' => EnrollmentStatus::Pending]);
        $classroom = $request->classroom;
        $student = Enrollment::factory()->for($classroom)->create()->user;
        $otherClassroom = Classroom::factory()->create();

        Livewire::actingAs($otherClassroom->teacher)->test(Show::class, ['classroom' => $otherClassroom])
            ->call('reviewEnrollment', $request->id, true)->assertNotFound();
        Livewire::actingAs($student)->test(Show::class, ['classroom' => $classroom])
            ->call('reviewEnrollment', $request->id, true)->assertForbidden();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')->call('reviewEnrollment', $request->id, false)->assertHasNoErrors()
            ->call('reviewEnrollment', $request->id, true)->assertHasNoErrors();

        $this->assertSame(EnrollmentStatus::Rejected, $request->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['classroom_id' => $classroom->id, 'action' => 'enrollment.rejected']);
        $this->actingAs($request->user)->get(route('classrooms.browse'))->assertSee('Solicitação recusada');
        $this->get(route('classrooms.show', $classroom))->assertNotFound();
    }

    public function test_teacher_adds_a_new_student_by_email(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('studentName', 'Yara Potiguara')
            ->set('studentEmail', 'Yara@Example.test')
            ->call('addStudent')
            ->assertHasNoErrors()
            ->assertSee('Yara Potiguara');

        $student = User::where('email', 'yara@example.test')->sole();

        $this->assertSame(UserRole::Student, $student->role);
        $this->assertTrue($classroom->activeEnrollments()->where('user_id', $student->id)->exists());
    }

    public function test_adding_an_existing_user_does_not_duplicate_or_rename_them(): void
    {
        $classroom = Classroom::factory()->create();
        $student = User::factory()->create(['name' => 'Nome Original']);

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('studentName', 'Outro Nome')
            ->set('studentEmail', $student->email)
            ->call('addStudent');

        $this->assertDatabaseCount('users', 2);
        $this->assertSame('Nome Original', $student->refresh()->name);
    }

    public function test_staff_accounts_cannot_be_enrolled_as_students(): void
    {
        $classroom = Classroom::factory()->create();
        $otherTeacher = User::factory()->teacher()->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('studentName', $otherTeacher->name)
            ->set('studentEmail', $otherTeacher->email)
            ->call('addStudent')
            ->assertHasErrors(['studentEmail'])
            ->assertSee('Esse e-mail é de uma conta de professor ou da administração.');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_invalid_email_is_refused(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('studentName', 'Ana')
            ->set('studentEmail', 'nao-e-email')
            ->call('addStudent')
            ->assertHasErrors(['studentEmail'])
            ->assertSee('Informe um e-mail válido.');
    }

    public function test_teacher_removes_a_student_who_loses_access(): void
    {
        $enrollment = Enrollment::factory()->create();
        $classroom = $enrollment->classroom;

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->call('removeStudent', $enrollment->id);

        $this->assertSame(EnrollmentStatus::Removed, $enrollment->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'enrollment.removed']);

        $this->actingAs($enrollment->user)
            ->get(route('classrooms.show', $classroom))
            ->assertNotFound();
    }

    public function test_teacher_cannot_remove_a_student_from_another_classroom(): void
    {
        $classroom = Classroom::factory()->create();
        $otherEnrollment = Enrollment::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->call('removeStudent', $otherEnrollment->id)
            ->assertNotFound();

        $this->assertSame(EnrollmentStatus::Active, $otherEnrollment->refresh()->status);
    }

    public function test_student_cannot_remove_a_classmate(): void
    {
        $enrollment = Enrollment::factory()->create();
        $classroom = $enrollment->classroom;
        $student = Enrollment::factory()->for($classroom)->create()->user;

        Livewire::actingAs($student)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->call('removeStudent', $enrollment->id)
            ->assertForbidden();

        $this->assertSame(EnrollmentStatus::Active, $enrollment->refresh()->status);
    }

    public function test_teacher_can_bring_a_removed_student_back(): void
    {
        $enrollment = Enrollment::factory()->removed()->create();
        $classroom = $enrollment->classroom;

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->set('studentName', $enrollment->user->name)
            ->set('studentEmail', $enrollment->user->email)
            ->call('addStudent');

        $this->assertSame(EnrollmentStatus::Active, $enrollment->refresh()->status);
    }
}
