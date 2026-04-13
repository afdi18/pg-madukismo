<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Enums\Permission as PermissionEnum;

class AbacSeeder extends Seeder
{
    public function run(): void
    {
        DB::connection('pgsql')->transaction(function () {

            // ============================================================
            // 1. INSERT PERMISSIONS
            // ============================================================
            $permissionsData = [];
            foreach (PermissionEnum::cases() as $perm) {
                $permissionsData[] = [
                    'name'         => $perm->value,
                    'display_name' => $perm->label(),
                    'group'        => $perm->group(),
                    'description'  => null,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ];
            }
            DB::connection('pgsql')->table('permissions')->insertOrIgnore($permissionsData);

            // ============================================================
            // 2. INSERT ROLES
            // ============================================================
            $roles = [
                [
                    'name'         => 'Administrator',
                    'display_name' => 'Administrator',
                    'description'  => 'Akses penuh ke seluruh sistem termasuk manajemen user.',
                    'is_system'    => true,
                    'color'        => 'red',
                ],
                [
                    'name'         => 'Manajer',
                    'display_name' => 'Manajer',
                    'description'  => 'Akses monitoring, approval, dan laporan. Tidak bisa kelola user.',
                    'is_system'    => true,
                    'color'        => 'purple',
                ],
                [
                    'name'         => 'Supervisor',
                    'display_name' => 'Supervisor',
                    'description'  => 'Akses input data, approval Lab QA, monitoring area.',
                    'is_system'    => true,
                    'color'        => 'blue',
                ],
                [
                    'name'         => 'Operator',
                    'display_name' => 'Operator',
                    'description'  => 'Akses input dan update data tanaman, kebun, dan lab.',
                    'is_system'    => false,
                    'color'        => 'green',
                ],
                [
                    'name'         => 'Viewer',
                    'display_name' => 'Viewer',
                    'description'  => 'Akses baca saja (read-only) untuk semua modul.',
                    'is_system'    => false,
                    'color'        => 'gray',
                ],
            ];

            foreach ($roles as &$role) {
                $role['created_at'] = now();
                $role['updated_at'] = now();
            }

            DB::connection('pgsql')->table('roles')->insertOrIgnore($roles);

            // ============================================================
            // 3. ASSIGN PERMISSIONS KE ROLES
            // ============================================================
            $rolePermMap = [
                'Administrator' => PermissionEnum::adminPermissions(),
                'Manajer'       => PermissionEnum::managerPermissions(),
                'Supervisor'    => array_merge(
                    PermissionEnum::operatorPermissions(),
                    [PermissionEnum::LAB_QA_APPROVE->value]
                ),
                'Operator'      => PermissionEnum::operatorPermissions(),
                'Viewer'        => PermissionEnum::viewerPermissions(),
            ];

            foreach ($rolePermMap as $roleName => $permNames) {
                $roleId = DB::connection('pgsql')
                    ->table('roles')
                    ->where('name', $roleName)
                    ->value('id');

                $permIds = DB::connection('pgsql')
                    ->table('permissions')
                    ->whereIn('name', $permNames)
                    ->pluck('id');

                $pivotData = $permIds->map(fn($pid) => [
                    'role_id'       => $roleId,
                    'permission_id' => $pid,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ])->toArray();

                DB::connection('pgsql')->table('role_permission')->insertOrIgnore($pivotData);
            }

            // ============================================================
            // 4. CREATE USER ADMINISTRATOR PERTAMA
            // ============================================================
            // CREATE OR GET EXISTING ADMIN USER
            $existingAdmin = DB::connection('pgsql')->table('users')->where('username', 'admin')->first();

            if ($existingAdmin) {
                $adminId = $existingAdmin->id;
            } else {
                $adminId = DB::connection('pgsql')->table('users')->insertGetId([
                    'username'   => 'admin',
                    'name'       => 'Administrator Sistem',
                    'email'      => 'admin@madukismo.co.id',
                    'password'   => Hash::make('Admin@Madukismo2024'),
                    'is_active'  => true,
                    'jabatan'    => 'System Administrator',
                    'divisi'     => 'IT',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Assign role Administrator ke admin (insert jika belum ada)
            $adminRoleId = DB::connection('pgsql')
                ->table('roles')
                ->where('name', 'Administrator')
                ->value('id');

            DB::connection('pgsql')->table('user_roles')->insertOrIgnore([
                'user_id'     => $adminId,
                'role_id'     => $adminRoleId,
                'assigned_at' => now(),
                'assigned_by' => $adminId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $this->command->info('✅ ABAC Seeder berhasil:');
            $this->command->info('   - ' . count($permissionsData) . ' permissions dibuat');
            $this->command->info('   - ' . count($roles) . ' roles dibuat');
            $this->command->info('   - User admin: username=admin, password=Admin@Madukismo2024');
        });
    }
}
