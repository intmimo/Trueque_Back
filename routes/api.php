<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ChatController; // 👈 Importación necesaria

// Rutas de Broadcast para canales privados
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users/{id}/likes', [LikeController::class, 'getUserLikes']);
Route::get('/users/{id}/liked-by-others', [LikeController::class, 'getUserLikedByOthers']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {

    // Perfil de usuario
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Sistema de likes
    Route::post('/products/{id}/like', [LikeController::class, 'likeProduct']);
    Route::delete('/products/{id}/unlike', [LikeController::class, 'unlikeProduct']);

    // Usuario autenticado (test)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // PRODUCTS
    Route::post('/products', [ProductController::class, 'store']); // Crear producto
    Route::get('/products', [ProductController::class, 'index']); // Listar productos

    // 🔥 Sistema de chat
    Route::post('/chats/start', [ChatController::class, 'startChat']);
    Route::post('/chats/{id}/send', [ChatController::class, 'sendMessage']);
    Route::get('/chats', [ChatController::class, 'listChats']);
    Route::get('/chats/{id}/messages', [ChatController::class, 'getMessages']);
    Route::get('/chats/with/{userId}', [ChatController::class, 'getChatWith']);
});
