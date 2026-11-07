<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_can_register_and_reach_the_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ana Souza',
            'email' => 'ana@example.com',
            'password' => 'senha-de-teste-123',
            'password_confirmation' => 'senha-de-teste-123',
        ]);

        $response->assertRedirect('/painel');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'ana@example.com']);

        $this->get('/painel')->assertOk()->assertSee('Olá, Ana!');
    }

    public function test_registration_errors_are_shown_in_portuguese(): void
    {
        User::factory()->create(['email' => 'ana@example.com']);

        $response = $this->from('/')->post('/register', [
            'name' => '',
            'email' => 'ana@example.com',
            'password' => 'curta',
            'password_confirmation' => 'diferente',
        ]);

        $response->assertRedirect('/');
        $response->assertSessionHasErrors([
            'name' => 'Preencha o campo nome.',
            'email' => 'Este e-mail já está cadastrado.',
        ]);
        $this->assertGuest();
    }

    public function test_user_can_log_in_and_log_out(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/painel');
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect('/');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->from('/login')->post('/login', ['email' => $user->email, 'password' => 'errada']);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'E-mail ou senha incorretos.']);
        $this->assertGuest();
    }

    public function test_login_is_limited_after_repeated_failures(): void
    {
        $user = User::factory()->create();

        foreach (range(1, 5) as $attempt) {
            $this->post('/login', ['email' => $user->email, 'password' => 'errada']);
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'errada'])->assertTooManyRequests();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get('/painel')->assertRedirect('/login');
    }

    public function test_logged_in_user_skips_the_landing_page(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/')->assertRedirect('/painel');
    }
}
