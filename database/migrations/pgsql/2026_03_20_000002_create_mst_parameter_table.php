<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('mst_parameter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stasiun_id')
                  ->constrained('mst_stasiun')
                  ->onDelete('cascade')
                  ->comment('Referensi ke stasiun produksi');
            $table->string('nama_parameter', 100)->comment('Parameter yang diukur: Kap. Gilingan, N. Mentah pH, dll');
            $table->enum('operator_kondisi', ['>', '<', '>=', '<=', 'BETWEEN', 'NONE'])
                  ->default('NONE')
                  ->comment('Operator untuk pengecekan batas (>, <, >=, <=, BETWEEN, atau NONE untuk tidak ada pengecekan)');
            $table->decimal('batas_bawah', 10, 4)->nullable()->comment('Batas bawah/minimum nilai yang diizinkan');
            $table->decimal('batas_atas', 10, 4)->nullable()->comment('Batas atas/maksimum nilai yang diizinkan');
            $table->boolean('is_aktif')->default(true)->comment('Status parameter aktif atau tidak');
            $table->timestamps();
            $table->index(['stasiun_id', 'is_aktif']);
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('mst_parameter');
    }
};
