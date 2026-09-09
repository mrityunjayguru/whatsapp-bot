<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'contact_id',
        'whatsapp_phone_number_id',
        'meta_message_id',
        'reply_to_meta_message_id',
        'message_type',
        'direction',
        'sender_type',
        'tenant_user_id',
        'message_text',
        'media_id',
        'media_url',
        'mime_type',
        'file_name',
        'caption',
        'status',
        'failure_reason',
        'is_deleted',
        'is_forwarded',
        'is_starred',
        'is_edited',
        'sent_at',
        'delivered_at',
        'read_at',
    ];
}
