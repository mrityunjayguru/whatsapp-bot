<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CloseIdleConversations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'conversations:close-idle';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close conversations where the user has not replied for 10 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $conversations = \App\Models\Conversation::whereNotIn('status', ['RESOLVED', 'CLOSED'])
            ->where('last_message_at', '<', now()->subMinutes(10))
            ->get();

        foreach ($conversations as $conversation) {
            $conversation->update([
                'status' => 'CLOSED',
                'resolved_at' => now(),
            ]);

            $msgText = "Ended conversation";
            $contact = $conversation->contact;

            if ($conversation->channel === 'web_widget') {
                $message = \App\Models\Message::create([
                    'tenant_id' => $conversation->tenant_id,
                    'channel' => 'web_widget',
                    'conversation_id' => $conversation->id,
                    'contact_id' => $contact->id,
                    'whatsapp_phone_number_id' => null,
                    'meta_message_id' => 'web_' . (string) \Illuminate\Support\Str::uuid(),
                    'message_type' => 'TEXT',
                    'direction' => 'OUTBOUND',
                    'sender_type' => 'SYSTEM',
                    'message_text' => $msgText,
                    'status' => 'SENT',
                    'sent_at' => now(),
                ]);
                event(new \App\Events\NewMessage($message));
            } else {
                try {
                    $whatsappService = app(\App\Services\WhatsAppService::class);
                    $whatsappResponse = $whatsappService->sendMessage($contact->phone_number, $msgText);
                    if (!empty($whatsappResponse) && isset($whatsappResponse['messages'][0]['id'])) {
                        $message = \App\Models\Message::create([
                            'tenant_id' => $conversation->tenant_id,
                            'conversation_id' => $conversation->id,
                            'contact_id' => $contact->id,
                            'whatsapp_phone_number_id' => $conversation->whatsapp_phone_number_id,
                            'meta_message_id' => $whatsappResponse['messages'][0]['id'],
                            'message_type' => 'TEXT',
                            'direction' => 'OUTBOUND',
                            'sender_type' => 'SYSTEM',
                            'message_text' => $msgText,
                            'status' => 'SENT',
                            'sent_at' => now(),
                        ]);
                        event(new \App\Events\NewMessage($message));
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to send status update message from auto-closer: ' . $e->getMessage());
                }
            }
        }
        
        $this->info("Closed " . $conversations->count() . " idle conversations.");
    }
}
