<?php

namespace App\Enums;

enum Permission: string
{
    // ================================================================
    // DASHBOARD & MONITORING
    // ================================================================
    case DASHBOARD_VIEW           = 'dashboard.view';
    case DASHBOARD_PENGAWASAN_QA_VIEW = 'dashboard.pengawasan_qa.view';
    case DASHBOARD_EXPORT         = 'dashboard.export';

    // ================================================================
    // PETA KEBUN (PostgreSQL)
    // ================================================================
    case PETA_KEBUN_VIEW          = 'peta_kebun.view';
    case PETA_KEBUN_CREATE        = 'peta_kebun.create';
    case PETA_KEBUN_UPDATE        = 'peta_kebun.update';
    case PETA_KEBUN_DELETE        = 'peta_kebun.delete';

    // ================================================================
    // LAB QA (PostgreSQL)
    // ================================================================
    case LAB_QA_VIEW              = 'lab_qa.view';
    case LAB_QA_CREATE            = 'lab_qa.create';
    case LAB_QA_UPDATE            = 'lab_qa.update';
    case LAB_QA_DELETE            = 'lab_qa.delete';
    case LAB_QA_APPROVE           = 'lab_qa.approve';
    case LAB_QA_EXPORT            = 'lab_qa.export';

    // ================================================================
    // PENERIMAAN TEBU (SQL Server)
    // ================================================================
    case PENERIMAAN_VIEW          = 'penerimaan.view';
    case PENERIMAAN_SPA_VIEW      = 'penerimaan.spa.view';
    case PENERIMAAN_ANTRIAN_VIEW  = 'penerimaan.antrian.view';
    case PENERIMAAN_PEMASUKAN_VIEW = 'penerimaan.pemasukan.view';
    case PENERIMAAN_CREATE        = 'penerimaan.create';
    case PENERIMAAN_UPDATE        = 'penerimaan.update';
    case PENERIMAAN_DELETE        = 'penerimaan.delete';
    case PENERIMAAN_EXPORT        = 'penerimaan.export';

    // ================================================================
    // MONITORING PABRIK (dipakai di menu Dashboard)
    // ================================================================
    case OPERASIONAL_VIEW         = 'operasional.view';
    case OPERASIONAL_CREATE       = 'operasional.create';
    case OPERASIONAL_UPDATE       = 'operasional.update';
    case OPERASIONAL_DELETE       = 'operasional.delete';
    case OPERASIONAL_EXPORT       = 'operasional.export';

    // ================================================================
    // USER MANAGEMENT (PostgreSQL)
    // ================================================================
    case USER_VIEW                = 'user.view';
    case USER_CREATE              = 'user.create';
    case USER_UPDATE              = 'user.update';
    case USER_DELETE              = 'user.delete';
    case USER_ASSIGN_ROLE         = 'user.assign_role';
    case USER_ASSIGN_ATTRIBUTE    = 'user.assign_attribute';

    // ================================================================
    // MANAJEMEN ACL
    // ================================================================
    case ROLE_VIEW                = 'role.view';
    case ROLE_CREATE              = 'role.create';
    case ROLE_UPDATE              = 'role.update';
    case ROLE_DELETE              = 'role.delete';
    case ROLE_ASSIGN_PERMISSION   = 'role.assign_permission';

    // ================================================================
    // SISTEM & KONFIGURASI
    // ================================================================
    case SISTEM_SETTINGS          = 'sistem.settings';
    case SISTEM_LOGS              = 'sistem.logs';
    case SISTEM_BACKUP            = 'sistem.backup';

    /**
     * Kembalikan label tampilan untuk setiap permission.
     */
    public function label(): string
    {
        return match($this) {
            self::DASHBOARD_VIEW           => 'Akses Dashboard (Informasi Tebu)',
            self::DASHBOARD_PENGAWASAN_QA_VIEW => 'Akses Dashboard Angka Pengawasan QA',
            self::DASHBOARD_EXPORT         => 'Export Dashboard',
            self::PETA_KEBUN_VIEW          => 'Lihat Peta Kebun',
            self::PETA_KEBUN_CREATE        => 'Tambah Peta Kebun',
            self::PETA_KEBUN_UPDATE        => 'Edit Peta Kebun',
            self::PETA_KEBUN_DELETE        => 'Hapus Peta Kebun',
            self::LAB_QA_VIEW              => 'Akses Analisa QA (Entri Pabrik Gula & Alkohol)',
            self::LAB_QA_CREATE            => 'Tambah Entri Data QA Pabrik Gula / Alkohol',
            self::LAB_QA_UPDATE            => 'Edit Entri Data QA Pabrik Gula / Alkohol',
            self::LAB_QA_DELETE            => 'Hapus Entri Data QA Pabrik Gula / Alkohol',
            self::LAB_QA_APPROVE           => 'Approve Lab QA',
            self::LAB_QA_EXPORT            => 'Export Lab QA',
            self::PENERIMAAN_VIEW          => 'Akses Penerimaan Tebu (Data SPA, Pengaturan EPOS, Monitoring Antrian, Data Pemasukan)',
            self::PENERIMAAN_SPA_VIEW      => 'Akses Penerimaan Tebu - Monitoring SPA',
            self::PENERIMAAN_ANTRIAN_VIEW  => 'Akses Penerimaan Tebu - Monitoring Antrian',
            self::PENERIMAAN_PEMASUKAN_VIEW => 'Akses Penerimaan Tebu - Data Pemasukan',
            self::PENERIMAAN_CREATE        => 'Tambah Penerimaan Tebu',
            self::PENERIMAAN_UPDATE        => 'Edit Penerimaan Tebu',
            self::PENERIMAAN_DELETE        => 'Hapus Penerimaan Tebu',
            self::PENERIMAAN_EXPORT        => 'Export Penerimaan Tebu',
            self::OPERASIONAL_VIEW         => 'Akses Monitoring Pabrik',
            self::OPERASIONAL_CREATE       => 'Tambah Data Monitoring Pabrik',
            self::OPERASIONAL_UPDATE       => 'Edit Data Monitoring Pabrik',
            self::OPERASIONAL_DELETE       => 'Hapus Data Monitoring Pabrik',
            self::OPERASIONAL_EXPORT       => 'Export Data Monitoring Pabrik',
            self::USER_VIEW                => 'Lihat Manajemen User',
            self::USER_CREATE              => 'Tambah User',
            self::USER_UPDATE              => 'Edit User',
            self::USER_DELETE              => 'Hapus User',
            self::USER_ASSIGN_ROLE         => 'Assign Role User',
            self::USER_ASSIGN_ATTRIBUTE    => 'Assign Atribut User',
            self::ROLE_VIEW                => 'Lihat Manajemen ACL',
            self::ROLE_CREATE              => 'Tambah ACL',
            self::ROLE_UPDATE              => 'Edit ACL',
            self::ROLE_DELETE              => 'Hapus ACL',
            self::ROLE_ASSIGN_PERMISSION   => 'Assign Permission ACL',
            self::SISTEM_SETTINGS          => 'Pengaturan Sistem',
            self::SISTEM_LOGS              => 'Lihat Log Sistem',
            self::SISTEM_BACKUP            => 'Backup Sistem',
        };
    }

    /**
     * Grup permission berdasarkan modul.
     */
    public function group(): string
    {
        return match(true) {
            str_starts_with($this->value, 'dashboard')  => 'Dashboard',
            str_starts_with($this->value, 'peta_kebun') => 'Peta Kebun',
            str_starts_with($this->value, 'lab_qa')     => 'Analisa QA',
            str_starts_with($this->value, 'penerimaan') => 'Penerimaan Tebu',
            str_starts_with($this->value, 'operasional') => 'Dashboard',
            str_starts_with($this->value, 'user')       => 'Manajemen User',
            str_starts_with($this->value, 'role')       => 'Manajemen ACL',
            str_starts_with($this->value, 'sistem')     => 'Sistem',
            default                                     => 'Lainnya',
        };
    }

    /**
     * Semua permission default untuk role Administrator.
     */
    public static function adminPermissions(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Permission default untuk role Manajer.
     */
    public static function managerPermissions(): array
    {
        return [
            self::DASHBOARD_VIEW->value,
            self::DASHBOARD_PENGAWASAN_QA_VIEW->value,
            self::DASHBOARD_EXPORT->value,
            self::PETA_KEBUN_VIEW->value,
            self::LAB_QA_VIEW->value,
            self::LAB_QA_EXPORT->value,
            self::PENERIMAAN_VIEW->value,
            self::PENERIMAAN_SPA_VIEW->value,
            self::PENERIMAAN_ANTRIAN_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_VIEW->value,
            self::PENERIMAAN_EXPORT->value,
            self::OPERASIONAL_VIEW->value,
            self::OPERASIONAL_EXPORT->value,
        ];
    }

    /**
     * Permission default untuk role Operator.
     */
    public static function operatorPermissions(): array
    {
        return [
            self::DASHBOARD_VIEW->value,
            self::DASHBOARD_PENGAWASAN_QA_VIEW->value,
            self::PETA_KEBUN_VIEW->value,
            self::LAB_QA_VIEW->value,
            self::LAB_QA_CREATE->value,
            self::LAB_QA_UPDATE->value,
            self::PENERIMAAN_VIEW->value,
            self::PENERIMAAN_SPA_VIEW->value,
            self::PENERIMAAN_ANTRIAN_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_VIEW->value,
            self::PENERIMAAN_CREATE->value,
            self::PENERIMAAN_UPDATE->value,
            self::OPERASIONAL_VIEW->value,
            self::OPERASIONAL_CREATE->value,
            self::OPERASIONAL_UPDATE->value,
        ];
    }

    /**
     * Permission default untuk role Viewer (read-only).
     */
    public static function viewerPermissions(): array
    {
        return [
            self::DASHBOARD_VIEW->value,
            self::DASHBOARD_PENGAWASAN_QA_VIEW->value,
            self::PETA_KEBUN_VIEW->value,
            self::LAB_QA_VIEW->value,
            self::PENERIMAAN_VIEW->value,
            self::PENERIMAAN_SPA_VIEW->value,
            self::PENERIMAAN_ANTRIAN_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_VIEW->value,
            self::OPERASIONAL_VIEW->value,
        ];
    }
}
