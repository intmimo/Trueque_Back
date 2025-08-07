<?php

use Illuminate\Http\Request;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ChatController; // 👈 Importación necesaria

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users/{id}/likes', [LikeController::class, 'getUserLikes']);
Route::get('/users/{id}/liked-by-others', [LikeController::class, 'getUserLikedByOthers']);

// Ruta pública para ver productos
Route::get('/products', [ProductController::class, 'index']);
// Ruta pública para ver un producto específico
Route::get('/products/{id}', [ProductController::class, 'show']);
// Ruta para obtener productos de un usuario específico
Route::get('/users/{userId}/products', [ProductController::class, 'getUserProducts']);

// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {

    // Perfil de usuario
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rating
    Route::post('/rate/{userId}', [RatingController::class, 'rateUser']);
    Route::get('/rating/{userId}', [RatingController::class, 'getAverageRating']);

     // Sistema de likes
    Route::post('/products/{id}/like', [LikeController::class, 'likeProduct']);
    Route::delete('/products/{id}/unlike', [LikeController::class, 'unlikeProduct']);

    // Usuario autenticado (test)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // PRODUCTS
    // Ruta para crear un producto
    Route::post('/products', [ProductController::class, 'store']);
    // Ruta para eliminar un producto
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    //Obtener todos los productos del usuario autenticado
    Route::get('/my-products', [ProductController::class, 'getMyProducts']);
    // Actualizar un producto
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::patch('/products/{id}', [ProductController::class, 'update']);

    // 🔥 Sistema de chat
    Route::post('/chats/start', [ChatController::class, 'startChat']);
    Route::post('/chats/{id}/send', [ChatController::class, 'sendMessage']);
    Route::get('/chats', [ChatController::class, 'listChats']);
    Route::get('/chats/{id}/messages', [ChatController::class, 'getMessages']);
    Route::get('/chats/with/{userId}', [ChatController::class, 'getChatWith']);
});
