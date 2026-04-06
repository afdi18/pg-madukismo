<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('trx_qa_header', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->comment('Tanggal pelaksanaan QA');
            $table->time('jam')->comment('Jam pelaksanaan QA');
            $table->integer('shift')->comment('Shift kerja: 1, 2, atau 3');
            $table->string('petugas', 100)->comment('Nama petugas yang melaksanakan QA');
            $table->timestamps();
            $table->index(['tanggal', 'shift']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('trx_qa_header');
    }
};
