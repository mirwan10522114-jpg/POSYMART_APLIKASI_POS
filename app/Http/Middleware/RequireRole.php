<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user() ?? $request->user('sanctum');

        if (!$user) {
            return response()->json([
                'error' => [
                    'code' => 'AUTH-001',
                    'message' => 'Token tidak valid atau belum diautentikasi',
                ],
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'error' => [
                    'code' => 'AUTH-007',
                    'message' => 'Akun dinonaktifkan',
                ],
            ], 401);
        }

        if (!empty($roles)) {
            if ($user->role === 'Owner') {
                return $next($request);
            }

            if (!in_array($user->role, $roles)) {
                return response()->json([
                    'error' => [
                        'code' => 'AUTH-003',
                        'message' => 'Role tidak diizinkan untuk aksi ini',
                    ],
                ], 403);
            }
        }

        return $next($request);
    }
}
