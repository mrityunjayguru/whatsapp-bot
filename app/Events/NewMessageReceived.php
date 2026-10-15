<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessageReceived implements \Illuminate\Contracts\Broadcasting\ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Message $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('messages'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.received';
    }

    public function broadcastWith(): array
    {
        $conversation = $this->message->conversation_id
            ? \App\Models\Conversation::find($this->message->conversation_id)
            : null;

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'direction' => $this->message->direction,
            'sender_type' => $this->message->sender_type,
            'tenant_id' => $this->message->tenant_id,
            'assigned_tenant_user_id' => $conversation?->assigned_tenant_user_id,
            'message_preview' => mb_substr(strip_tags($this->message->message_text ?? ''), 0, 80),
        ];
    }
}
