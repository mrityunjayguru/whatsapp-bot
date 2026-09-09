<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'tenant_id',
        'whatsapp_phone_number_id',
        'contact_id',
        'title',
        'assigned_tenant_user_id',
        'status',
        'unread_count',
        'last_message_id',
        'last_message_preview',
        'last_message_at',
        'first_message_at',
        'resolved_at',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_tenant_user_id');
    }
}
