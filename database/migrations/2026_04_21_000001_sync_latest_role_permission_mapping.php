<?php

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('pgsql')->transaction(function () {
            $now = now();

            $permissionsData = [];
            foreach (PermissionEnum::cases() as $perm) {
                $permissionsData[] = [
                    'name' => $perm->value,
                    'display_name' => $perm->label(),
                    'group' => $perm->group(),
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::connection('pgsql')->table('permissions')->upsert(
                $permissionsData,
                ['name'],
                ['display_name', 'group', 'description', 'updated_at']
            );

            $roles = [
                [
                    'name' => 'Administrator',
                    'display_name' => 'Administrator',
                    'description' => 'Akses penuh ke seluruh sistem termasuk manajemen user.',
                    'is_system' => true,
                    'color' => 'red',
                ],
                [
                    'name' => 'Manajer',
                    'display_name' => 'Manajer',
                    'description' => 'Akses monitoring, approval, dan laporan. Tidak bisa kelola user.',
                    'is_system' => true,
                    'color' => 'purple',
                ],
                [
                    'name' => 'Supervisor',
                    'display_name' => 'Supervisor',
                    'description' => 'Akses input data, approval Lab QA, monitoring area.',
                    'is_system' => true,
                    'color' => 'blue',
                ],
                [
                    'name' => 'Operator',
                    'display_name' => 'Operator',
                    'description' => 'Akses input dan update data kebun, lab, penerimaan, dan operasional pabrik.',
                    'is_system' => false,
                    'color' => 'green',
                ],
                [
                    'name' => 'Viewer',
                    'display_name' => 'Viewer',
                    'description' => 'Akses baca saja (read-only) untuk semua modul.',
                    'is_system' => false,
                    'color' => 'gray',
                ],
            ];

            foreach ($roles as &$role) {
                $role['created_at'] = $now;
                $role['updated_at'] = $now;
            }
            unset($role);

            DB::connection('pgsql')->table('roles')->upsert(
                $roles,
                ['name'],
                ['display_name', 'description', 'is_system', 'color', 'updated_at']
            );

            $rolePermMap = [
                'Administrator' => PermissionEnum::adminPermissions(),
                'Manajer' => PermissionEnum::managerPermissions(),
                'Supervisor' => array_merge(
                    PermissionEnum::operatorPermissions(),
                    [PermissionEnum::LAB_QA_APPROVE->value]
                ),
                'Operator' => PermissionEnum::operatorPermissions(),
                'Viewer' => PermissionEnum::viewerPermissions(),
            ];

            foreach ($rolePermMap as $roleName => $permNames) {
                $roleId = DB::connection('pgsql')
                    ->table('roles')
                    ->where('name', $roleName)
                    ->value('id');

                if (!$roleId) {
                    continue;
                }

                $permIds = DB::connection('pgsql')
                    ->table('permissions')
                    ->whereIn('name', $permNames)
                    ->pluck('id');

                if ($permIds->isEmpty()) {
                    DB::connection('pgsql')->table('role_permission')
                        ->where('role_id', $roleId)
                        ->delete();
                    continue;
                }

                DB::connection('pgsql')->table('role_permission')
                    ->where('role_id', $roleId)
                    ->whereNotIn('permission_id', $permIds)
                    ->delete();

                $pivotData = $permIds->map(fn($pid) => [
                    'role_id' => $roleId,
                    'permission_id' => $pid,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->toArray();

                DB::connection('pgsql')->table('role_permission')->upsert(
                    $pivotData,
                    ['role_id', 'permission_id'],
                    ['updated_at']
                );
            }
        });
    }

    public function down(): void
    {
        // no-op: this migration normalizes ACL data to current application definitions.
    }
};
