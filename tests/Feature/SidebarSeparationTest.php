<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SidebarSeparationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sidebar_separates_customers_and_staff(): void
    {
        $admin = User::factory()->create([
            'id' => 'admin-test-sidebar',
            'email' => 'admin@posymart.com',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        // 1. Visit /customers
        $custResponse = $this->actingAs($admin)->get('/customers');
        $custResponse->assertStatus(200);
        $custResponse->assertSee('Pelanggan Terdaftar');
        $custResponse->assertSee('/customers');
        $custResponse->assertSee('/users');
        $custResponse->assertSee('Staf &amp; Tim Toko', false);

        // 2. Visit /users
        $usersResponse = $this->actingAs($admin)->get('/users');
        $usersResponse->assertStatus(200);
        $usersResponse->assertSee('Staf &amp; Tim Toko', false);
        $usersResponse->assertSee('/customers');
        $usersResponse->assertSee('Pelanggan Terdaftar');
    }

    public function test_registered_customer_api_and_storage(): void
    {
        $admin = User::factory()->create([
            'id' => 'admin-test-cust-api',
            'email' => 'admin2@posymart.com',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        // Create a customer registered via QR
        $customer = Customer::create([
            'id' => 'CUS-TEST-001',
            'name' => 'Budi Santoso Pelanggan',
            'phone' => '081234567890',
            'email' => 'budi.santoso@example.com',
            'status' => 'Active',
            'notes' => 'Terdaftar via scan QR Meja',
            'joined_at' => now(),
        ]);

        // Check index API
        $response = $this->actingAs($admin)->getJson('/api/v1/customers');
        $response->assertStatus(200);
        $response->assertJsonFragment(['name' => 'Budi Santoso Pelanggan']);

        // Check show API
        $showResponse = $this->actingAs($admin)->getJson("/api/v1/customers/{$customer->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertJsonFragment(['id' => 'CUS-TEST-001']);
    }
}
