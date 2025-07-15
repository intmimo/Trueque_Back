<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    // Un chat tiene muchos usuarios (relación muchos a muchos)
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    // Un chat tiene muchos mensajes
    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
