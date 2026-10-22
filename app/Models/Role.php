<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'role_id');
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permissionName): bool
    {
        // Admin role has all permissions by default
        if (strtoupper($this->slug) === 'ADMIN' || strtoupper($this->name) === 'ADMIN') {
            return true;
        }

        return $this->permissions->contains('name', $permissionName);
    }
}
