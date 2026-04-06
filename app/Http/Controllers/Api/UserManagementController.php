<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pgsql\User;
use App\Models\Pgsql\Role;
use App\Models\Pgsql\UserAttribute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    protected ?User $createdUser = null;
    /**
     * Daftar semua user (paginasi).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with(['roles'])
            ->when($request->search, fn($q, $s) =>
                $q->where('name', 'ilike', "%{$s}%")
                  ->orWhere('username', 'ilike', "%{$s}%")
            )
            ->when($request->divisi, fn($q, $d) => $q->where('divisi', $d))
            ->when($request->status, fn($q, $s) =>
                $q->where('is_active', $s === 'aktif')
            )
            ->when($request->role, fn($q, $r) =>
                $q->whereHas('roles', fn($rq) => $rq->where('name', $r))
            )
            ->orderBy('name');

        $users = $query->paginate($request->per_page ?? 15);

        return response()->json([
            'data' => $users->map(fn($u) => $this->formatUser($u)),
            'meta' => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    /**
     * Detail user.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['roles.permissions', 'userAttributes']);

        return response()->json([
            'user'       => $this->formatUser($user),
            'attributes' => $user->userAttributes->pluck('attribute_value', 'attribute_key'),
            'roles'      => $user->roles->map(fn($r) => [
                'id'           => $r->id,
                'name'         => $r->name,
                'display_name' => $r->display_name,
                'color'        => $r->color,
                'pivot'        => [
                    'area_kebun' => $r->pivot->area_kebun,
                    'divisi'     => $r->pivot->divisi,
                    'assigned_at'=> $r->pivot->assigned_at,
                ],
            ]),
        ]);
    }

    /**
     * Tambah user baru — hanya Administrator.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username'   => ['required', 'string', 'max:50', 'unique:pgsql.users,username', 'regex:/^[a-zA-Z0-9._]+$/'],
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:100', 'unique:pgsql.users,email'],
            'password'   => ['required', Password::min(8)->mixedCase()->numbers()],
            'jabatan'    => ['nullable', 'string', 'max:100'],
            'divisi'     => ['nullable', 'string', 'max:100'],
            'area_kebun' => ['nullable', 'string', 'max:50'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'is_active'  => ['boolean'],
            'roles'      => ['required', 'array', 'min:1'],
            'roles.*'    => ['exists:pgsql.roles,id'],
            'attributes' => ['nullable', 'array'],
        ], [
            'username.unique'  => 'Username sudah digunakan.',
            'username.regex'   => 'Username hanya boleh huruf, angka, titik, dan underscore.',
            'email.unique'     => 'Email sudah digunakan.',
            'roles.required'   => 'Minimal satu role harus dipilih.',
        ]);

        DB::connection('pgsql')->transaction(function () use ($validated, $request) {
            $user = User::create([
                'username'   => $validated['username'],
                'name'       => $validated['name'],
                'email'      => $validated['email'] ?? null,
                'password'   => Hash::make($validated['password']),
                'jabatan'    => $validated['jabatan'] ?? null,
                'divisi'     => $validated['divisi'] ?? null,
                'area_kebun' => $validated['area_kebun'] ?? null,
                'phone'      => $validated['phone'] ?? null,
                'is_active'  => $validated['is_active'] ?? true,
                'created_by' => $request->user()->id,
            ]);

            // Assign roles dengan konteks ABAC
            $rolesData = [];
            foreach ($validated['roles'] as $roleId) {
                $rolesData[$roleId] = [
                    'area_kebun'  => $validated['area_kebun'] ?? null,
                    'divisi'      => $validated['divisi'] ?? null,
                    'assigned_at' => now(),
                    'assigned_by' => $request->user()->id,
                ];
            }
            $user->roles()->sync($rolesData);

            // Simpan atribut ABAC
            if (!empty($validated['attributes'])) {
                foreach ($validated['attributes'] as $key => $value) {
                    if ($value !== null && $value !== '') {
                        UserAttribute::updateOrCreate(
                            ['user_id' => $user->id, 'attribute_key' => $key],
                            ['attribute_value' => $value]
                        );
                    }
                }
            }

            // Log
            DB::connection('pgsql')->table('activity_logs')->insert([
                'user_id'     => $request->user()->id,
                'action'      => 'create_user',
                'module'      => 'User',
                'description' => "Membuat user baru: {$user->username}",
                'new_values'  => json_encode(['username' => $user->username, 'name' => $user->name]),
                'ip_address'  => $request->ip(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $this->createdUser = $user;
        });

        return response()->json([
            'message' => 'User berhasil ditambahkan.',
            'user'    => $this->formatUser($this->createdUser->load('roles')),
        ], 201);
    }

    /**
     * Update data user.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'email'      => ['nullable', 'email', 'max:100', "unique:pgsql.users,email,{$user->id}"],
            'password'   => ['nullable', Password::min(8)->mixedCase()->numbers()],
            'jabatan'    => ['nullable', 'string', 'max:100'],
            'divisi'     => ['nullable', 'string', 'max:100'],
            'area_kebun' => ['nullable', 'string', 'max:50'],
            'phone'      => ['nullable', 'string', 'max:20'],
            'is_active'  => ['boolean'],
            'roles'      => ['required', 'array', 'min:1'],
            'roles.*'    => ['exists:pgsql.roles,id'],
            'attributes' => ['nullable', 'array'],
            'notes'      => ['nullable', 'string'],
        ]);

        // Cegah admin menghapus role dirinya sendiri jika satu-satunya admin
        if ($user->id === $request->user()->id && !in_array(
            Role::where('name', 'Administrator')->value('id'),
            $validated['roles']
        )) {
            return response()->json(['message' => 'Anda tidak bisa menghapus role Administrator dari akun sendiri.'], 422);
        }

        DB::connection('pgsql')->transaction(function () use ($validated, $user, $request) {
            $updateData = [
                'name'       => $validated['name'],
                'email'      => $validated['email'] ?? null,
                'jabatan'    => $validated['jabatan'] ?? null,
                'divisi'     => $validated['divisi'] ?? null,
                'area_kebun' => $validated['area_kebun'] ?? null,
                'phone'      => $validated['phone'] ?? null,
                'is_active'  => $validated['is_active'] ?? $user->is_active,
                'notes'      => $validated['notes'] ?? null,
            ];

            if (!empty($validated['password'])) {
                $updateData['password'] = Hash::make($validated['password']);
            }

            $user->update($updateData);

            // Sync roles
            $rolesData = [];
            foreach ($validated['roles'] as $roleId) {
                $rolesData[$roleId] = [
                    'area_kebun'  => $validated['area_kebun'] ?? null,
                    'divisi'      => $validated['divisi'] ?? null,
                    'assigned_at' => now(),
                    'assigned_by' => $request->user()->id,
                ];
            }
            $user->roles()->sync($rolesData);

            // Update atribut ABAC
            if (isset($validated['attributes'])) {
                foreach ($validated['attributes'] as $key => $value) {
                    if ($value !== null && $value !== '') {
                        UserAttribute::updateOrCreate(
                            ['user_id' => $user->id, 'attribute_key' => $key],
                            ['attribute_value' => $value]
                        );
                    } else {
                        UserAttribute::where('user_id', $user->id)
                            ->where('attribute_key', $key)
                            ->delete();
                    }
                }
            }

            DB::connection('pgsql')->table('activity_logs')->insert([
                'user_id'     => $request->user()->id,
                'action'      => 'update_user',
                'module'      => 'User',
                'description' => "Update user: {$user->username}",
                'ip_address'  => $request->ip(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });

        return response()->json([
            'message' => 'User berhasil diperbarui.',
            'user'    => $this->formatUser($user->fresh()->load('roles')),
        ]);
    }

    /**
     * Nonaktifkan user (soft delete).
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'Tidak bisa menghapus akun sendiri.'], 422);
        }

        $user->update(['is_active' => false]);
        $user->delete();

        DB::connection('pgsql')->table('activity_logs')->insert([
            'user_id'     => $request->user()->id,
            'action'      => 'delete_user',
            'module'      => 'User',
            'description' => "Hapus user: {$user->username}",
            'ip_address'  => $request->ip(),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        return response()->json(['message' => 'User berhasil dihapus.']);
    }

    /**
     * Daftar role yang tersedia (untuk dropdown form).
     */
    public function roles(): JsonResponse
    {
        $roles = Role::orderBy('name')->get(['id', 'name', 'display_name', 'description', 'color']);

        return response()->json(['data' => $roles]);
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
            'phone'        => $user->phone,
            'is_active'    => $user->is_active,
            'avatar_url'   => $user->avatar_url,
            'last_login_at'=> $user->last_login_at?->format('d M Y H:i'),
            'created_at'   => $user->created_at->format('d M Y'),
            'roles'        => $user->roles->map(fn($r) => [
                'id'           => $r->id,
                'name'         => $r->name,
                'display_name' => $r->display_name,
                'color'        => $r->color,
            ]),
        ];
    }
}
