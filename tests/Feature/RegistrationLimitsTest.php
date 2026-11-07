<?php

namespace Tests\Feature;

use App\Actions\Fortify\CreateNewUser;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RegistrationLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_cannot_choose_a_role(): void
    {
        $this->post('/register', [
            'name' => 'Ana Souza',
            'email' => 'ana@example.test',
            'password' => 'senha-de-teste-123',
            'password_confirmation' => 'senha-de-teste-123',
            'role' => 'admin',
        ]);

        $this->assertSame(UserRole::Student, User::sole()->role);
    }

    public function test_registrations_from_one_network_are_limited(): void
    {
        $createNewUser = app(CreateNewUser::class);
        $attributes = fn (int $number) => [
            'name' => "Aluno {$number}",
            'email' => "aluno{$number}@example.test",
            'password' => 'senha-de-teste-123',
            'password_confirmation' => 'senha-de-teste-123',
        ];

        foreach (range(1, 60) as $number) {
            $createNewUser->create($attributes($number));
        }

        try {
            $createNewUser->create($attributes(61));
            $this->fail('O limite de cadastros não foi aplicado.');
        } catch (ValidationException $exception) {
            $this->assertSame('Muitos cadastros nesta rede. Aguarde um minuto.', $exception->errors()['email'][0]);
        }

        $this->assertDatabaseCount('users', 60);
    }
}
