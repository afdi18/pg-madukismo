<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Api\AclManagementController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PetaKebunController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Middleware\AbacMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Dashboard Monitoring PG Madukismo
|--------------------------------------------------------------------------
*/

// ================================================================
// AUTH (Public)
// ================================================================
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
         ->name('auth.login');
});

// ================================================================
// PROTECTED ROUTES (Membutuhkan Sanctum Token)
// ================================================================
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
             ->name('auth.logout');
        Route::get('/me', [AuthenticatedSessionController::class, 'me'])
             ->name('auth.me');
    });

    // ============================================================
    // DASHBOARD (SQL Server - read)
    // ============================================================
    Route::prefix('dashboard')->middleware(AbacMiddleware::class . ':dashboard.view')->group(function () {
        Route::get('/summary',             [DashboardController::class, 'summary']);
        Route::get('/rendemen-trend',      [DashboardController::class, 'rendemenTrend']);
        Route::get('/kpi',                 [DashboardController::class, 'kpiSummary']);
        Route::get('/perbandingan-musim',  [DashboardController::class, 'perbandinganMusim']);
        Route::get('/musim-list',          [DashboardController::class, 'musimList']);
        Route::get('/data-digiling',       [DashboardController::class, 'dataDigilingPerJam']);
        Route::get('/tebu-digiling-sampai-hari-ini', [DashboardController::class, 'tebuDigilingSampaiBariIni']);
          Route::get('/antrian-summary', [DashboardController::class, 'antrianSummaryDashboard']);
    });

    // Dashboard Export (butuh permission tambahan)
    Route::get('/dashboard/export', [DashboardController::class, 'export'])
         ->middleware(AbacMiddleware::class . ':dashboard.export')
         ->name('dashboard.export');

     // ============================================================
     // PENERIMAAN (SQL Server - TBL_MASTSPA)
     // ============================================================
     Route::prefix('penerimaan')->group(function () {
          Route::get('/', [\App\Http\Controllers\Api\PenerimaanController::class, 'index'])
                ->middleware(AbacMiddleware::class . ':penerimaan.spa.view');
              Route::get('/antrian-lori', [\App\Http\Controllers\Api\PenerimaanController::class, 'antrianLori'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.antrian.view');
              Route::get('/antrian-truk-sudah-timbang', [\App\Http\Controllers\Api\PenerimaanController::class, 'antrianTrukSudahTimbang'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.antrian.view');
              Route::get('/antrian-truk-belum-timbang', [\App\Http\Controllers\Api\PenerimaanController::class, 'antrianTrukBelumTimbang'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.antrian.view');
              Route::get('/pemasukan-kategori', [\App\Http\Controllers\Api\PenerimaanController::class, 'pemasukanKategori'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.pemasukan.view');
              Route::get('/pemasukan-wilayah', [\App\Http\Controllers\Api\PenerimaanController::class, 'pemasukanWilayah'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.pemasukan.view');
              Route::get('/pemasukan-kebun', [\App\Http\Controllers\Api\PenerimaanController::class, 'pemasukanKebun'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.pemasukan.view');
              Route::get('/default-hari', [\App\Http\Controllers\Api\PenerimaanController::class, 'defaultHari'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.pemasukan.view');
              Route::get('/sisa-pagi', [\App\Http\Controllers\Api\PenerimaanController::class, 'sisaPagi'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.pemasukan.view');
              Route::get('/digiling-per-spa', [\App\Http\Controllers\Api\PenerimaanController::class, 'digilingPerSpa'])
                   ->middleware(AbacMiddleware::class . ':penerimaan.pemasukan.view');
     });

     // ============================================================
     // EPOS DEVICE MANAGEMENT (SQL Server - TBL_DEVICE_EPOS, TBL_POS_PANTAU)
     // ============================================================
     Route::prefix('epos')->group(function () {
          Route::get('/devices', [\App\Http\Controllers\Api\EposController::class, 'devices'])
               ->middleware(AbacMiddleware::class . ':penerimaan.spa.view');
          Route::get('/pos', [\App\Http\Controllers\Api\EposController::class, 'posList'])
               ->middleware(AbacMiddleware::class . ':penerimaan.spa.view');
          Route::post('/pos', [\App\Http\Controllers\Api\EposController::class, 'storePos'])
               ->middleware(AbacMiddleware::class . ':penerimaan.update,penerimaan.spa.view');
          Route::put('/pos/{idPos}', [\App\Http\Controllers\Api\EposController::class, 'updatePos'])
               ->middleware(AbacMiddleware::class . ':penerimaan.update,penerimaan.spa.view');
          Route::put('/devices/{idDevice}/active', [\App\Http\Controllers\Api\EposController::class, 'setActive'])
               ->middleware(AbacMiddleware::class . ':penerimaan.update,penerimaan.spa.view');
          Route::put('/devices/{idDevice}', [\App\Http\Controllers\Api\EposController::class, 'updateDevice'])
               ->middleware(AbacMiddleware::class . ':penerimaan.update,penerimaan.spa.view');
          Route::get('/settings', [\App\Http\Controllers\Api\EposController::class, 'settings'])
               ->middleware(AbacMiddleware::class . ':penerimaan.spa.view');
          Route::post('/settings', [\App\Http\Controllers\Api\EposController::class, 'saveSettings'])
               ->middleware(AbacMiddleware::class . ':penerimaan.update');
     });

    // ============================================================
    // PETA KEBUN (PostgreSQL)
    // ============================================================
    Route::prefix('peta-kebun')->group(function () {
        Route::get('/',           [PetaKebunController::class, 'index'])
             ->middleware(AbacMiddleware::class . ':peta_kebun.view');
        Route::get('/{kode}',     [PetaKebunController::class, 'show'])
             ->middleware(AbacMiddleware::class . ':peta_kebun.view');
        Route::post('/',          [PetaKebunController::class, 'store'])
             ->middleware(AbacMiddleware::class . ':peta_kebun.create');
        Route::put('/{kebunPeta}',[PetaKebunController::class, 'update'])
             ->middleware(AbacMiddleware::class . ':peta_kebun.update');
        Route::delete('/{kebunPeta}', [PetaKebunController::class, 'destroy'])
             ->middleware(AbacMiddleware::class . ':peta_kebun.delete');
    });

    // ============================================================
    // LAB QA (PostgreSQL)
    // ============================================================
    Route::prefix('lab-qa')->group(function () {
        // Master data untuk form entri
        Route::get('/master/stasiun', [\App\Http\Controllers\Api\LabQaController::class, 'stasiunOptions'])
             ->middleware(AbacMiddleware::class . ':lab_qa.view');
        Route::get('/master/parameters', [\App\Http\Controllers\Api\LabQaController::class, 'parameterOptions'])
             ->middleware(AbacMiddleware::class . ':lab_qa.view');

        // Report
        Route::get('/report/alerts', [\App\Http\Controllers\Api\LabQaController::class, 'reportAlert'])
             ->middleware(AbacMiddleware::class . ':lab_qa.view');

        // Dashboard monitoring (hourly pivot)
        Route::get('/dashboard-monitoring', [\App\Http\Controllers\Api\LabQaController::class, 'dashboardMonitoring'])
             ->middleware(AbacMiddleware::class . ':dashboard.pengawasan_qa.view');

        // Header QA
        Route::get('/',         [\App\Http\Controllers\Api\LabQaController::class, 'index'])
             ->middleware(AbacMiddleware::class . ':lab_qa.view');
        Route::get('/{qaHeader}',  [\App\Http\Controllers\Api\LabQaController::class, 'show'])
             ->middleware(AbacMiddleware::class . ':lab_qa.view');
        Route::post('/',        [\App\Http\Controllers\Api\LabQaController::class, 'store'])
             ->middleware(AbacMiddleware::class . ':lab_qa.create');
        Route::put('/{qaHeader}',  [\App\Http\Controllers\Api\LabQaController::class, 'update'])
             ->middleware(AbacMiddleware::class . ':lab_qa.update');
        Route::delete('/{qaHeader}', [\App\Http\Controllers\Api\LabQaController::class, 'destroy'])
             ->middleware(AbacMiddleware::class . ':lab_qa.delete');

        // Detail QA
        Route::post('/{qaHeader}/details', [\App\Http\Controllers\Api\LabQaController::class, 'storeDetail'])
             ->middleware(AbacMiddleware::class . ':lab_qa.update');
        Route::put('/detail/{qaDetail}', [\App\Http\Controllers\Api\LabQaController::class, 'updateDetail'])
             ->middleware(AbacMiddleware::class . ':lab_qa.update');
        Route::delete('/detail/{qaDetail}', [\App\Http\Controllers\Api\LabQaController::class, 'destroyDetail'])
             ->middleware(AbacMiddleware::class . ':lab_qa.delete');
    });

    // ============================================================
    // USER MANAGEMENT (Admin only)
    // ============================================================
    Route::prefix('users')->middleware(AbacMiddleware::class . ':user.view')->group(function () {
        Route::get('/',          [UserManagementController::class, 'index']);
        Route::get('/roles',     [UserManagementController::class, 'roles']);
        Route::get('/{user}',    [UserManagementController::class, 'show']);
        Route::post('/',         [UserManagementController::class, 'store'])
             ->middleware(AbacMiddleware::class . ':user.create');
        Route::put('/{user}',    [UserManagementController::class, 'update'])
             ->middleware(AbacMiddleware::class . ':user.update');
        Route::delete('/{user}', [UserManagementController::class, 'destroy'])
             ->middleware(AbacMiddleware::class . ':user.delete');
    });

     // ============================================================
     // ACL / ROLE MANAGEMENT
     // ============================================================
     Route::prefix('acl')->middleware(AbacMiddleware::class . ':role.view')->group(function () {
          Route::get('/permissions', [AclManagementController::class, 'permissionGroups']);
          Route::get('/roles', [AclManagementController::class, 'roles']);
          Route::post('/roles', [AclManagementController::class, 'store'])
               ->middleware(AbacMiddleware::class . ':role.create');
          Route::put('/roles/{role}', [AclManagementController::class, 'update'])
               ->middleware(AbacMiddleware::class . ':role.update');
          Route::delete('/roles/{role}', [AclManagementController::class, 'destroy'])
               ->middleware(AbacMiddleware::class . ':role.delete');
     });

});
