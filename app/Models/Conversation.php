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
        'assigned_by',
        'status',
        'unread_count',
        'last_message_id',
        'last_message_preview',
        'last_message_at',
        'first_message_at',
        'resolved_at',
        'bot_stopped',
        'assignment_history',
        'is_sent_to_crm',
        'crm_lead_id',
        'sent_to_crm_at',
    ];

    protected $casts = [
        'assignment_history' => 'array',
        'is_sent_to_crm' => 'boolean',
        'sent_to_crm_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(Employee::class, 'assigned_tenant_user_id');
    }

    public function assignedByUser()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'conversation_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'tenant_id');
    }

    public function getFormattedIdAttribute()
    {
        if (!isset($this->attributes['tenant_rank'])) {
            // Calculate sequence number for this specific company (tenant)
            $this->attributes['tenant_rank'] = static::where('tenant_id', $this->tenant_id)
                ->where('id', '<=', $this->id)
                ->count();
        }
        
        return 'CONV-' . str_pad($this->attributes['tenant_rank'], 4, '0', STR_PAD_LEFT);
    }
}
