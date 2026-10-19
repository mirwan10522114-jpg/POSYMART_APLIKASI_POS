<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        // 1. Pengguna anonim diarahkan ke /login
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $this->get('/login')->assertStatus(200);

        // 2. Pengguna yang sudah login dapat mengakses dasbor
        $admin = \App\Models\User::factory()->create([
            'id' => 'user-test-admin',
            'email' => 'admin@posymart.com',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertStatus(200);
    }

    public function test_admin_can_login_and_logout(): void
    {
        $admin = \App\Models\User::factory()->create([
            'id' => 'user-test-admin-2',
            'name' => 'Admin Test',
            'email' => 'admin.test@posymart.com',
            'password' => \Illuminate\Support\Facades\Hash::make('rahasia123'),
            'role' => 'Admin',
            'is_active' => true,
        ]);

        // Login dengan kredensial yang salah -> Gagal
        $this->post('/login', [
            'email' => 'admin.test@posymart.com',
            'password' => 'passwordsalah',
        ])->assertSessionHasErrors(['email']);

        $this->assertGuest();

        // Login dengan kredensial yang benar -> Berhasil dan redirect ke dashboard
        $response = $this->post('/login', [
            'email' => 'admin.test@posymart.com',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($admin);

        // Logout -> Berhasil dan redirect ke login
        $this->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
