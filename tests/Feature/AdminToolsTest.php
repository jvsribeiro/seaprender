<?php

namespace Tests\Feature;

use App\Livewire\Admin\Users;
use App\Livewire\Admin\UserShow;
use App\Livewire\Classrooms\Show;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_temporary_password_works_once_and_forces_a_new_one(): void
    {
        $student = User::factory()->create(['password' => 'senha-antiga-123']);

        $page = Livewire::actingAs(User::factory()->admin()->create())->test(UserShow::class, ['user' => $student])
            ->call('issueTemporaryPassword');

        $temporaryPassword = $page->get('temporaryPassword');

        $this->assertDatabaseHas('audit_logs', ['action' => 'user.password_reset']);
        $this->assertFalse(Hash::check('senha-antiga-123', $student->refresh()->password));

        auth()->logout();

        $this->post('/login', ['email' => $student->email, 'password' => $temporaryPassword])->assertRedirect('/painel');
        $this->get('/painel')->assertRedirect(route('password.change'));
        $this->get(route('classrooms.index'))->assertRedirect(route('password.change'));
        $this->get(route('password.change'))->assertOk();

        Livewire::actingAs($student->refresh())->test('account.change-password')
            ->set('password', 'minha-senha-nova-123')
            ->set('password_confirmation', 'minha-senha-nova-123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse($student->refresh()->must_change_password);
        $this->assertTrue(Hash::check('minha-senha-nova-123', $student->password));
        $this->actingAs($student)->get('/painel')->assertOk();
    }

    public function test_voluntary_password_change_needs_the_current_password(): void
    {
        $user = User::factory()->create(['password' => 'senha-atual-123']);

        Livewire::actingAs($user)->test('account.change-password')
            ->set('current_password', 'senha-errada')
            ->set('password', 'minha-senha-nova-123')
            ->set('password_confirmation', 'minha-senha-nova-123')
            ->call('save')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('senha-atual-123', $user->refresh()->password));
    }

    public function test_teacher_resets_the_password_of_their_student(): void
    {
        $enrollment = Enrollment::factory()->create();

        Livewire::actingAs($enrollment->classroom->teacher)->test(Show::class, ['classroom' => $enrollment->classroom])
            ->call('resetStudentPassword', $enrollment->id)
            ->assertSet('temporaryPasswordOwner', $enrollment->user->name);

        $this->assertTrue($enrollment->user->refresh()->must_change_password);
    }

    public function test_teacher_cannot_reset_someone_from_another_classroom(): void
    {
        $classroom = Classroom::factory()->create();
        $otherEnrollment = Enrollment::factory()->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->call('resetStudentPassword', $otherEnrollment->id)
            ->assertNotFound();

        $this->assertFalse($otherEnrollment->user->refresh()->must_change_password);
    }

    public function test_teacher_cannot_reset_the_password_of_a_staff_account_listed_in_the_class(): void
    {
        $classroom = Classroom::factory()->create();
        $enrollment = Enrollment::factory()->for($classroom)->for(User::factory()->admin())->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->call('resetStudentPassword', $enrollment->id)
            ->assertForbidden();

        $this->assertFalse($enrollment->user->refresh()->must_change_password);
    }

    public function test_teacher_cannot_reset_a_student_who_also_studies_with_another_teacher(): void
    {
        $enrollment = Enrollment::factory()->create();
        $classroom = $enrollment->classroom;
        Enrollment::factory()->for($enrollment->user)->create();

        Livewire::actingAs($classroom->teacher)->test(Show::class, ['classroom' => $classroom])
            ->set('tab', 'alunos')
            ->assertSee('Senha pela administração')
            ->call('resetStudentPassword', $enrollment->id)
            ->assertHasErrors(['classroom'])
            ->assertSet('temporaryPassword', null);

        $this->assertFalse($enrollment->user->refresh()->must_change_password);

        Livewire::actingAs(User::factory()->admin()->create())->test(Show::class, ['classroom' => $classroom])
            ->call('resetStudentPassword', $enrollment->id)
            ->assertHasNoErrors();

        $this->assertTrue($enrollment->user->refresh()->must_change_password);
    }

    public function test_former_classrooms_of_other_teachers_do_not_block_the_reset(): void
    {
        $enrollment = Enrollment::factory()->create();
        Enrollment::factory()->removed()->for($enrollment->user)->create();

        Livewire::actingAs($enrollment->classroom->teacher)->test(Show::class, ['classroom' => $enrollment->classroom])
            ->call('resetStudentPassword', $enrollment->id)
            ->assertHasNoErrors();

        $this->assertTrue($enrollment->user->refresh()->must_change_password);
    }

    public function test_admin_cannot_issue_a_temporary_password_to_themselves(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'senha-do-admin-123']);

        Livewire::actingAs($admin)->test(UserShow::class, ['user' => $admin])
            ->assertDontSee('Gerar senha temporária')
            ->call('issueTemporaryPassword')
            ->assertHasErrors(['user'])
            ->assertSet('temporaryPassword', null);

        $this->assertTrue(Hash::check('senha-do-admin-123', $admin->refresh()->password));
    }

    public function test_changing_the_password_signs_out_the_other_devices(): void
    {
        $user = User::factory()->create(['password' => 'senha-atual-123', 'remember_token' => 'token-antigo']);
        DB::table('sessions')->insert([
            ['id' => 'outro-aparelho', 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp],
            ['id' => session()->getId(), 'user_id' => $user->id, 'payload' => '', 'last_activity' => now()->timestamp],
        ]);

        Livewire::actingAs($user)->test('account.change-password')
            ->set('current_password', 'senha-atual-123')
            ->set('password', 'minha-senha-nova-123')
            ->set('password_confirmation', 'minha-senha-nova-123')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('sessions', ['id' => 'outro-aparelho']);
        $this->assertDatabaseHas('sessions', ['id' => session()->getId()]);
        $this->assertNotSame('token-antigo', $user->refresh()->remember_token);
    }

    public function test_admin_sends_a_reset_link(): void
    {
        Notification::fake();
        $student = User::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())->test(UserShow::class, ['user' => $student])
            ->call('sendResetLink');

        Notification::assertSentTo($student, ResetPassword::class);
    }

    public function test_admin_deletes_a_student_and_the_action_stays_in_the_audit(): void
    {
        $enrollment = Enrollment::factory()->create();
        $student = $enrollment->user;

        Livewire::actingAs(User::factory()->admin()->create())->test(UserShow::class, ['user' => $student])
            ->call('deleteUser')
            ->assertRedirect(route('admin.users'));

        $this->assertModelMissing($student);
        $this->assertModelMissing($enrollment);
        $this->assertDatabaseHas('audit_logs', ['action' => 'user.deleted']);
    }

    public function test_admin_cannot_delete_themselves_or_a_teacher_with_classrooms(): void
    {
        $admin = User::factory()->admin()->create();
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($admin)->test(UserShow::class, ['user' => $admin])
            ->call('deleteUser')
            ->assertSee('Você não pode excluir a própria conta.');

        Livewire::actingAs($admin)->test(UserShow::class, ['user' => $classroom->teacher])
            ->call('deleteUser')
            ->assertSee('Esta conta é responsável por turmas e não pode ser excluída.');

        $this->assertModelExists($admin);
        $this->assertModelExists($classroom->teacher);
    }

    public function test_only_admin_opens_the_user_page(): void
    {
        $student = User::factory()->create();

        $this->actingAs(User::factory()->teacher()->create())
            ->get(route('admin.users.show', $student))
            ->assertForbidden();
    }

    public function test_list_filters_accounts_that_never_accessed(): void
    {
        User::factory()->create(['name' => 'Nunca Veio']);
        User::factory()->create(['name' => 'Veio Hoje', 'last_seen_at' => now()]);

        Livewire::actingAs(User::factory()->admin()->create())->test(Users::class)
            ->set('neverAccessed', true)
            ->assertSee('Nunca Veio')
            ->assertDontSee('Veio Hoje');
    }
}
