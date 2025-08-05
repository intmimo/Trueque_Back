<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Chat;
use App\Models\Message;
use App\Events\MessageSent;

class ChatController extends Controller
{
    public function startChat(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $authUserId = auth()->id();
        $targetUserId = $request->user_id;

        $chat = Chat::whereHas('users', fn($q) => $q->where('user_id', $authUserId))
            ->whereHas('users', fn($q) => $q->where('user_id', $targetUserId))
            ->first();

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

    public function sendMessage(Request $request, $id)
    {
        $request->validate([
            'content' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        $chat = Chat::findOrFail($id);

        if (!$chat->users->contains(auth()->id())) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('chat_images', 'public');
        }

        $message = $chat->messages()->create([
            'user_id' => auth()->id(),
            'content' => $request->content,
            'image_path' => $imagePath
        ]);

        $message->load('user');

        event(new MessageSent($message));

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

    public function getMessages($id)
    {
        $chat = Chat::findOrFail($id);

        if (!$chat->users->contains(auth()->id())) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $messages = $chat->messages()->with('user')->latest()->get();

        $filtered = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'chat_id' => $message->chat_id,
                'user_id' => $message->user_id,
                'content' => $message->content,
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
