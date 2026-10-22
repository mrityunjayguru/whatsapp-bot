<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'employee_code',
        'first_name',
        'last_name',
        'display_name',
        'email',
        'mobile_number',
        'password_hash',
        'profile_photo',
        'designation',
        'department',
        'role',
        'role_id',
        'can_assign_chats',
        'status',
        'last_login_at',
        'is_online',
        'assigned_conversation_count',
        'resolved_conversation_count',
        'created_by'
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'is_online' => 'boolean',
        'can_assign_chats' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customRole()
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    /**
     * Check if employee has permission to assign/transfer chats
     */
    public function canAssignChats(): bool
    {
        if ($this->role === 'ADMIN') {
            return true;
        }

        return (bool) $this->can_assign_chats;
    }

    /**
     * Check if employee has a given permission
     */
    public function hasPermission(string $permissionName): bool
    {
        // Admin always has all permissions
        if ($this->role === 'ADMIN') {
            return true;
        }

        // Specifically check chat assignment/transfer permission
        if ($permissionName === 'conversations.assign') {
            return (bool) $this->can_assign_chats;
        }

        if ($this->customRole) {
            return $this->customRole->hasPermission($permissionName);
        }

        // Fallback for default enum roles if customRole is not set
        if ($this->role === 'MANAGER') {
            // Manager gets view and edit access across all main modules except critical settings
            return !in_array($permissionName, ['settings.manage', 'roles.manage'], true);
        }

        // Regular Employee default permissions
        $employeeAllowed = [
            'conversations.view',
            'conversations.reply',
            'conversations.status',
            'contacts.view',
            'faqs.view',
        ];

        return in_array($permissionName, $employeeAllowed, true);
    }
}

