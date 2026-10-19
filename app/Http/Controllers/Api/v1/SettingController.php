<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function show(): JsonResponse
    {
        $settings = Setting::instance();

        return response()->json($settings);
    }

    public function update(Request $request): JsonResponse
    {
        $settings = Setting::instance();

        // Optimistic locking check
        if ($request->has('version')) {
            if ((int) $request->version !== $settings->version) {
                return response()->json([
                    'error' => [
                        'code' => 'POS-060',
                        'message' => 'Konflik versi pengaturan (Settings version mismatch). Silakan muat ulang dan coba lagi.',
                    ],
                ], 409);
            }
        }

        $validated = $request->validate([
            'store_name' => 'sometimes|string|max:255',
            'store_address' => 'sometimes|nullable|string',
            'store_phone' => 'sometimes|nullable|string',
            'store_email' => 'sometimes|nullable|string',
            'receipt_footer' => 'sometimes|nullable|string',
            'receipt_show_cashier' => 'sometimes|boolean',
            'receipt_show_customer' => 'sometimes|boolean',
            'tax_enabled' => 'sometimes|boolean',
            'tax_rate_bps' => 'sometimes|integer|min:0|max:10000',
            'tax_name' => 'sometimes|string|max:50',
            'table_service_fee_enabled' => 'sometimes|boolean',
            'table_service_fee_cents' => 'sometimes|integer|min:0',
            'track_inventory' => 'sometimes|boolean',
            'low_stock_threshold' => 'sometimes|integer|min:0',
            'currency_code' => 'sometimes|string|max:10',
            'currency_symbol' => 'sometimes|string|max:10',
            'timezone' => 'sometimes|string|max:50',
            'theme_mode' => 'sometimes|string|in:Light,Dark',
            'accent_color' => 'sometimes|string|max:30',
        ]);

        $before = $settings->toArray();

        $validated['version'] = $settings->version + 1;
        $validated['updated_by'] = $request->user()?->id;

        $settings->update($validated);
        $after = $settings->fresh()->toArray();

        AuditService::log('settings.update', 'settings', (string) $settings->id, $before, $after);

        return response()->json($settings->fresh());
    }
}
