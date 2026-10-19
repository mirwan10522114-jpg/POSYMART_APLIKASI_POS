<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Table;
use App\Models\TableQr;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminTableController extends Controller
{
    /**
     * List all tables with active QRs.
     * GET /api/v1/admin/tables
     */
    public function index(Request $request): JsonResponse
    {
        $query = Table::with(['qrs' => fn ($q) => $q->latest()]);

        if ($request->filled('q')) {
            $q = $request->q;
            $query->where(function ($w) use ($q) {
                $w->where('label', 'like', "%{$q}%")
                    ->orWhere('table_code', 'like', "%{$q}%")
                    ->orWhere('area', 'like', "%{$q}%");
            });
        }

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        $tables = $query->orderByRaw("CAST(SUBSTR(table_code, 2) AS INTEGER) ASC")->get();

        $formatted = $tables->map(function ($t) {
            $activeQr = $t->qrs->where('status', 'Active')->first() ?: $t->qrs->first();
            return [
                'id' => $t->id,
                'table_code' => $t->table_code,
                'label' => $t->label,
                'area' => $t->area,
                'is_active' => $t->is_active,
                'qr_id' => $activeQr?->id,
                'public_token' => $activeQr?->public_token,
                'qr_status' => $activeQr?->status ?? 'Draft',
                'version' => $activeQr?->version ?? 1,
                'scan_count' => $activeQr?->scan_count ?? 0,
                'last_printed_at' => $activeQr?->last_printed_at,
                'qr_url' => $activeQr ? url('/m/' . $activeQr->public_token) : null,
            ];
        });

        return response()->json([
            'data' => $formatted,
            'total' => $tables->count(),
        ]);
    }

    /**
     * Create a new table (e.g. Meja 51+)
     * POST /api/v1/admin/tables
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'label' => 'required|string|max:50',
            'area' => 'nullable|string|max:50',
        ]);

        $nextNum = Table::count() + 1;
        $tableCode = 'T' . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
        $qrId = 'QR-' . $tableCode;
        $publicToken = 'tok_' . Str::lower(Str::random(16));

        $table = Table::create([
            'id' => $tableCode,
            'table_code' => $tableCode,
            'label' => $request->label,
            'area' => $request->area ?: 'Indoor',
            'is_active' => true,
        ]);

        $qr = TableQr::create([
            'id' => $qrId,
            'table_id' => $table->id,
            'public_token' => $publicToken,
            'status' => 'Active',
            'version' => 1,
            'scan_count' => 0,
        ]);

        AuditService::log('table.create', 'table', $table->id, null, $table->toArray());

        return response()->json([
            'table' => $table,
            'qr' => $qr,
        ], 201);
    }

    /**
     * Update table details.
     * PATCH /api/v1/admin/tables/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $table = Table::find($id);
        if (!$table) {
            return response()->json(['error' => ['code' => 'TABLE_NOT_FOUND', 'message' => 'Meja tidak ditemukan']], 404);
        }

        $validated = $request->validate([
            'label' => 'sometimes|string|max:50',
            'area' => 'sometimes|nullable|string|max:50',
            'is_active' => 'sometimes|boolean',
        ]);

        $before = $table->toArray();
        $table->update($validated);

        AuditService::log('table.update', 'table', $table->id, $before, $table->fresh()->toArray());

        return response()->json($table->fresh());
    }

    /**
     * Rotate QR token (revoking old token and issuing new one).
     * POST /api/v1/admin/table-qrs/{id}/rotate
     */
    public function rotateQr(Request $request, string $id): JsonResponse
    {
        $table = Table::with('qrs')->find($id);
        if (!$table) {
            return response()->json(['error' => ['code' => 'TABLE_NOT_FOUND', 'message' => 'Meja tidak ditemukan']], 404);
        }

        $latestQr = $table->qrs->first();
        $nextVersion = $latestQr ? $latestQr->version + 1 : 1;

        // Revoke old active QRs
        TableQr::where('table_id', $table->id)->where('status', 'Active')->update(['status' => 'Revoked']);

        $newQrId = 'QR-' . $table->table_code . '-v' . $nextVersion;
        $newToken = 'tok_' . Str::lower(Str::random(16));

        $newQr = TableQr::create([
            'id' => $newQrId,
            'table_id' => $table->id,
            'public_token' => $newToken,
            'status' => 'Active',
            'version' => $nextVersion,
            'scan_count' => 0,
        ]);

        AuditService::log('table_qr.rotate', 'table', $table->id, null, [
            'new_qr_id' => $newQr->id,
            'version' => $nextVersion,
        ]);

        return response()->json([
            'message' => "QR untuk {$table->label} berhasil dirotasi ke versi {$nextVersion}.",
            'table' => $table,
            'qr' => $newQr,
            'qr_url' => url('/m/' . $newQr->public_token),
        ]);
    }

    /**
     * Enable or disable QR.
     * POST /api/v1/admin/table-qrs/{id}/toggle
     */
    public function toggleQr(Request $request, string $id): JsonResponse
    {
        $qr = TableQr::find($id);
        if (!$qr) {
            // Check if passed table id
            $qr = TableQr::where('table_id', $id)->latest()->first();
        }

        if (!$qr) {
            return response()->json(['error' => ['code' => 'QR_NOT_FOUND', 'message' => 'QR tidak ditemukan']], 404);
        }

        $newStatus = $qr->status === 'Active' ? 'Inactive' : 'Active';
        $qr->update(['status' => $newStatus]);

        return response()->json([
            'qr_id' => $qr->id,
            'status' => $qr->status,
            'message' => "Status QR berhasil diubah menjadi {$qr->status}",
        ]);
    }
}
