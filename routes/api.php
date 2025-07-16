<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;


// Rutas públicas (no requieren autenticación)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rutas protegidas (requieren autenticación)
Route::middleware('auth:sanctum')->group(function () {

    // Perfil de usuario
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile/update', [AuthController::class, 'updateProfile']);

    // Logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ruta de ejemplo para obtener usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // PRODUCTS
    // Ruta para crear un producto 
    Route::post('/products', [ProductController::class, 'store']);
    // Ruta para listar productos 
    Route::get('/products', [ProductController::class, 'index']);
});


