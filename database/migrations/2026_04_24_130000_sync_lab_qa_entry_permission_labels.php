<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $updates = [
            'lab_qa.create' => 'Tambah Entri Data QA Pabrik Gula / Alkohol',
            'lab_qa.update' => 'Edit Entri Data QA Pabrik Gula / Alkohol',
            'lab_qa.delete' => 'Hapus Entri Data QA Pabrik Gula / Alkohol',
        ];

        foreach ($updates as $name => $displayName) {
            DB::connection('pgsql')
                ->table('permissions')
                ->where('name', $name)
                ->update([
                    'display_name' => $displayName,
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        $rollback = [
            'lab_qa.create' => 'Tambah Data Analisa QA',
            'lab_qa.update' => 'Edit Data Analisa QA',
            'lab_qa.delete' => 'Hapus Data Lab QA',
        ];

        foreach ($rollback as $name => $displayName) {
            DB::connection('pgsql')
                ->table('permissions')
                ->where('name', $name)
                ->update([
                    'display_name' => $displayName,
                    'updated_at' => now(),
                ]);
        }
    }
};
