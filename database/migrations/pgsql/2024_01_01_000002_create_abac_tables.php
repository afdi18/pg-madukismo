<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'pgsql';

    public function up(): void
    {
        // ============================================================
        // ROLES
        // ============================================================
        Schema::connection('pgsql')->create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 50)->unique()->comment('Nama role: Administrator, Manajer, Operator, Viewer');
            $table->string('display_name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false)->comment('Role sistem tidak bisa dihapus');
            $table->string('color', 20)->default('blue')->comment('Warna badge di UI');
            $table->timestamps();
        });

        // ============================================================
        // PERMISSIONS
        // ============================================================
        Schema::connection('pgsql')->create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique()->comment('e.g. dashboard.view, lab_qa.approve');
            $table->string('display_name', 150);
            $table->text('description')->nullable();
            $table->string('group', 50)->comment('Modul: Dashboard, Peta Kebun, Lab QA, dll');
            $table->timestamps();
        });

        // ============================================================
        // PIVOT: ROLE <-> PERMISSION
        // ============================================================
        Schema::connection('pgsql')->create('role_permission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->unsignedBigInteger('permission_id');
            $table->timestamps();

            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
            $table->unique(['role_id', 'permission_id']);
        });

        // ============================================================
        // PIVOT: USER <-> ROLE (dengan ABAC context)
        // ============================================================
        Schema::connection('pgsql')->create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->string('area_kebun', 50)->nullable()->comment('Override area kebun untuk role ini');
            $table->string('divisi', 100)->nullable()->comment('Override divisi untuk role ini');
            $table->timestamp('assigned_at')->useCurrent();
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['user_id', 'role_id']);
            $table->index('user_id');
        });

        // ============================================================
        // USER ATTRIBUTES (ABAC Subject Attributes)
        // ============================================================
        Schema::connection('pgsql')->create('user_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('attribute_key', 50)->comment('e.g. area_kebun, shift, level_akses');
            $table->string('attribute_value', 200)->comment('Nilai atribut');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['user_id', 'attribute_key']);
            $table->index('attribute_key');
        });

        // ============================================================
        // AUDIT LOG (Catat semua aktivitas user)
        // ============================================================
        Schema::connection('pgsql')->create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action', 100)->comment('e.g. login, create_user, update_lab_qa');
            $table->string('module', 50)->comment('Dashboard, PetaKebun, LabQA, User');
            $table->text('description')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['user_id', 'created_at']);
            $table->index('module');
        });
    }

    public function down(): void
    {
        Schema::connection('pgsql')->dropIfExists('activity_logs');
        Schema::connection('pgsql')->dropIfExists('user_attributes');
        Schema::connection('pgsql')->dropIfExists('user_roles');
        Schema::connection('pgsql')->dropIfExists('role_permission');
        Schema::connection('pgsql')->dropIfExists('permissions');
        Schema::connection('pgsql')->dropIfExists('roles');
    }
};
