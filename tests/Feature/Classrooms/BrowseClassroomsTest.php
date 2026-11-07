<?php

namespace Tests\Feature\Classrooms;

use App\Enums\ClassroomStatus;
use App\Livewire\Classrooms\Browse;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BrowseClassroomsTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_student_registers_finds_a_classroom_and_enrolls(): void
    {
        $classroom = Classroom::factory()->create(['name' => 'Programação Web T1']);

        $this->post('/register', [
            'name' => 'Ana Souza',
            'email' => 'ana@example.test',
            'password' => 'senha-de-teste-123',
            'password_confirmation' => 'senha-de-teste-123',
        ])->assertRedirect('/painel');

        $student = User::where('email', 'ana@example.test')->sole();

        $this->get(route('classrooms.browse'))->assertOk()->assertSee('Programação Web T1');

        Livewire::actingAs($student)->test(Browse::class)
            ->call('enroll', $classroom->id)
            ->assertRedirect(route('classrooms.show', $classroom));

        $this->assertDatabaseHas('enrollments', ['classroom_id' => $classroom->id, 'user_id' => $student->id]);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $student->id, 'action' => 'enrollment.joined']);
        $this->get(route('classrooms.show', $classroom))->assertOk();
    }

    public function test_catalog_lists_open_classrooms_without_student_data(): void
    {
        $student = User::factory()->create();
        $classroom = Classroom::factory()->create(['name' => 'Turma aberta']);
        $classmate = Enrollment::factory()->for($classroom)->create()->user;
        Classroom::factory()->archived()->create(['name' => 'Turma encerrada']);

        $this->actingAs($student)
            ->get(route('classrooms.browse'))
            ->assertSee('Turma aberta')
            ->assertDontSee('Turma encerrada')
            ->assertDontSee($classmate->email)
            ->assertDontSee($classmate->name)
            ->assertDontSee($classroom->enrollment_code);
    }

    public function test_search_matches_course_and_teacher(): void
    {
        $student = User::factory()->create();
        $teacher = User::factory()->teacher()->create(['name' => 'Renata Costa']);
        Classroom::factory()->for($teacher, 'teacher')->create(['name' => 'Redes T2']);
        Classroom::factory()->create(['name' => 'Design T3', 'course' => 'Técnico em Multimídia']);

        Livewire::actingAs($student)->test(Browse::class)
            ->set('search', 'renata')
            ->assertSee('Redes T2')
            ->assertDontSee('Design T3')
            ->set('search', 'multimídia')
            ->assertSee('Design T3')
            ->assertDontSee('Redes T2')
            ->set('search', 'nada parecido')
            ->assertSee('Nada encontrado');
    }

    public function test_enrolled_student_sees_a_link_instead_of_the_enroll_button(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($enrollment->user)
            ->get(route('classrooms.browse'))
            ->assertSee('Abrir turma')
            ->assertDontSee('Inscrever-se');
    }

    public function test_enrolling_twice_keeps_one_enrollment(): void
    {
        $enrollment = Enrollment::factory()->create();

        Livewire::actingAs($enrollment->user)->test(Browse::class)
            ->call('enroll', $enrollment->classroom_id)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('enrollments', 1);
    }

    public function test_removed_student_cannot_enroll_again_or_see_the_classroom(): void
    {
        $enrollment = Enrollment::factory()->removed()->create();

        Livewire::actingAs($enrollment->user)->test(Browse::class)
            ->assertDontSee($enrollment->classroom->name)
            ->call('enroll', $enrollment->classroom_id)
            ->assertHasErrors(['code'])
            ->assertSee('Sua matrícula nesta turma foi encerrada. Fale com o professor.');
    }

    public function test_classroom_archived_after_the_page_loaded_refuses_enrollment(): void
    {
        $student = User::factory()->create();
        $classroom = Classroom::factory()->create();

        $page = Livewire::actingAs($student)->test(Browse::class);

        $classroom->status = ClassroomStatus::Archived;
        $classroom->save();

        $page->call('enroll', $classroom->id)
            ->assertSee('Esta turma não está aceitando inscrições.');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_long_catalog_is_paginated_in_portuguese(): void
    {
        Classroom::factory()->count(13)->create();

        Livewire::actingAs(User::factory()->create())->test(Browse::class)
            ->assertSee('Página 1 de 2')
            ->call('nextPage')
            ->assertSee('Página 2 de 2');
    }

    public function test_teacher_cannot_open_the_catalog(): void
    {
        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('classrooms.browse'))
            ->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('classrooms.browse'))->assertRedirect('/login');
    }
}
