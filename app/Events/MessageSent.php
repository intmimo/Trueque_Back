<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class MessageSent implements ShouldBroadcast
{
    use SerializesModels;

    public $message;

    // Cuando se crea el evento, recibimos el mensaje y cargamos el usuario relacionado
    public function __construct(Message $message)
    {
        $this->message = $message->load('user');
    }

    // Canal de broadcasting (un canal por chat, ejemplo: chat.1)
    public function broadcastOn()
    {
        return new Channel('chat.' . $this->message->chat_id);
    }
}
