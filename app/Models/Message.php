<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'tenant_id',
        'channel',
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

    /**
     * True for the SYSTEM notices ConversationController::assign() sends
     * when a human agent joins, leaves, or hands a conversation back to
     * the bot - as opposed to an ordinary SYSTEM-tagged escalation/
     * fallback reply (e.g. "I'm still in training..."), which is a real
     * answer the visitor should see in a normal chat bubble, not a
     * divider. Both share sender_type === 'SYSTEM', so this is the one
     * place that tells them apart - by exact, backend-controlled
     * wording (never tenant- or admin-editable text). Not its own
     * stored column: this is purely a widget presentation detail, not
     * worth a migration, and keeping the check in one place means the
     * widget history endpoint and the live broadcast event can't drift
     * out of sync on what counts as an event vs. a real reply.
     */
    public static function isConversationEvent(?string $senderType, ?string $text): bool
    {
        if ($senderType !== 'SYSTEM' || !$text) {
            return false;
        }
        return str_ends_with($text, 'joined conversation')
            || stripos($text, 'ended conversation') !== false
            || str_starts_with($text, 'AI Support is now assisting');
    }
}
