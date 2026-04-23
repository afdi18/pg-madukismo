<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $permissions = [
            [
                'name' => 'penerimaan.spa.view',
                'display_name' => 'Akses Penerimaan Tebu - Monitoring SPA',
                'group' => 'Penerimaan Tebu',
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'penerimaan.antrian.view',
                'display_name' => 'Akses Penerimaan Tebu - Monitoring Antrian',
                'group' => 'Penerimaan Tebu',
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'penerimaan.pemasukan.view',
                'display_name' => 'Akses Penerimaan Tebu - Data Pemasukan',
                'group' => 'Penerimaan Tebu',
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::connection('pgsql')->table('permissions')->upsert(
            $permissions,
            ['name'],
            ['display_name', 'group', 'description', 'updated_at']
        );

        $oldPermissionId = DB::connection('pgsql')->table('permissions')
            ->where('name', 'penerimaan.view')
            ->value('id');

        if (!$oldPermissionId) {
            return;
        }

        $newPermissionIds = DB::connection('pgsql')->table('permissions')
            ->whereIn('name', [
                'penerimaan.spa.view',
                'penerimaan.antrian.view',
                'penerimaan.pemasukan.view',
            ])
            ->pluck('id')
            ->values();

        if ($newPermissionIds->isEmpty()) {
            return;
        }

        $roleIds = DB::connection('pgsql')->table('role_permission')
            ->where('permission_id', $oldPermissionId)
            ->pluck('role_id')
            ->unique()
            ->values();

        if ($roleIds->isEmpty()) {
            return;
        }

        $pivotRows = [];
        foreach ($roleIds as $roleId) {
            foreach ($newPermissionIds as $permissionId) {
                $pivotRows[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::connection('pgsql')->table('role_permission')->upsert(
            $pivotRows,
            ['role_id', 'permission_id'],
            ['updated_at']
        );
    }

    public function down(): void
    {
        $permissionIds = DB::connection('pgsql')->table('permissions')
            ->whereIn('name', [
                'penerimaan.spa.view',
                'penerimaan.antrian.view',
                'penerimaan.pemasukan.view',
            ])
            ->pluck('id')
            ->values();

        if ($permissionIds->isNotEmpty()) {
            DB::connection('pgsql')->table('role_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::connection('pgsql')->table('permissions')
            ->whereIn('name', [
                'penerimaan.spa.view',
                'penerimaan.antrian.view',
                'penerimaan.pemasukan.view',
            ])
            ->delete();
    }
};
