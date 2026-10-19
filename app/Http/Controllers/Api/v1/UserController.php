<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::whereNull('archived_at')->orderBy('name')->get();

        return response()->json($users);
    }

    public function show(string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Pengguna tidak ditemukan']], 404);
        }

        return response()->json($user);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:Owner,Admin,Manager,Cashier',
            'status' => 'nullable|in:Active,Invited,Disabled',
            'note' => 'nullable|string',
        ]);

        $id = 'user-' . Str::slug($validated['name']);
        if (User::where('id', $id)->exists()) {
            $id .= '-' . Str::random(4);
        }

        $user = User::create([
            'id' => $id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'status' => $validated['status'] ?? 'Active',
            'is_active' => ($validated['status'] ?? 'Active') === 'Active',
            'note' => $validated['note'] ?? null,
        ]);

        AuditService::log('user.create', 'user', $user->id, null, $user->toArray());

        return response()->json($user, 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Pengguna tidak ditemukan']], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,{$id}",
            'password' => 'sometimes|nullable|string|min:8',
            'role' => 'sometimes|in:Owner,Admin,Manager,Cashier',
            'status' => 'sometimes|in:Active,Invited,Disabled',
            'note' => 'sometimes|nullable|string',
        ]);

        // Guard: tidak boleh mengubah role Admin/Owner terakhir
        if (isset($validated['role']) && in_array($user->role, ['Admin', 'Owner']) && !in_array($validated['role'], ['Admin', 'Owner'])) {
            $adminCount = User::whereIn('role', ['Admin', 'Owner'])->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'error' => [
                        'code' => 'POS-080',
                        'message' => 'Tidak dapat mengubah peran Admin terakhir yang aktif.',
                    ],
                ], 409);
            }
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if (isset($validated['status'])) {
            $validated['is_active'] = $validated['status'] === 'Active';
        }

        $before = $user->toArray();
        $user->update($validated);
        $after = $user->fresh()->toArray();

        AuditService::log('user.update', 'user', $user->id, $before, $after);

        return response()->json($user);
    }

    public function deactivate(Request $request, string $id): JsonResponse
    {
        $targetUser = User::find($id);
        if (!$targetUser) {
            return response()->json(['error' => ['code' => 'POS-090', 'message' => 'Pengguna tidak ditemukan']], 404);
        }

        // Constraint 1: Tidak boleh menonaktifkan akun sendiri
        if ($targetUser->id === $request->user()->id) {
            return response()->json([
                'error' => [
                    'code' => 'POS-081',
                    'message' => 'Tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif.',
                ],
            ], 409);
        }

        // Constraint 2: Tidak boleh menonaktifkan Admin/Owner terakhir
        if (in_array($targetUser->role, ['Admin', 'Owner']) && $targetUser->is_active) {
            $adminCount = User::whereIn('role', ['Admin', 'Owner'])->where('is_active', true)->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'error' => [
                        'code' => 'POS-080',
                        'message' => 'Tidak dapat menonaktifkan Admin/Owner terakhir yang aktif.',
                    ],
                ], 409);
            }
        }

        $targetUser->update([
            'is_active' => false,
            'status' => 'Disabled',
        ]);

        AuditService::log('user.deactivate', 'user', $targetUser->id);

        return response()->json(['message' => 'Pengguna berhasil dinonaktifkan', 'user' => $targetUser]);
    }
}
