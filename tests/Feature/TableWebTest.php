<?php

namespace Tests\Feature;

use App\Models\Table;
use App\Models\TableQr;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_tables_page_with_50_tables(): void
    {
        $admin = User::factory()->create([
            'id' => 'user-admin-tbl',
            'role' => 'Admin',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 50; $i++) {
            $num = str_pad((string)$i, 3, '0', STR_PAD_LEFT);
            $num2 = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $t = Table::create([
                'id' => "T{$num}",
                'table_code' => "T{$num}",
                'label' => "Meja {$num2}",
                'area' => 'Indoor Utama',
                'is_active' => true,
            ]);
            TableQr::create([
                'id' => "QR-T{$num}",
                'table_id' => $t->id,
                'public_token' => "qr-meja-{$num2}",
                'status' => 'Active',
                'version' => 1,
                'scan_count' => 0,
            ]);
        }

        $response = $this->actingAs($admin)->get('/tables');

        $response->assertStatus(200);
        $response->assertSee('Meja & QR');
        $response->assertSee('T001');
        $response->assertSee('T050');
        $response->assertSee('qr-meja-01');
        $response->assertSee('qr-meja-50');

        // Verify web session can call API
        $apiResponse = $this->actingAs($admin)->getJson('/api/v1/admin/tables');
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonCount(50, 'data');
    }
}
