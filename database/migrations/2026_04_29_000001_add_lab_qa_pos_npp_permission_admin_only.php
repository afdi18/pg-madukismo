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
            $permissionName = 'lab_qa.pos_npp';

            $conn->table('permissions')->upsert([
                [
                    'name' => $permissionName,
                    'display_name' => 'Akses Pos NPP (Input Brix/Pol/Rend)',
                    'group' => 'Analisa QA',
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            ], ['name'], ['display_name', 'group', 'description', 'updated_at']);

            $permissionId = $conn->table('permissions')
                ->where('name', $permissionName)
                ->value('id');

            $adminRoleId = $conn->table('roles')
                ->where('name', 'Administrator')
                ->value('id');

            if ($permissionId && $adminRoleId) {
                $conn->table('role_permission')->upsert([
                    [
                        'role_id' => $adminRoleId,
                        'permission_id' => $permissionId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ],
                ], ['role_id', 'permission_id'], ['updated_at']);
            }
        });
    }

    public function down(): void
    {
        $conn = DB::connection('pgsql');

        $permissionId = $conn->table('permissions')
            ->where('name', 'lab_qa.pos_npp')
            ->value('id');

        if ($permissionId) {
            $conn->table('role_permission')
                ->where('permission_id', $permissionId)
                ->delete();

            $conn->table('permissions')
                ->where('id', $permissionId)
                ->delete();
        }
    }
};
