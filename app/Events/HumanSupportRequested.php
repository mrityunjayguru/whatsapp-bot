<?php

namespace App\Events;

use App\Models\Conversation;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired the instant a customer explicitly asks for a human - on the
 * widget (see widget_engine.py's _wants_human()) or on WhatsApp (see
 * bot_engine.py's human_handoff intent) - not just when the bot fails
 * to find an answer. Broadcasts on a single, global public channel that
 * any authenticated page in the CRM can listen on, so an agent sees it
 * immediately no matter which page they're on - unlike NewMessage,
 * which only reaches someone already viewing that specific
 * conversation's page.
 *
 * Public channel, same reasoning as NewMessage: no per-company auth
 * scoping exists yet for this specific alert, so for now every
 * logged-in CRM user sees every request. Once that scoping is added,
 * this would become company.{id}.human-support instead.
 */
class HumanSupportRequested implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public string $companyName,
        public string $messageText,
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('human-support'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'conversation_id' => $this->conversationId,
            'company_name' => $this->companyName,
            'message_text' => $this->messageText,
            'url' => route('conversations.show', $this->conversationId),
        ];
    }
}
