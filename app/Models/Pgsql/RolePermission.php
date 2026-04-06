<?php

namespace App\Models\Pgsql;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RolePermission extends Model
{
    protected $connection = 'pgsql';
    protected $table      = 'permissions';

    protected $fillable = [
        'name',
        'display_name',
        'description',
        'group',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'role_permission',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }
}
