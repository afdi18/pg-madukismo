<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $conn = DB::connection('pgsql');
        $now = now();

        $conn->transaction(function () use ($conn, $now) {
            $permissionName = 'dashboard.pengawasan_qa.view';

            $existingPermissionId = $conn->table('permissions')
                ->where('name', $permissionName)
                ->value('id');

            if (!$existingPermissionId) {
                $existingPermissionId = $conn->table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'display_name' => 'Akses Dashboard Angka Pengawasan QA',
                    'group' => 'Dashboard',
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            } else {
                $conn->table('permissions')
                    ->where('id', $existingPermissionId)
                    ->update([
                        'display_name' => 'Akses Dashboard Angka Pengawasan QA',
                        'group' => 'Dashboard',
                        'updated_at' => $now,
                    ]);
            }

            $conn->table('permissions')
                ->where('name', 'dashboard.view')
                ->update([
                    'display_name' => 'Akses Dashboard (Informasi Tebu)',
                    'group' => 'Dashboard',
                    'updated_at' => $now,
                ]);

            $labQaViewId = $conn->table('permissions')
                ->where('name', 'lab_qa.view')
                ->value('id');

            if ($labQaViewId) {
                $roleIds = $conn->table('role_permission')
                    ->where('permission_id', $labQaViewId)
                    ->pluck('role_id')
                    ->all();

                if (!empty($roleIds)) {
                    $rows = array_map(static fn ($roleId) => [
                        'role_id' => $roleId,
                        'permission_id' => $existingPermissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ], $roleIds);

                    $conn->table('role_permission')->upsert(
                        $rows,
                        ['role_id', 'permission_id'],
                        ['updated_at']
                    );
                }
            }
        });
    }

    public function down(): void
    {
        $conn = DB::connection('pgsql');
        $now = now();

        $conn->transaction(function () use ($conn, $now) {
            $permissionId = $conn->table('permissions')
                ->where('name', 'dashboard.pengawasan_qa.view')
                ->value('id');

            if ($permissionId) {
                $conn->table('role_permission')
                    ->where('permission_id', $permissionId)
                    ->delete();

                $conn->table('permissions')
                    ->where('id', $permissionId)
                    ->delete();
            }

            $conn->table('permissions')
                ->where('name', 'dashboard.view')
                ->update([
                    'display_name' => 'Akses Dashboard (Informasi Tebu, Monitoring Pabrik, Angka Pengawasan QA)',
                    'group' => 'Dashboard',
                    'updated_at' => $now,
                ]);
        });
    }
};
