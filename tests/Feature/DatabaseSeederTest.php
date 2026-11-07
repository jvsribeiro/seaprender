<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_is_created_once(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(1, User::where('role', UserRole::Admin)->count());
        $this->assertSame(2, User::where('role', UserRole::Teacher)->count());
        $this->assertSame(15, User::where('role', UserRole::Student)->count());
        $this->assertSame(2, Classroom::count());
        $this->assertSame(15, Enrollment::count());
        $this->assertSame(0, User::whereNot('email', 'like', '%@example.test')->count());
    }

    public function test_demo_data_is_refused_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('db:seed', ['--force' => true]);

        $this->assertDatabaseCount('users', 0);
    }
}
