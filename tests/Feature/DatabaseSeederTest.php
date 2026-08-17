<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_first_tenant_and_attaches_the_test_user_as_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $tenant = Tenant::query()->first();
        $user = User::query()->where('email', 'test@example.com')->first();

        $this->assertNotNull($tenant);
        $this->assertNotNull($user);
        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame('admin', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('password1@1@', $user->password));
    }
}
