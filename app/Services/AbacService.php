<?php

namespace App\Services;

use App\Models\Pgsql\User;
use App\Models\Pgsql\UserAttribute;
use Illuminate\Http\Request;

/**
 * AbacService — Attribute-Based Access Control
 *
 * ABAC mengevaluasi akses berdasarkan:
 * 1. Subject attributes  : atribut user (divisi, area_kebun, shift, dll)
 * 2. Object attributes   : atribut resource yang diakses
 * 3. Environment attrs   : waktu, IP, lokasi
 * 4. Action              : permission yang dibutuhkan
 */
class AbacService
{
    public function __construct(protected Request $request) {}

    // ================================================================
    // CORE CHECK
    // ================================================================

    /**
     * Periksa apakah user dapat melakukan aksi tertentu.
     *
     * @param User   $user       User yang mengakses
     * @param string $permission Permission yang dibutuhkan (e.g. 'tanaman.view')
     * @param array  $context    Konteks resource (optional, untuk fine-grained access)
     */
    public function can(User $user, string $permission, array $context = []): bool
    {
        // 1. User harus aktif
        if (!$user->is_active) {
            return false;
        }

        // 2. Cek permission dasar dari role
        if (!$user->hasPermission($permission)) {
            return false;
        }

        // 3. Terapkan policy ABAC berdasarkan konteks resource
        return $this->evaluatePolicy($user, $permission, $context);
    }

    // ================================================================
    // POLICY EVALUATION
    // ================================================================

    /**
     * Evaluasi policy ABAC berdasarkan atribut user dan konteks resource.
     */
    protected function evaluatePolicy(User $user, string $permission, array $context): bool
    {
        // Jika admin, lewati semua policy ABAC
        if ($user->isAdministrator()) {
            return true;
        }

        $userAttrs = $user->getAbacAttributes();

        // Policy berdasarkan modul
        return match(true) {
            str_starts_with($permission, 'peta_kebun') => $this->checkKebunPolicy($user, $userAttrs, $context),
            str_starts_with($permission, 'lab_qa')     => $this->checkLabQaPolicy($user, $userAttrs, $context),
            str_starts_with($permission, 'tanaman')    => $this->checkTanamanPolicy($user, $userAttrs, $context),
            default                                    => true,
        };
    }

    /**
     * Policy akses Peta Kebun — berdasarkan area_kebun user.
     */
    protected function checkKebunPolicy(User $user, array $attrs, array $context): bool
    {
        // Jika resource membutuhkan area tertentu
        if (isset($context['area_kebun'])) {
            $userArea = $attrs[UserAttribute::KEY_AREA_KEBUN] ?? null;

            // User tanpa area_kebun yang di-set bisa akses semua (Manajer)
            if ($userArea === null) {
                return $user->hasRole('Manajer') || $user->hasRole('Administrator');
            }

            // User dengan area tertentu hanya bisa akses areanya
            return $userArea === $context['area_kebun'];
        }

        return true;
    }

    /**
     * Policy akses Lab QA — berdasarkan divisi user.
     */
    protected function checkLabQaPolicy(User $user, array $attrs, array $context): bool
    {
        // Approve hanya untuk role Supervisor ke atas
        if (str_ends_with($context['action'] ?? '', '.approve')) {
            return $user->hasRole('Supervisor') || $user->hasRole('Manajer') || $user->isAdministrator();
        }

        return true;
    }

    /**
     * Policy akses Data Tanaman — berdasarkan divisi.
     */
    protected function checkTanamanPolicy(User $user, array $attrs, array $context): bool
    {
        // Delete hanya untuk Manajer ke atas
        if (str_ends_with($permission ?? '', '.delete')) {
            return $user->hasRole('Manajer') || $user->isAdministrator();
        }

        return true;
    }

    // ================================================================
    // ENVIRONMENT POLICY
    // ================================================================

    /**
     * Cek batasan berbasis waktu/shift.
     * Bisa dikembangkan: akses hanya saat jam kerja, dll.
     */
    public function checkEnvironmentPolicy(User $user): bool
    {
        // Contoh: user shift-pagi hanya bisa akses input data 06:00-14:00
        $userShift = $user->getAbacAttributes()[UserAttribute::KEY_SHIFT] ?? null;

        if ($userShift === null) {
            return true; // Tidak ada batasan shift
        }

        $hour = (int) now()->format('H');

        return match($userShift) {
            'pagi'   => $hour >= 6 && $hour < 14,
            'siang'  => $hour >= 14 && $hour < 22,
            'malam'  => $hour >= 22 || $hour < 6,
            default  => true,
        };
    }

    // ================================================================
    // HELPER
    // ================================================================

    /**
     * Kembalikan daftar permission yang dimiliki user sebagai array.
     */
    public function getUserPermissions(User $user): array
    {
        if (!$user->is_active) {
            return [];
        }

        $permissions = [];
        foreach ($user->roles as $role) {
            foreach ($role->permissions as $permission) {
                $permissions[] = $permission->name;
            }
        }

        return array_values(array_unique($permissions));
    }

    /**
     * Kembalikan ringkasan akses user untuk frontend (Vue).
     */
    public function getUserAccessSummary(User $user): array
    {
        return [
            'permissions'   => $this->getUserPermissions($user),
            'roles'         => $user->roles->pluck('name')->toArray(),
            'attributes'    => $user->getAbacAttributes(),
            'is_admin'      => $user->isAdministrator(),
        ];
    }
}
