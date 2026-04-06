<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->table('trx_qa_header', function (Blueprint $table) {
            if (!Schema::connection('pgsql')->hasColumn('trx_qa_header', 'stasiun_id')) {
                $table->foreignId('stasiun_id')
                    ->nullable()
                    ->after('petugas')
                    ->constrained('mst_stasiun')
                    ->nullOnDelete();

                $table->index(['stasiun_id', 'tanggal']);
            }
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->table('trx_qa_header', function (Blueprint $table) {
            if (Schema::connection('pgsql')->hasColumn('trx_qa_header', 'stasiun_id')) {
                $table->dropConstrainedForeignId('stasiun_id');
            }
        });
    }
};
