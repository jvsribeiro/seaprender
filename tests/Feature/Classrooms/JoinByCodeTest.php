<?php

namespace Tests\Feature\Classrooms;

use App\Enums\EnrollmentStatus;
use App\Livewire\Classrooms\Invite;
use App\Livewire\Classrooms\JoinForm;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class JoinByCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_lowercase_code_creates_one_pending_request_in_a_closed_catalog(): void
    {
        config(['app.classroom_open_enrollment' => false]);
        $student = User::factory()->create();
        $classroom = Classroom::factory()->create(['enrollment_code' => 'ABCD2345']);

        for ($attempt = 0; $attempt < 2; $attempt++) {
            Livewire::actingAs($student)->test(JoinForm::class)
                ->set('code', ' abcd2345 ')
                ->call('join')
                ->assertRedirect(route('classrooms.browse'));
        }

        $this->assertDatabaseCount('enrollments', 1);
        $this->assertDatabaseHas('enrollments', ['classroom_id' => $classroom->id, 'user_id' => $student->id, 'status' => EnrollmentStatus::Pending->value]);
        $this->get(route('classrooms.browse'))->assertSee($classroom->name)->assertSee('Aguardando aprovação');
        $this->get(route('classrooms.show', $classroom))->assertNotFound();
    }

    public function test_invalid_and_archived_codes_are_refused_the_same_way(): void
    {
        $classroom = Classroom::factory()->archived()->create();

        foreach (['NAOEXISTE', $classroom->enrollment_code] as $code) {
            Livewire::actingAs(User::factory()->create())->test(JoinForm::class)
                ->set('code', $code)
                ->call('join')
                ->assertHasErrors(['code'])
                ->assertSee('Código inválido. Confira com o professor.');
        }

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_teacher_cannot_join_their_own_classroom(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(JoinForm::class)
            ->set('code', $classroom->enrollment_code)
            ->call('join')
            ->assertSee('Você é o professor desta turma.');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_another_teacher_cannot_enter_with_a_code(): void
    {
        $classroom = Classroom::factory()->create();
        $otherTeacher = User::factory()->teacher()->create();

        Livewire::actingAs($otherTeacher)->test(JoinForm::class)
            ->set('code', $classroom->enrollment_code)
            ->call('join')
            ->assertHasErrors(['code'])
            ->assertSee('Só contas de aluno podem se inscrever em turmas.');

        $this->assertDatabaseCount('enrollments', 0);
        $this->actingAs($otherTeacher)->get(route('classrooms.show', $classroom))->assertNotFound();
    }

    public function test_guessing_codes_is_throttled(): void
    {
        $student = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $form = Livewire::actingAs($student)->test(JoinForm::class);

        foreach (range(1, 10) as $attempt) {
            $form->set('code', "ERRADO{$attempt}")->call('join');
        }

        $form->set('code', $classroom->enrollment_code)
            ->call('join')
            ->assertSee('Muitas tentativas. Aguarde um minuto e tente de novo.');

        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_invite_link_shows_the_classroom_and_requests_approval(): void
    {
        $student = User::factory()->create();
        $classroom = Classroom::factory()->create(['name' => 'Turma convidada', 'enrollment_code' => 'ABCD2345']);

        Livewire::actingAs($student)->test(Invite::class, ['code' => 'abcd2345'])
            ->assertSee('Turma convidada')
            ->call('join')
            ->assertRedirect(route('classrooms.browse'));

        $this->assertSame(EnrollmentStatus::Pending, $classroom->enrollments()->sole()->status);
        $this->get(route('invite', 'ABCD2345'))->assertSee('Aguardando aprovação')->assertDontSee('Abrir turma');
    }

    public function test_invalid_invite_says_so(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('invite', 'NAOEXISTE'))
            ->assertOk()
            ->assertSee('Convite inválido');
    }

    public function test_guest_returns_to_the_invite_after_logging_in(): void
    {
        $student = User::factory()->create(['password' => 'senha-de-teste-123']);
        $url = route('invite', 'ABCD2345');

        $this->get($url)->assertRedirect('/login');

        $this->post('/login', ['email' => $student->email, 'password' => 'senha-de-teste-123'])
            ->assertRedirect($url);
    }
}
