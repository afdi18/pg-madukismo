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
    case DASHBOARD_PRINT          = 'dashboard.print';

    // ================================================================
    // PETA KEBUN (PostgreSQL)
    // ================================================================
    case PETA_KEBUN_VIEW          = 'peta_kebun.view';
    case PETA_KEBUN_CREATE        = 'peta_kebun.create';
    case PETA_KEBUN_UPDATE        = 'peta_kebun.update';
    case PETA_KEBUN_DELETE        = 'peta_kebun.delete';
    case PETA_KEBUN_PRINT         = 'peta_kebun.print';

    // ================================================================
    // LAB QA (PostgreSQL)
    // ================================================================
    case LAB_QA_VIEW              = 'lab_qa.view';
    case LAB_QA_CREATE            = 'lab_qa.create';
    case LAB_QA_UPDATE            = 'lab_qa.update';
    case LAB_QA_DELETE            = 'lab_qa.delete';
    case LAB_QA_APPROVE           = 'lab_qa.approve';
    case LAB_QA_EXPORT            = 'lab_qa.export';
    case LAB_QA_PRINT             = 'lab_qa.print';
    case LAB_QA_POS_NPP           = 'lab_qa.pos_npp';

    // ================================================================
    // PENERIMAAN TEBU (SQL Server)
    // ================================================================
    case PENERIMAAN_VIEW          = 'penerimaan.view';
    case PENERIMAAN_SPA_VIEW      = 'penerimaan.spa.view';
    case PENERIMAAN_SPA_PRINT     = 'penerimaan.spa.print';
    case PENERIMAAN_ANTRIAN_VIEW  = 'penerimaan.antrian.view';
    case PENERIMAAN_ANTRIAN_PRINT = 'penerimaan.antrian.print';
    case PENERIMAAN_PEMASUKAN_VIEW = 'penerimaan.pemasukan.view';
    case PENERIMAAN_PEMASUKAN_EXPORT = 'penerimaan.pemasukan.export';
    case PENERIMAAN_PEMASUKAN_PRINT = 'penerimaan.pemasukan.print';
    case PENERIMAAN_PEMASUKAN_KEBUN_VIEW = 'penerimaan.pemasukan.kebun.view';
    case PENERIMAAN_PEMASUKAN_KATEGORI_VIEW = 'penerimaan.pemasukan.kategori.view';
    case PENERIMAAN_PEMASUKAN_WILAYAH_VIEW = 'penerimaan.pemasukan.wilayah.view';
    case PENERIMAAN_PEMASUKAN_SISA_PAGI_VIEW = 'penerimaan.pemasukan.sisa_pagi.view';
    case PENERIMAAN_PEMASUKAN_DIGILING_SPA_VIEW = 'penerimaan.pemasukan.digiling_spa.view';
    case PENERIMAAN_PEMASUKAN_KEBUN_EXPORT = 'penerimaan.pemasukan.kebun.export';
    case PENERIMAAN_PEMASUKAN_KEBUN_PRINT = 'penerimaan.pemasukan.kebun.print';
    case PENERIMAAN_PEMASUKAN_KATEGORI_EXPORT = 'penerimaan.pemasukan.kategori.export';
    case PENERIMAAN_PEMASUKAN_KATEGORI_PRINT = 'penerimaan.pemasukan.kategori.print';
    case PENERIMAAN_PEMASUKAN_WILAYAH_EXPORT = 'penerimaan.pemasukan.wilayah.export';
    case PENERIMAAN_PEMASUKAN_WILAYAH_PRINT = 'penerimaan.pemasukan.wilayah.print';
    case PENERIMAAN_PEMASUKAN_SISA_PAGI_EXPORT = 'penerimaan.pemasukan.sisa_pagi.export';
    case PENERIMAAN_PEMASUKAN_SISA_PAGI_PRINT = 'penerimaan.pemasukan.sisa_pagi.print';
    case PENERIMAAN_PEMASUKAN_DIGILING_SPA_EXPORT = 'penerimaan.pemasukan.digiling_spa.export';
    case PENERIMAAN_PEMASUKAN_DIGILING_SPA_PRINT = 'penerimaan.pemasukan.digiling_spa.print';
    case PENERIMAAN_CREATE        = 'penerimaan.create';
    case PENERIMAAN_UPDATE        = 'penerimaan.update';
    case PENERIMAAN_DELETE        = 'penerimaan.delete';
    case PENERIMAAN_EXPORT        = 'penerimaan.export';
    case PENERIMAAN_PRINT         = 'penerimaan.print';

    // ================================================================
    // MONITORING PABRIK (dipakai di menu Dashboard)
    // ================================================================
    case OPERASIONAL_VIEW         = 'operasional.view';
    case OPERASIONAL_CREATE       = 'operasional.create';
    case OPERASIONAL_UPDATE       = 'operasional.update';
    case OPERASIONAL_DELETE       = 'operasional.delete';
    case OPERASIONAL_EXPORT       = 'operasional.export';
    case OPERASIONAL_PRINT        = 'operasional.print';

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
            self::DASHBOARD_PRINT          => 'Cetak Dashboard',
            self::PETA_KEBUN_VIEW          => 'Lihat Peta Kebun',
            self::PETA_KEBUN_CREATE        => 'Tambah Peta Kebun',
            self::PETA_KEBUN_UPDATE        => 'Edit Peta Kebun',
            self::PETA_KEBUN_DELETE        => 'Hapus Peta Kebun',
            self::PETA_KEBUN_PRINT         => 'Cetak Peta Kebun',
            self::LAB_QA_VIEW              => 'Akses Analisa QA (Entri Pabrik Gula & Alkohol)',
            self::LAB_QA_CREATE            => 'Tambah Entri Data QA Pabrik Gula / Alkohol',
            self::LAB_QA_UPDATE            => 'Edit Entri Data QA Pabrik Gula / Alkohol',
            self::LAB_QA_DELETE            => 'Hapus Entri Data QA Pabrik Gula / Alkohol',
            self::LAB_QA_APPROVE           => 'Approve Lab QA',
            self::LAB_QA_EXPORT            => 'Export Lab QA',
            self::LAB_QA_PRINT             => 'Cetak Lab QA',
            self::LAB_QA_POS_NPP           => 'Akses Pos NPP (Input Brix/Pol/Rend)',
            self::PENERIMAAN_VIEW          => 'Akses Penerimaan Tebu (Data SPA, Pengaturan EPOS, Monitoring Antrian, Data Pemasukan)',
            self::PENERIMAAN_SPA_VIEW      => 'Akses Penerimaan Tebu - Monitoring SPA',
            self::PENERIMAAN_SPA_PRINT     => 'Cetak Penerimaan Tebu - Monitoring SPA',
            self::PENERIMAAN_ANTRIAN_VIEW  => 'Akses Penerimaan Tebu - Monitoring Antrian',
            self::PENERIMAAN_ANTRIAN_PRINT => 'Cetak Penerimaan Tebu - Monitoring Antrian',
            self::PENERIMAAN_PEMASUKAN_VIEW => 'Akses Penerimaan Tebu - Data Pemasukan',
            self::PENERIMAAN_PEMASUKAN_EXPORT => 'Export Penerimaan Tebu - Data Pemasukan',
            self::PENERIMAAN_PEMASUKAN_PRINT => 'Cetak Penerimaan Tebu - Data Pemasukan',
            self::PENERIMAAN_PEMASUKAN_KEBUN_VIEW => 'Akses Data Pemasukan - Tab Pemasukan per Kebun',
            self::PENERIMAAN_PEMASUKAN_KATEGORI_VIEW => 'Akses Data Pemasukan - Tab Pemasukan per Kategori',
            self::PENERIMAAN_PEMASUKAN_WILAYAH_VIEW => 'Akses Data Pemasukan - Tab Pemasukan per Wilayah',
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_VIEW => 'Akses Data Pemasukan - Tab Sisa Pagi',
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_VIEW => 'Akses Data Pemasukan - Tab Digiling per SPA',
            self::PENERIMAAN_PEMASUKAN_KEBUN_EXPORT => 'Export Data Pemasukan - Tab Pemasukan per Kebun',
            self::PENERIMAAN_PEMASUKAN_KEBUN_PRINT => 'Cetak Data Pemasukan - Tab Pemasukan per Kebun',
            self::PENERIMAAN_PEMASUKAN_KATEGORI_EXPORT => 'Export Data Pemasukan - Tab Pemasukan per Kategori',
            self::PENERIMAAN_PEMASUKAN_KATEGORI_PRINT => 'Cetak Data Pemasukan - Tab Pemasukan per Kategori',
            self::PENERIMAAN_PEMASUKAN_WILAYAH_EXPORT => 'Export Data Pemasukan - Tab Pemasukan per Wilayah',
            self::PENERIMAAN_PEMASUKAN_WILAYAH_PRINT => 'Cetak Data Pemasukan - Tab Pemasukan per Wilayah',
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_EXPORT => 'Export Data Pemasukan - Tab Sisa Pagi',
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_PRINT => 'Cetak Data Pemasukan - Tab Sisa Pagi',
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_EXPORT => 'Export Data Pemasukan - Tab Digiling per SPA',
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_PRINT => 'Cetak Data Pemasukan - Tab Digiling per SPA',
            self::PENERIMAAN_CREATE        => 'Tambah Penerimaan Tebu',
            self::PENERIMAAN_UPDATE        => 'Edit Penerimaan Tebu',
            self::PENERIMAAN_DELETE        => 'Hapus Penerimaan Tebu',
            self::PENERIMAAN_EXPORT        => 'Export Penerimaan Tebu',
            self::PENERIMAAN_PRINT         => 'Cetak Penerimaan Tebu',
            self::OPERASIONAL_VIEW         => 'Akses Monitoring Pabrik',
            self::OPERASIONAL_CREATE       => 'Tambah Data Monitoring Pabrik',
            self::OPERASIONAL_UPDATE       => 'Edit Data Monitoring Pabrik',
            self::OPERASIONAL_DELETE       => 'Hapus Data Monitoring Pabrik',
            self::OPERASIONAL_EXPORT       => 'Export Data Monitoring Pabrik',
            self::OPERASIONAL_PRINT        => 'Cetak Data Monitoring Pabrik',
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
            self::DASHBOARD_PRINT->value,
            self::PETA_KEBUN_VIEW->value,
            self::PETA_KEBUN_PRINT->value,
            self::LAB_QA_VIEW->value,
            self::LAB_QA_EXPORT->value,
            self::LAB_QA_PRINT->value,
            self::PENERIMAAN_VIEW->value,
            self::PENERIMAAN_SPA_VIEW->value,
            self::PENERIMAAN_SPA_PRINT->value,
            self::PENERIMAAN_ANTRIAN_VIEW->value,
            self::PENERIMAAN_ANTRIAN_PRINT->value,
            self::PENERIMAAN_PEMASUKAN_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_EXPORT->value,
            self::PENERIMAAN_PEMASUKAN_PRINT->value,
            self::PENERIMAAN_PEMASUKAN_KEBUN_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_KATEGORI_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_WILAYAH_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_KEBUN_EXPORT->value,
            self::PENERIMAAN_PEMASUKAN_KEBUN_PRINT->value,
            self::PENERIMAAN_PEMASUKAN_KATEGORI_EXPORT->value,
            self::PENERIMAAN_PEMASUKAN_KATEGORI_PRINT->value,
            self::PENERIMAAN_PEMASUKAN_WILAYAH_EXPORT->value,
            self::PENERIMAAN_PEMASUKAN_WILAYAH_PRINT->value,
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_EXPORT->value,
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_PRINT->value,
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_EXPORT->value,
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_PRINT->value,
            self::PENERIMAAN_EXPORT->value,
            self::PENERIMAAN_PRINT->value,
            self::OPERASIONAL_VIEW->value,
            self::OPERASIONAL_EXPORT->value,
            self::OPERASIONAL_PRINT->value,
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
            self::PENERIMAAN_PEMASUKAN_KEBUN_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_KATEGORI_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_WILAYAH_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_VIEW->value,
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
            self::PENERIMAAN_PEMASUKAN_KEBUN_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_KATEGORI_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_WILAYAH_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_SISA_PAGI_VIEW->value,
            self::PENERIMAAN_PEMASUKAN_DIGILING_SPA_VIEW->value,
            self::OPERASIONAL_VIEW->value,
        ];
    }
}
