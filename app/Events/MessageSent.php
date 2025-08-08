<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Al instante
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // Cargamos relación del usuario para mandarla en el evento
        $this->message = $message->load('user');
    }

    public function broadcastOn()
    {
        // Canal privado del chat
        return new PrivateChannel('chat.' . $this->message->chat_id);
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastWith()
    {
        return [
            'id'         => $this->message->id,
            'chat_id'    => $this->message->chat_id,
            'user'       => [
                'id'   => $this->message->user->id,
                'name' => $this->message->user->name,
            ],
            'content'    => $this->message->content,
            'image_path' => $this->message->image_path,
            'created_at' => $this->message->created_at->toDateTimeString(),
        ];
    }
}
