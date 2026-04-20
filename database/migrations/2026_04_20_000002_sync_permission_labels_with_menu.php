<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $updates = [
            'dashboard.view' => [
                'display_name' => 'Akses Dashboard (Informasi Tebu, Monitoring Pabrik, Angka Pengawasan QA)',
                'group' => 'Dashboard',
            ],
            'lab_qa.view' => [
                'display_name' => 'Akses Analisa QA (Entri Pabrik Gula & Alkohol)',
                'group' => 'Analisa QA',
            ],
            'lab_qa.create' => [
                'display_name' => 'Tambah Data Analisa QA',
                'group' => 'Analisa QA',
            ],
            'lab_qa.update' => [
                'display_name' => 'Edit Data Analisa QA',
                'group' => 'Analisa QA',
            ],
            'lab_qa.delete' => [
                'display_name' => 'Hapus Data Lab QA',
                'group' => 'Analisa QA',
            ],
            'lab_qa.approve' => [
                'display_name' => 'Approve Lab QA',
                'group' => 'Analisa QA',
            ],
            'lab_qa.export' => [
                'display_name' => 'Export Lab QA',
                'group' => 'Analisa QA',
            ],
            'penerimaan.view' => [
                'display_name' => 'Akses Penerimaan Tebu (Data SPA, Pengaturan EPOS, Monitoring Antrian, Data Pemasukan)',
                'group' => 'Penerimaan Tebu',
            ],
            'operasional.view' => [
                'display_name' => 'Akses Monitoring Pabrik',
                'group' => 'Dashboard',
            ],
            'operasional.create' => [
                'display_name' => 'Tambah Data Monitoring Pabrik',
                'group' => 'Dashboard',
            ],
            'operasional.update' => [
                'display_name' => 'Edit Data Monitoring Pabrik',
                'group' => 'Dashboard',
            ],
            'operasional.delete' => [
                'display_name' => 'Hapus Data Monitoring Pabrik',
                'group' => 'Dashboard',
            ],
            'operasional.export' => [
                'display_name' => 'Export Data Monitoring Pabrik',
                'group' => 'Dashboard',
            ],
            'user.view' => [
                'display_name' => 'Lihat Manajemen User',
                'group' => 'Manajemen User',
            ],
            'role.view' => [
                'display_name' => 'Lihat Manajemen ACL',
                'group' => 'Manajemen ACL',
            ],
            'role.create' => [
                'display_name' => 'Tambah ACL',
                'group' => 'Manajemen ACL',
            ],
            'role.update' => [
                'display_name' => 'Edit ACL',
                'group' => 'Manajemen ACL',
            ],
            'role.delete' => [
                'display_name' => 'Hapus ACL',
                'group' => 'Manajemen ACL',
            ],
            'role.assign_permission' => [
                'display_name' => 'Assign Permission ACL',
                'group' => 'Manajemen ACL',
            ],
        ];

        $query = DB::connection('pgsql')->table('permissions');

        foreach ($updates as $name => $data) {
            $query->where('name', $name)->update($data);
        }
    }

    public function down(): void
    {
        $reverts = [
            'dashboard.view' => [
                'display_name' => 'Lihat Dashboard',
                'group' => 'Dashboard',
            ],
            'lab_qa.view' => [
                'display_name' => 'Lihat Lab QA',
                'group' => 'Lab QA',
            ],
            'lab_qa.create' => [
                'display_name' => 'Tambah Data Lab QA',
                'group' => 'Lab QA',
            ],
            'lab_qa.update' => [
                'display_name' => 'Edit Data Lab QA',
                'group' => 'Lab QA',
            ],
            'lab_qa.delete' => [
                'display_name' => 'Hapus Data Lab QA',
                'group' => 'Lab QA',
            ],
            'lab_qa.approve' => [
                'display_name' => 'Approve Lab QA',
                'group' => 'Lab QA',
            ],
            'lab_qa.export' => [
                'display_name' => 'Export Lab QA',
                'group' => 'Lab QA',
            ],
            'penerimaan.view' => [
                'display_name' => 'Lihat Penerimaan Tebu',
                'group' => 'Penerimaan Tebu',
            ],
            'operasional.view' => [
                'display_name' => 'Lihat Operasional Pabrik',
                'group' => 'Operasional Pabrik',
            ],
            'operasional.create' => [
                'display_name' => 'Tambah Operasional Pabrik',
                'group' => 'Operasional Pabrik',
            ],
            'operasional.update' => [
                'display_name' => 'Edit Operasional Pabrik',
                'group' => 'Operasional Pabrik',
            ],
            'operasional.delete' => [
                'display_name' => 'Hapus Operasional Pabrik',
                'group' => 'Operasional Pabrik',
            ],
            'operasional.export' => [
                'display_name' => 'Export Operasional Pabrik',
                'group' => 'Operasional Pabrik',
            ],
            'user.view' => [
                'display_name' => 'Lihat User',
                'group' => 'Manajemen User',
            ],
            'role.view' => [
                'display_name' => 'Lihat Role',
                'group' => 'Manajemen Role',
            ],
            'role.create' => [
                'display_name' => 'Tambah Role',
                'group' => 'Manajemen Role',
            ],
            'role.update' => [
                'display_name' => 'Edit Role',
                'group' => 'Manajemen Role',
            ],
            'role.delete' => [
                'display_name' => 'Hapus Role',
                'group' => 'Manajemen Role',
            ],
            'role.assign_permission' => [
                'display_name' => 'Assign Permission Role',
                'group' => 'Manajemen Role',
            ],
        ];

        $query = DB::connection('pgsql')->table('permissions');

        foreach ($reverts as $name => $data) {
            $query->where('name', $name)->update($data);
        }
    }
};
