<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use App\Events\MessageSent; // Importamos el evento para emitir notificaciones

class ChatController extends Controller
{
    // POST /api/chats/start
    // Inicia un chat entre dos usuarios (o devuelve el que ya existe)
    public function startChat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $authUserId = auth()->id();
        $targetUserId = $request->user_id;

        // Busco si ya existe un chat entre ambos usuarios
        $chat = Chat::whereHas('users', fn($q) => $q->where('user_id', $authUserId))
            ->whereHas('users', fn($q) => $q->where('user_id', $targetUserId))
            ->first();

        // Si no existe, creo un nuevo chat y los vinculo
        if (!$chat) {
            $chat = Chat::create();
            $chat->users()->attach([$authUserId, $targetUserId]);
        }

        return response()->json($chat->load('users'));
    }

    // POST /api/chats/{id}/send
    // Envía un mensaje en un chat si el usuario pertenece al chat
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $chat = Chat::findOrFail($id);

        // Verificamos que el usuario autenticado esté en el chat
        if (!$chat->users->contains(auth()->id())) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Guardamos el mensaje en la base de datos
        $message = $chat->messages()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
        ]);

        // Disparamos el evento para notificación en tiempo real
        event(new MessageSent($message));

        return response()->json($message);
    }

    // GET /api/chats
    // Lista los chats del usuario con su último mensaje y el otro participante
    public function listChats()
    {
        $chats = Chat::whereHas('users', fn($q) => $q->where('user_id', auth()->id()))
            ->with([
                'users' => fn($q) => $q->where('user_id', '!=', auth()->id()),
                'messages' => fn($q) => $q->latest()->limit(1)
            ])
            ->get();

        return response()->json($chats);
    }

    // GET /api/chats/{id}/messages
    // Retorna todos los mensajes del chat, paginados
    public function getMessages($id)
    {
        $chat = Chat::findOrFail($id);

        if (!$chat->users->contains(auth()->id())) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $messages = $chat->messages()->with('user')->latest()->paginate(20);

        return response()->json($messages);
    }
}
