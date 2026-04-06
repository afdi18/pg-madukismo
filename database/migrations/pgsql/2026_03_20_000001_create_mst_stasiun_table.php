<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('mst_stasiun', function (Blueprint $table) {
            $table->id();
            $table->string('nama_stasiun', 100)->unique()->comment('Nama stasiun produksi: Gilingan, Pemurnian, dll');
            $table->timestamps();
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('mst_stasiun');
    }
};
