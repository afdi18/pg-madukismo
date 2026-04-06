<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Pgsql\User;
use App\Services\AbacService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected AbacService $abacService) {}

    /**
     * Login dengan username + password.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'Username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        // Cari user berdasarkan username
        $user = User::where('username', $request->username)
                    ->with(['roles.permissions', 'userAttributes'])
                    ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'username' => ['Username atau password tidak sesuai.'],
            ]);
        }

        if (!$user->is_active) {
            throw ValidationException::withMessages([
                'username' => ['Akun Anda tidak aktif. Hubungi Administrator.'],
            ]);
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Buat Sanctum token
        $token = $user->createToken('madukismo-app')->plainTextToken;

        // Log aktivitas login
        DB::connection('pgsql')->table('activity_logs')->insert([
            'user_id'    => $user->id,
            'action'     => 'login',
            'module'     => 'Auth',
            'description'=> 'User berhasil login',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'token' => $token,
            'user'  => $this->formatUser($user),
            'access'=> $this->abacService->getUserAccessSummary($user),
        ]);
    }

    /**
     * Logout — revoke token.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::connection('pgsql')->table('activity_logs')->insert([
            'user_id'    => $user->id,
            'action'     => 'logout',
            'module'     => 'Auth',
            'description'=> 'User logout',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Berhasil logout.']);
    }

    /**
     * Kembalikan data user yang sedang login beserta akses ABAC.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions', 'userAttributes']);

        return response()->json([
            'user'  => $this->formatUser($user),
            'access'=> $this->abacService->getUserAccessSummary($user),
        ]);
    }

    protected function formatUser(User $user): array
    {
        return [
            'id'           => $user->id,
            'username'     => $user->username,
            'name'         => $user->name,
            'email'        => $user->email,
            'jabatan'      => $user->jabatan,
            'divisi'       => $user->divisi,
            'area_kebun'   => $user->area_kebun,
            'avatar_url'   => $user->avatar_url,
            'is_active'    => $user->is_active,
            'last_login_at'=> $user->last_login_at?->format('d M Y H:i'),
            'roles'        => $user->roles->map(fn($r) => [
                'name'         => $r->name,
                'display_name' => $r->display_name,
                'color'        => $r->color,
            ]),
        ];
    }
}
