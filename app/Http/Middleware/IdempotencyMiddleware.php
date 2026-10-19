<?php

namespace App\Http\Middleware;

use App\Models\IdempotencyKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (!$key) {
            return $next($request);
        }

        $userId = $request->user()?->id;
        $requestHash = hash('sha256', $request->getContent() ?: json_encode($request->all()));

        $existing = IdempotencyKey::where('key', $key)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->first();

        if ($existing) {
            if ($existing->expires_at->isPast()) {
                $existing->delete();
            } elseif ($existing->request_hash !== $requestHash) {
                return response()->json([
                    'error' => [
                        'code' => 'POS-050',
                        'message' => 'Idempotency-Key sudah digunakan dengan payload berbeda',
                    ],
                ], 409);
            } else {
                return response()->json($existing->response_body, $existing->response_status);
            }
        }

        $response = $next($request);

        // Hanya simpan jika response sukses (2xx)
        if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
            $content = json_decode($response->getContent(), true) ?: [];
            IdempotencyKey::updateOrCreate(
                ['key' => $key],
                [
                    'user_id' => $userId,
                    'request_hash' => $requestHash,
                    'response_status' => $response->getStatusCode(),
                    'response_body' => $content,
                    'expires_at' => now()->addHours(24),
                ]
            );
        }

        return $response;
    }
}
