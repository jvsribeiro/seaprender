<?php

namespace Tests\Feature;

use App\Livewire\Admin\AuditLogs;
use App\Livewire\Classrooms\AccessReport;
use App\Models\AuditLog;
use App\Models\Enrollment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AccessTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_logins_and_wrong_passwords_are_recorded(): void
    {
        $user = User::factory()->create(['password' => 'senha-de-teste-123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'senha-errada']);
        $this->post('/login', ['email' => $user->email, 'password' => 'senha-de-teste-123']);

        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'auth.failed']);
        $this->assertDatabaseHas('audit_logs', ['actor_id' => $user->id, 'action' => 'auth.login']);
    }

    public function test_last_access_is_stored_without_writing_on_every_request(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'));

        $firstSeenAt = $user->refresh()->last_seen_at;
        $this->assertNotNull($firstSeenAt);

        $this->travel(2)->minutes();
        $this->actingAs($user->refresh())->get(route('dashboard'));
        $this->assertTrue($firstSeenAt->equalTo($user->refresh()->last_seen_at));

        $this->travel(4)->minutes();
        $this->actingAs($user->refresh())->get(route('dashboard'));
        $this->assertTrue($user->refresh()->last_seen_at->gt($firstSeenAt));
    }

    public function test_opening_a_classroom_is_recorded_once_per_hour(): void
    {
        $enrollment = Enrollment::factory()->create();

        $this->actingAs($enrollment->user);
        $this->get(route('classrooms.show', $enrollment->classroom_id))->assertOk();
        $this->get(route('classrooms.show', $enrollment->classroom_id))->assertOk();

        $this->assertSame(1, AuditLog::where('action', 'classroom.viewed')->count());
    }

    public function test_report_shows_who_never_accessed_and_what_each_student_saw(): void
    {
        $post = Post::factory()->create();
        $classroom = $post->classroom;
        $reader = Enrollment::factory()->for($classroom)->for(User::factory()->state(['name' => 'Quem Leu', 'last_seen_at' => now()]))->create()->user;
        Enrollment::factory()->for($classroom)->for(User::factory()->state(['name' => 'Quem Faltou']))->create();

        DB::table('post_views')->insert(['post_id' => $post->id, 'user_id' => $reader->id]);

        Livewire::actingAs($classroom->teacher)->test(AccessReport::class, ['classroom' => $classroom])
            ->assertSeeInOrder(['Quem Faltou', 'Nunca acessou', '0 de 1', 'Quem Leu', '1 de 1'])
            ->set('onlyMissing', true)
            ->assertSee('Quem Faltou')
            ->assertDontSee('Quem Leu');
    }

    public function test_report_is_only_for_who_manages_the_classroom(): void
    {
        $enrollment = Enrollment::factory()->create();

        Livewire::actingAs($enrollment->user)->test(AccessReport::class, ['classroom' => $enrollment->classroom])
            ->assertForbidden();

        Livewire::actingAs(User::factory()->teacher()->create())->test(AccessReport::class, ['classroom' => $enrollment->classroom])
            ->assertNotFound();
    }

    public function test_student_cannot_force_the_private_tabs(): void
    {
        $enrollment = Enrollment::factory()->create();
        $classmate = Enrollment::factory()->for($enrollment->classroom)->create()->user;

        $this->actingAs($enrollment->user)
            ->get(route('classrooms.show', [$enrollment->classroom_id, 'aba' => 'alunos']))
            ->assertOk()
            ->assertDontSee($classmate->email);

        $this->get(route('classrooms.show', [$enrollment->classroom_id, 'aba' => 'acessos']))
            ->assertOk()
            ->assertDontSee('Acessos da turma');
    }

    public function test_audit_filters_by_person_and_action(): void
    {
        $ana = User::factory()->create(['name' => 'Ana Audit']);
        $caio = User::factory()->create(['name' => 'Caio Audit']);
        AuditLog::create(['actor_id' => $ana->id, 'action' => 'auth.login']);
        AuditLog::create(['actor_id' => $caio->id, 'action' => 'auth.failed']);

        Livewire::actingAs(User::factory()->admin()->create())->test(AuditLogs::class)
            ->set('person', 'ana')
            ->assertSee('Ana Audit')
            ->assertDontSee('Caio Audit')
            ->set('person', '')
            ->set('action', 'auth.failed')
            ->assertSee('Caio Audit')
            ->assertDontSee('Ana Audit');
    }
}
