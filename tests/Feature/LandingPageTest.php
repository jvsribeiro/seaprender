<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_shows_the_registration_form(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Toda aula é uma', false);
        $response->assertSee('lang="pt-BR"', false);
        $response->assertSee('name="password_confirmation"', false);
    }

    public function test_login_page_shows_the_login_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('autocomplete="current-password"', false);
        $response->assertDontSee('name="password_confirmation"', false);
    }

    public function test_landing_page_does_not_use_third_party_brands(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Senac');
        $response->assertDontSee('Kahoot');
    }

    public function test_pages_send_security_headers_and_the_official_logo(): void
    {
        $this->get('/')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertSee('images/logo.png');
    }
}
