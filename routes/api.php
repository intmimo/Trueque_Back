<?php

use Illuminate\Http\Request;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Api\ChatController; // 👈 Importación necesaria

    Route::get('/users/{id}', [AuthController::class, 'showUserProfile']);

// Rutas de Broadcast para canales privados
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Rutas públicas
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/users/{id}/likes', [LikeController::class, 'getUserLikes']);
Route::get('/users/{id}/liked-by-others', [LikeController::class, 'getUserLikedByOthers']);

// Productos (público)
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('/users/{userId}/products', [ProductController::class, 'getUserProducts']);


// Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {

    // Perfil de usuario
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);


    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);


    // Likes
    Route::post('/products/{id}/like', [LikeController::class, 'likeProduct']);
    Route::delete('/products/{id}/unlike', [LikeController::class, 'unlikeProduct']);

        // Rating
    Route::post('/rate/{toUserId}', [RatingController::class, 'rateUser']);
    Route::get('/rating/{toUserId}', [RatingController::class, 'getAverageRating']);

    // Usuario autenticado (test)
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Productos (protegido)
    Route::post('/products', [ProductController::class, 'store']);     // Crear producto
    Route::delete('/products/{id}', [ProductController::class, 'destroy']); // Eliminar producto
    Route::get('/my-products', [ProductController::class, 'getMyProducts']); // Mis productos
    Route::put('/products/{id}', [ProductController::class, 'update']);  // Actualizar producto
    Route::patch('/products/{id}', [ProductController::class, 'update']); // Actualizar producto

    // 🔥 Chat
    Route::post('/chats/start', [ChatController::class, 'startChat']);
    Route::post('/chats/{id}/send', [ChatController::class, 'sendMessage']);
    Route::get('/chats', [ChatController::class, 'listChats']);
    Route::get('/chats/{id}/messages', [ChatController::class, 'getMessages']);
    Route::get('/chats/with/{userId}', [ChatController::class, 'getChatWith']);
});
