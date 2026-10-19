<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Str;

class AuditService
{
    public static function log(
        string $action,
        string $targetType,
        ?string $targetId,
        ?array $beforeData = null,
        ?array $afterData = null,
        ?string $userId = null
    ): AuditLog {
        $user = auth()->user();
        $actualUserId = $userId ?? $user?->id;

        return AuditLog::create([
            'user_id' => $actualUserId,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'before_data' => $beforeData,
            'after_data' => $afterData,
            'trace_id' => (string) Str::uuid(),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
