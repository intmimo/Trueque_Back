<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    // Estos campos se pueden llenar desde un controlador
    protected $fillable = ['chat_id', 'user_id', 'content', 'read_at', 'image_path'];

    // Cada mensaje pertenece a un chat
    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    // Cada mensaje fue enviado por un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
