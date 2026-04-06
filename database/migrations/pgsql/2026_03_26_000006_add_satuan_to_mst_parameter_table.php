<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        if (!Schema::connection('pgsql')->hasColumn('mst_parameter', 'satuan')) {
            Schema::connection('pgsql')->table('mst_parameter', function (Blueprint $table) {
                $table->string('satuan', 20)->nullable()->after('nama_parameter')->comment('Satuan parameter: %, pH, ppm, ku, ton, dll');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('pgsql')->hasColumn('mst_parameter', 'satuan')) {
            Schema::connection('pgsql')->table('mst_parameter', function (Blueprint $table) {
                $table->dropColumn('satuan');
            });
        }
    }
};
