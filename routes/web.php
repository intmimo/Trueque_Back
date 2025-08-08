<?php

use App\Events\MessageSent;
use App\Models\Message;

Route::get('/test-message', function () {
    // Crear un mensaje de prueba (asegúrate que el chat_id exista)
    $message = Message::create([
        'chat_id' => 1, // ID de un chat existente
        'user_id' => 1, // ID de un usuario existente
        'content' => 'Mensaje de prueba en tiempo real 🚀',
        'image_path' => null,
    ]);

    // Disparar el evento
    broadcast(new MessageSent($message))->toOthers();

    return 'Mensaje enviado y evento emitido';
});
