<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function designations()
    {
        return $this->hasMany(Designation::class, 'department_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'department', 'name')
            ->where('tenant_id', $this->tenant_id);
    }
}
