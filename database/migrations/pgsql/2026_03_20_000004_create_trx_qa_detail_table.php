<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('trx_qa_detail', function (Blueprint $table) {
            $table->id();
            $table->foreignId('header_id')
                  ->constrained('trx_qa_header')
                  ->onDelete('cascade')
                  ->comment('Referensi ke header QA');
            $table->foreignId('parameter_id')
                  ->constrained('mst_parameter')
                  ->onDelete('restrict')
                  ->comment('Referensi ke master parameter');
            $table->decimal('nilai_aktual', 10, 4)->nullable()->comment('Nilai aktual hasil pengukuran');
            $table->boolean('status_alert')->default(false)->comment('true = melanggar batas (alert merah), false = aman (hijau)');
            $table->timestamps();
            $table->index(['header_id', 'parameter_id']);
            $table->index('status_alert');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('trx_qa_detail');
    }
};
