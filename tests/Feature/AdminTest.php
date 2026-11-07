<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Users;
use App\Livewire\Classrooms\Show;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_turns_a_student_into_a_teacher(): void
    {
        $admin = User::factory()->admin()->create();
        $student = User::factory()->create();

        Livewire::actingAs($admin)->test(Users::class)
            ->call('changeRole', $student->id, UserRole::Teacher->value)
            ->assertHasNoErrors();

        $this->assertSame(UserRole::Teacher, $student->refresh()->role);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $admin->id, 'action' => 'user.role_changed']);
    }

    public function test_admin_cannot_change_their_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        Livewire::actingAs($admin)->test(Users::class)
            ->call('changeRole', $admin->id, UserRole::Student->value)
            ->assertHasErrors(['role'])
            ->assertSee('Você não pode alterar o próprio perfil.');

        $this->assertSame(UserRole::Admin, $admin->refresh()->role);
    }

    public function test_unknown_role_is_refused(): void
    {
        $student = User::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())->test(Users::class)
            ->call('changeRole', $student->id, 'superuser')
            ->assertHasErrors(['role']);

        $this->assertSame(UserRole::Student, $student->refresh()->role);
    }

    public function test_teacher_and_student_cannot_reach_admin_pages(): void
    {
        foreach ([User::factory()->teacher()->create(), User::factory()->create()] as $user) {
            $this->actingAs($user);

            $this->get(route('admin.users'))->assertForbidden();
            $this->get(route('admin.audit'))->assertForbidden();
        }
    }

    public function test_audit_page_translates_actions(): void
    {
        $classroom = Classroom::factory()->create();

        Livewire::actingAs($classroom->teacher)
            ->test(Show::class, ['classroom' => $classroom])
            ->call('regenerateCode');

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.audit'))
            ->assertOk()
            ->assertSee('Código da turma trocado')
            ->assertSee($classroom->teacher->name);
    }

    public function test_command_promotes_the_first_teacher(): void
    {
        $user = User::factory()->create();

        $this->artisan('app:set-user-role', ['email' => $user->email, 'role' => 'teacher'])->assertSuccessful();

        $this->assertSame(UserRole::Teacher, $user->refresh()->role);
    }
}
