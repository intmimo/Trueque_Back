<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;

Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    return Chat::where('id', $chatId)
        ->whereHas('users', fn ($q) => $q->where('user_id', $user->id))
        ->exists();
});