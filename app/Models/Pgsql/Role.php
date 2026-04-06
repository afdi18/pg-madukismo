<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Role extends Model
{
    use HasFactory;

    protected $connection = 'pgsql';
    protected $table      = 'roles';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'is_system',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    // ================================================================
    // RELATIONSHIPS
    // ================================================================

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_roles', 'role_id', 'user_id')
                    ->withPivot('area_kebun', 'divisi', 'assigned_at', 'assigned_by')
                    ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            RolePermission::class,
            'role_permission',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    // ================================================================
    // ABAC HELPERS
    // ================================================================

    public function hasPermission(string $permissionName): bool
    {
        return $this->permissions->contains('name', $permissionName);
    }

    public function syncPermissions(array $permissionNames): void
    {
        $permissionIds = RolePermission::whereIn('name', $permissionNames)
                            ->pluck('id')
                            ->toArray();

        $this->permissions()->sync($permissionIds);
    }

    // ================================================================
    // SCOPES
    // ================================================================

    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }
}
