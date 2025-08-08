<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use App\Events\MessageSent;

class ChatController extends Controller
{
    // Inicia un chat entre dos usuarios
    public function startChat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $authUserId = auth()->id();
        $targetUserId = $request->user_id;

        // Buscar si ya existe un chat entre ambos
        $chat = Chat::whereHas('users', fn($q) => $q->where('user_id', $authUserId))
            ->whereHas('users', fn($q) => $q->where('user_id', $targetUserId))
            ->first();

        // Si no existe, crearlo y asociar usuarios
        if (!$chat) {
            $chat = Chat::create();
            $chat->users()->attach([$authUserId, $targetUserId]);
        }

        return response()->json([
            'chat_id' => $chat->id,
            'users' => $chat->users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])
        ]);
    }

    // Obtiene o crea un chat con un usuario específico
    public function getChatWith($userId)
    {
        $authUserId = auth()->id();

        $chat = Chat::whereHas('users', fn($q) => $q->where('user_id', $authUserId))
            ->whereHas('users', fn($q) => $q->where('user_id', $userId))
            ->with('users')
            ->first();

        if (!$chat) {
            $chat = Chat::create();
            $chat->users()->attach([$authUserId, $userId]);
        }

        return response()->json([
            'chat_id' => $chat->id,
            'users' => $chat->users->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
            ])
        ]);
    }

    // Envía un mensaje en un chat existente
    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        $chat = Chat::findOrFail($id);

        // Validar que el usuario pertenece al chat
        if (!$chat->users->contains(auth()->id())) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $imagePath = null;

        // Subir imagen si viene en la petición
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat_images', 'public');
        }

        // Crear mensaje
        $message = $chat->messages()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'image_path' => $imagePath
        ]);

        // Cargar relación de usuario para el broadcast
        $message->load('user');

        // 📢 Broadcasting en tiempo real a canal privado
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id' => $message->id,
            'chat_id' => $message->chat_id,
            'user_id' => $message->user_id,
            'content' => $message->content,
            'image_path' => $imagePath ? asset('storage/' . $imagePath) : null,
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
            ]
        ]);
    }

    // Lista los chats del usuario autenticado
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

    // Obtiene todos los mensajes de un chat
    public function getMessages($id)
    {
        $chat = Chat::findOrFail($id);

        // Validar que el usuario pertenece al chat
        if (!$chat->users->contains(auth()->id())) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $messages = $chat->messages()->with('user')->orderBy('created_at', 'asc')->get();

        $filtered = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'chat_id' => $message->chat_id,
                'user_id' => $message->user_id,
                'content' => $message->content,
                'created_at' => $message->created_at,
                'image_path' => $message->image_path ? asset('storage/' . $message->image_path) : null,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                ]
            ];
        });

        return response()->json(['data' => $filtered]);
    }
}
