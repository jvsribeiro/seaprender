<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private const STATUS = 'Se o e-mail estiver cadastrado, enviamos um link para criar uma nova senha.';

    public function test_login_page_links_to_password_recovery(): void
    {
        $this->get('/login')->assertSee('Esqueci a senha');
        $this->get(route('password.request'))->assertOk()->assertSee('Recuperar senha');
    }

    public function test_user_sets_a_new_password_from_the_emailed_link(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', self::STATUS);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]))
                ->assertOk()
                ->assertSee('Nova senha');

            $this->post(route('password.update'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'nova-senha-de-teste',
                'password_confirmation' => 'nova-senha-de-teste',
            ])->assertSessionHasNoErrors();

            return true;
        });

        $this->post('/login', ['email' => $user->email, 'password' => 'nova-senha-de-teste'])
            ->assertRedirect('/painel');
    }

    public function test_unknown_email_gets_the_same_answer(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'ninguem@example.test'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', self::STATUS);

        Notification::assertNothingSent();
    }

    public function test_invalid_token_is_refused(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.update'), [
            'token' => 'token-errado',
            'email' => $user->email,
            'password' => 'nova-senha-de-teste',
            'password_confirmation' => 'nova-senha-de-teste',
        ])->assertSessionHasErrors(['email' => 'Este link não é mais válido. Peça um novo.']);
    }
}
