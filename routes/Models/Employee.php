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
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
