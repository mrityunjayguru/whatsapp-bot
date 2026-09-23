<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMessage implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Create a new event instance.
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        // For simplicity we will broadcast on a public channel named conversation.{id} 
        // to avoid complex auth logic right now, but usually it should be PrivateChannel
        return [
            new Channel('conversation.' . $this->message->conversation_id),
        ];
    }

    /**
     * Customize exactly what goes over the wire. Adds `sender_name` for
     * EMPLOYEE-sent messages (looked up from whichever Employee the
     * conversation is currently assigned to) - the widget uses this to
     * show who a visitor is actually talking to once a human takes over,
     * instead of an anonymous "bot"-styled bubble. No new column needed:
     * this is computed fresh at broadcast time, so it always reflects
     * whoever was assigned at the moment the message was sent.
     */
    public function broadcastWith(): array
    {
        $payload = $this->message->toArray();

        if ($this->message->sender_type === 'EMPLOYEE') {
            $conversation = \App\Models\Conversation::find($this->message->conversation_id);
            $employee = $conversation && $conversation->assigned_tenant_user_id
                ? \App\Models\Employee::find($conversation->assigned_tenant_user_id)
                : null;
            $payload['sender_name'] = $employee?->display_name;
        }

        return ['message' => $payload];
    }
}
