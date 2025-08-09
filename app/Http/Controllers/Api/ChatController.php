<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use App\Events\MessageSent;
use App\Events\MessageRead; // ✅ para palomitas

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
            'user_id'   => auth()->id(),
            'content'   => $request->content,
            'image_path'=> $imagePath
        ]);

        // Cargar relación de usuario para el broadcast
        $message->load('user');

        // 📢 Broadcasting en tiempo real a canal privado
        broadcast(new MessageSent($message))->toOthers();

        return response()->json([
            'id'         => $message->id,
            'chat_id'    => $message->chat_id,
            'user_id'    => $message->user_id,
            'content'    => $message->content,
            'image_path' => $imagePath ? asset('storage/' . $imagePath) : null,
            'created_at' => $message->created_at,  // ✅ útil para hora
            'read_at'    => $message->read_at,     // ✅ útil para palomitas
            'user' => [
                'id'   => $message->user->id,
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
                'id'         => $message->id,
                'chat_id'    => $message->chat_id,
                'user_id'    => $message->user_id,
                'content'    => $message->content,
                'created_at' => $message->created_at,
                'read_at'    => $message->read_at, // ✅
                'image_path' => $message->image_path ? asset('storage/' . $message->image_path) : null,
                'user' => [
                    'id'   => $message->user->id,
                    'name' => $message->user->name,
                ]
            ];
        });

        return response()->json(['data' => $filtered]);
    }

    // ✅ Marcar como leídos (palomitas)
    public function markAsRead($chatId)
    {
        $user = auth()->user();

        // IDs de mensajes del chat que NO son del usuario y aún no están leídos
        $ids = Message::where('chat_id', $chatId)
            ->whereNull('read_at')
            ->where('user_id', '<>', $user->id)
            ->pluck('id');

        if ($ids->isEmpty()) {
            return response()->json(['read_ids' => []], 200);
        }

        Message::whereIn('id', $ids)->update(['read_at' => now()]);

        // Notifica a los demás participantes del chat
        broadcast(new MessageRead($chatId, $user->id, $ids->toArray()))->toOthers();

        return response()->json(['read_ids' => $ids], 200);
    }

    // 🗑️ Eliminar mensaje (solo autor y perteneciente al chat)
    public function destroyMessage($id)
    {
        $message = Message::with('chat.users')->findOrFail($id);
        $userId = auth()->id();

        // Debe pertenecer al chat y ser autor del mensaje
        $belongs = $message->chat->users->contains(fn($u) => $u->id === $userId);
        if (!$belongs || $message->user_id !== $userId) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $message->delete(); // hard delete

        return response()->json(['status' => 'ok']);
    }

    // 🗑️ Eliminar chat completo (solo participantes)
    public function destroyChat($id)
    {
        $userId = auth()->id();
        $chat = Chat::with('users')->findOrFail($id);

        // Solo un usuario que pertenece al chat puede borrarlo
        $belongs = $chat->users->contains(fn($u) => $u->id === $userId);
        if (!$belongs) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        // Borrado explícito y seguro
        $chat->messages()->delete(); // si tu FK ya tiene cascade, esto es redundante pero seguro
        $chat->users()->detach();
        $chat->delete();

        return response()->json(['status' => 'ok']);
    }
}
