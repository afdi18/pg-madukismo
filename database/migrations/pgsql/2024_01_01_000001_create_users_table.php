<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gunakan koneksi PostgreSQL.
     */
    protected $connection = 'pgsql';

    public function up(): void
    {
        Schema::connection('pgsql')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 50)->unique()->comment('Login username');
            $table->string('name', 100)->comment('Nama lengkap');
            $table->string('email', 100)->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true)->comment('Status aktif user');
            $table->timestamp('last_login_at')->nullable();
            $table->string('avatar')->nullable()->comment('Path foto profil');
            $table->string('phone', 20)->nullable();
            $table->string('jabatan', 100)->nullable()->comment('Jabatan/posisi');
            $table->string('divisi', 100)->nullable()->comment('Divisi/departemen');
            $table->string('area_kebun', 50)->nullable()->comment('Area kebun tanggung jawab');
            $table->text('notes')->nullable()->comment('Catatan admin');
            $table->unsignedBigInteger('created_by')->nullable()->comment('ID admin yang membuat');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['username', 'is_active']);
            $table->index('divisi');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('users');
    }
};
