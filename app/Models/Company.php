<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = [
        'name',
        'contact_email',
        'contact_number',
        'widget_token',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function conversations()
    {
        // Conversation's tenant_id column IS the company id - no schema
        // duplication, just a meaningful relationship name on top of it.
        return $this->hasMany(Conversation::class, 'tenant_id');
    }
}
