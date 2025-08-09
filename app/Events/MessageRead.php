<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $chatId,
        public int $readerId,
        public array $messageIds
    ) {}

    public function broadcastOn()
    {
        return new PrivateChannel("chat.{$this->chatId}");
    }

    public function broadcastAs()
    {
        return 'message.read';
    }

    public function broadcastWith()
    {
        return [
            'chat_id'     => $this->chatId,
            'reader_id'   => $this->readerId,
            'message_ids' => $this->messageIds,
        ];
    }
}
