<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'tenant_id',
        'channel',
        'widget_token',
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
        'bot_stopped',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedUser()
    {
        // Despite the column's name (assigned_tenant_user_id), this app
        // actually assigns conversations to Employee records, not User
        // accounts - confirmed by routes/web.php's dashboard route,
        // which already looks up Employee::where('id', ...) against this
        // same column. Employee has its own id space, separate from
        // users.id - pointing this at User (as it was before) meant
        // ->assignedUser resolved to the wrong record whenever an
        // assignment existed, which is part of why "Assigned" always
        // showed as empty even after assigning someone.
        return $this->belongsTo(Employee::class, 'assigned_tenant_user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'tenant_id');
    }
}
