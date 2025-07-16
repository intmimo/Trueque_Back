<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ChatController; // 👈 Importación necesaria

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {

    // Perfil de usuario
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Usuario autenticado (test)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // PRODUCTS
    // Ruta para crear un producto 
    Route::post('/products', [ProductController::class, 'store']);
    // Ruta para listar productos 
    Route::get('/products', [ProductController::class, 'index']);
    // 🔥 Sistema de chat
    Route::post('/chats/start', [ChatController::class, 'startChat']);
    Route::post('/chats/{id}/send', [ChatController::class, 'sendMessage']);
    Route::get('/chats', [ChatController::class, 'listChats']);
    Route::get('/chats/{id}/messages', [ChatController::class, 'getMessages']);
});
