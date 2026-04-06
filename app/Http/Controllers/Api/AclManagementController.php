<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pgsql\Role;
use App\Models\Pgsql\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AclManagementController extends Controller
{
    public function roles(): JsonResponse
    {
        $roles = Role::with(['permissions:id,name,display_name,group'])
            ->orderByDesc('is_system')
            ->orderBy('display_name')
            ->get();

        return response()->json([
            'data' => $roles->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $role->display_name,
                'description' => $role->description,
                'color' => $role->color,
                'is_system' => $role->is_system,
                'permissions' => $role->permissions->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'display_name' => $permission->display_name,
                    'group' => $permission->group,
                ])->values(),
            ])->values(),
        ]);
    }

    public function permissionGroups(): JsonResponse
    {
        $permissions = RolePermission::query()
            ->orderBy('group')
            ->orderBy('display_name')
            ->get(['id', 'name', 'display_name', 'group']);

        $grouped = $permissions
            ->groupBy('group')
            ->map(fn ($items) => $items->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
            ])->values())
            ->sortKeys();

        return response()->json([
            'data' => $grouped,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('pgsql.roles', 'name')],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', Rule::in(['red', 'purple', 'blue', 'green', 'gray'])],
            'make_admin' => ['boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('pgsql.permissions', 'id')],
        ], [
            'name.regex' => 'Nama ACL hanya boleh huruf, angka, titik, garis bawah, dan dash.',
            'name.unique' => 'Nama ACL sudah digunakan.',
        ]);

        $role = DB::connection('pgsql')->transaction(function () use ($validated, $request) {
            $role = Role::create([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? 'blue',
                'is_system' => false,
            ]);

            $permissionIds = $this->resolvePermissionIds($validated);
            $role->permissions()->sync($permissionIds);

            DB::connection('pgsql')->table('activity_logs')->insert([
                'user_id' => $request->user()?->id,
                'action' => 'create_acl',
                'module' => 'ACL',
                'description' => "Membuat ACL: {$role->display_name}",
                'new_values' => json_encode([
                    'role_id' => $role->id,
                    'permissions_count' => count($permissionIds),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $role;
        });

        return response()->json([
            'message' => 'ACL berhasil dibuat.',
            'data' => $this->formatRole($role->load('permissions:id,name,display_name,group')),
        ], 201);
    }

    public function update(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json([
                'message' => 'ACL sistem tidak dapat diubah.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9_.-]+$/', Rule::unique('pgsql.roles', 'name')->ignore($role->id)],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'color' => ['nullable', 'string', Rule::in(['red', 'purple', 'blue', 'green', 'gray'])],
            'make_admin' => ['boolean'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', Rule::exists('pgsql.permissions', 'id')],
        ], [
            'name.regex' => 'Nama ACL hanya boleh huruf, angka, titik, garis bawah, dan dash.',
            'name.unique' => 'Nama ACL sudah digunakan.',
        ]);

        $role = DB::connection('pgsql')->transaction(function () use ($validated, $request, $role) {
            $role->update([
                'name' => $validated['name'],
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
                'color' => $validated['color'] ?? 'blue',
            ]);

            $permissionIds = $this->resolvePermissionIds($validated);
            $role->permissions()->sync($permissionIds);

            DB::connection('pgsql')->table('activity_logs')->insert([
                'user_id' => $request->user()?->id,
                'action' => 'update_acl',
                'module' => 'ACL',
                'description' => "Update ACL: {$role->display_name}",
                'new_values' => json_encode([
                    'role_id' => $role->id,
                    'permissions_count' => count($permissionIds),
                ]),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $role;
        });

        return response()->json([
            'message' => 'ACL berhasil diperbarui.',
            'data' => $this->formatRole($role->load('permissions:id,name,display_name,group')),
        ]);
    }

    public function destroy(Request $request, Role $role): JsonResponse
    {
        if ($role->is_system) {
            return response()->json([
                'message' => 'ACL sistem tidak dapat dihapus.',
            ], 422);
        }

        $userCount = DB::connection('pgsql')
            ->table('role_user')
            ->where('role_id', $role->id)
            ->count();

        if ($userCount > 0) {
            return response()->json([
                'message' => "ACL tidak dapat dihapus karena masih digunakan oleh {$userCount} user.",
            ], 422);
        }

        DB::connection('pgsql')->transaction(function () use ($role, $request) {
            $roleName = $role->display_name;
            $role->permissions()->detach();
            $role->delete();

            DB::connection('pgsql')->table('activity_logs')->insert([
                'user_id'     => $request->user()?->id,
                'action'      => 'delete_acl',
                'module'      => 'ACL',
                'description' => "Hapus ACL: {$roleName}",
                'new_values'  => null,
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });

        return response()->json([
            'message' => 'ACL berhasil dihapus.',
        ]);
    }

    protected function resolvePermissionIds(array $validated): array
    {
        if (($validated['make_admin'] ?? false) === true) {
            return RolePermission::query()->pluck('id')->toArray();
        }

        return array_values(array_unique($validated['permission_ids'] ?? []));
    }

    protected function formatRole(Role $role): array
    {
        return [
            'id' => $role->id,
            'name' => $role->name,
            'display_name' => $role->display_name,
            'description' => $role->description,
            'color' => $role->color,
            'is_system' => $role->is_system,
            'permissions' => $role->permissions->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'display_name' => $permission->display_name,
                'group' => $permission->group,
            ])->values(),
        ];
    }
}
