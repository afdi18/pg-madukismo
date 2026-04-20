<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function getSqlServerMajorVersion(): int
    {
        $version = DB::connection('sqlsrv')
            ->selectOne("SELECT SERVERPROPERTY('ProductMajorVersion') AS ver");

        return (int) ($version->ver ?? 0);
    }

    protected function dropSqlSrvTableSafe(string $table): void
    {
        $version = $this->getSqlServerMajorVersion();

        if ($version >= 13) {
            DB::connection('sqlsrv')->statement("DROP TABLE IF EXISTS [{$table}]");
            return;
        }

        DB::connection('sqlsrv')
            ->statement("IF OBJECT_ID(N'[dbo].[{$table}]', N'U') IS NOT NULL DROP TABLE [dbo].[{$table}]");
    }

    public function up(): void
    {
        if (Schema::connection('sqlsrv')->hasTable('tanaman_produksi')) {
            $this->dropSqlSrvTableSafe('tanaman_produksi');
        }

        $permissionIds = DB::connection('pgsql')
            ->table('permissions')
            ->where('name', 'like', 'tanaman.%')
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::connection('pgsql')
                ->table('role_permission')
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::connection('pgsql')
            ->table('permissions')
            ->where('name', 'like', 'tanaman.%')
            ->delete();
    }

    public function down(): void
    {
        if (!Schema::connection('sqlsrv')->hasTable('tanaman_produksi')) {
            Schema::connection('sqlsrv')->create('tanaman_produksi', function (Blueprint $table) {
                $table->id();
                $table->string('kode_kebun', 20)->comment('Referensi kode kebun dari PostgreSQL');
                $table->integer('musim_tanam')->comment('Tahun musim tanam: 2024, 2025, dst');
                $table->date('tanggal_input');
                $table->decimal('tonase_tebang', 12, 2)->default(0)->comment('Tonase tebu ditebang (ton)');
                $table->decimal('tonase_giling', 12, 2)->default(0)->comment('Tonase masuk gilingan (ton)');
                $table->decimal('rendemen_kebun', 5, 2)->default(0)->comment('Rendemen dari kebun (%)');
                $table->decimal('hasil_gula', 12, 2)->default(0)->comment('Hasil gula (ton)');
                $table->decimal('hablur', 5, 2)->default(0)->comment('Hablur/kristal gula (%)');
                $table->string('varietas', 50)->nullable();
                $table->string('jenis_tebangan', 30)->nullable()->comment('Mekanis, Manual');
                $table->string('status_input', 20)->default('draft')->comment('draft, final, dikoreksi');
                $table->unsignedBigInteger('input_by')->nullable()->comment('ID user dari PostgreSQL');
                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->index(['kode_kebun', 'musim_tanam']);
                $table->index('tanggal_input');
                $table->index('musim_tanam');
            });
        }

        $permissions = [
            ['name' => 'tanaman.view', 'display_name' => 'Lihat Data Tanaman', 'group' => 'Data Tanaman'],
            ['name' => 'tanaman.create', 'display_name' => 'Tambah Data Tanaman', 'group' => 'Data Tanaman'],
            ['name' => 'tanaman.update', 'display_name' => 'Edit Data Tanaman', 'group' => 'Data Tanaman'],
            ['name' => 'tanaman.delete', 'display_name' => 'Hapus Data Tanaman', 'group' => 'Data Tanaman'],
            ['name' => 'tanaman.export', 'display_name' => 'Export Data Tanaman', 'group' => 'Data Tanaman'],
        ];

        foreach ($permissions as $permission) {
            DB::connection('pgsql')->table('permissions')->updateOrInsert(
                ['name' => $permission['name']],
                [
                    'display_name' => $permission['display_name'],
                    'group' => $permission['group'],
                    'description' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
};
