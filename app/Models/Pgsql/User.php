<?php

namespace App\Models\Pgsql;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * Gunakan koneksi PostgreSQL.
     */
    protected $connection = 'pgsql';
    protected $table      = 'users';

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
        'created_by',
        'avatar',
        'phone',
        'jabatan',
        'divisi',
        'area_kebun',
        'notes',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // ================================================================
    // RELATIONSHIPS
    // ================================================================

    /**
     * User memiliki banyak role (many-to-many).
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles', 'user_id', 'role_id')
                    ->withPivot('area_kebun', 'divisi', 'assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    /**
     * User memiliki atribut ABAC.
     */
    public function userAttributes(): HasMany
    {
        return $this->hasMany(UserAttribute::class, 'user_id');
    }

    /**
     * User yang membuat user ini.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ================================================================
    // ABAC HELPERS
    // ================================================================

    /**
     * Cek apakah user memiliki permission tertentu.
     * Menggunakan ABAC: role + attribute + context.
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->is_active) {
            return false;
        }

        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cek apakah user memiliki salah satu dari permission yang diberikan.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cek apakah user memiliki role tertentu.
     */
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    /**
     * Cek apakah user adalah Administrator.
     */
    public function isAdministrator(): bool
    {
        return $this->hasRole('Administrator');
    }

    /**
     * Ambil nilai atribut ABAC berdasarkan key.
     */
    public function getAbacAttributeValue(string $key): mixed
    {
        $attribute = $this->userAttributes()->where('attribute_key', $key)->first();
        return $attribute?->attribute_value;
    }

    /**
     * Ambil semua atribut ABAC sebagai array key-value.
     */
    public function getAbacAttributes(): array
    {
        return $this->userAttributes()
            ->pluck('attribute_value', 'attribute_key')
            ->toArray();
    }

    // ================================================================
    // SCOPES
    // ================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDivisi($query, string $divisi)
    {
        return $query->where('divisi', $divisi);
    }

    // ================================================================
    // ACCESSORS
    // ================================================================

    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        return asset('images/default-avatar.png');
    }

    /**
     * Override findForPassport untuk login dengan username.
     */
    public function findForPassport(string $username): ?User
    {
        return $this->where('username', $username)
                    ->where('is_active', true)
                    ->first();
    }
}
