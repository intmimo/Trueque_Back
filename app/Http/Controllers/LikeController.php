<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LikeController extends Controller
{
    /**
     * Dar like a un producto
     * POST /api/products/{id}/like
     */
    public function likeProduct(Request $request, $productId)
    {
        try {
            $user = $request->user();

            // Verificar que el producto existe
            $product = Product::findOrFail($productId);

            // Verificar que el usuario no esté dando like a su propio producto
            if ($product->user_id === $user->id) {
                return response()->json([
                    'message' => 'No puedes dar like a tu propio producto'
                ], 400);
            }

            // Verificar si ya dio like
            if ($user->hasLiked($productId)) {
                return response()->json([
                    'message' => 'Ya has dado like a este producto'
                ], 400);
            }

            // Crear el like
            $like = Like::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            return response()->json([
                'message' => 'Like agregado exitosamente',
                'like' => $like,
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'total_likes' => $product->total_likes + 1
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quitar like de un producto
     * DELETE /api/products/{id}/unlike
     */
    public function unlikeProduct(Request $request, $productId)
    {
        try {
            $user = $request->user();

            // Verificar que el producto existe
            $product = Product::findOrFail($productId);

            // Buscar el like
            $like = Like::where('user_id', $user->id)
                    ->where('product_id', $productId)
                    ->first();

            if (!$like) {
                return response()->json([
                    'message' => 'No has dado like a este producto'
                ], 400);
            }

            // Eliminar el like
            $like->delete();

            return response()->json([
                'message' => 'Like eliminado exitosamente',
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'total_likes' => $product->total_likes - 1
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Producto no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar productos que el usuario ha likeado
     * GET /api/users/{id}/likes
     */
    public function getUserLikes($userId)
    {
        try {
            $user = User::findOrFail($userId);

            // Obtener productos likeados con información adicional
            $likedProducts = $user->likedProducts()
                                 ->with('user:id,name') // Incluir info del dueño del producto
                                 ->withPivot('created_at') // Incluir fecha del like
                                ->orderBy('pivot_created_at', 'desc')
                                ->get();

            return response()->json([
                'message' => 'Productos likeados obtenidos exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name
                ],
                'liked_products' => $likedProducts->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'description' => $product->description,
                        'owner' => $product->user,
                        'total_likes' => $product->total_likes,
                        'liked_at' => $product->pivot->created_at
                    ];
                }),
                'total_likes_given' => $likedProducts->count()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar likes recibidos en los productos del usuario
     * GET /api/users/{id}/liked-by-others
     */
    public function getUserLikedByOthers($userId)
    {
        try {
            $user = User::findOrFail($userId);

            // Obtener likes recibidos en los productos del usuario
            $likesReceived = Like::whereHas('product', function ($query) use ($userId) {
                                    $query->where('user_id', $userId);
                                })
                                ->with([
                                    'user:id,name', // Usuario que dio el like
                                    'product:id,name,description' // Producto likeado
                                ])
                                ->orderBy('created_at', 'desc')
                                ->get();

            return response()->json([
                'message' => 'Likes recibidos obtenidos exitosamente',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name
                ],
                'likes_received' => $likesReceived->map(function ($like) {
                    return [
                        'id' => $like->id,
                        'liked_by' => $like->user,
                        'product' => $like->product,
                        'liked_at' => $like->created_at
                    ];
                }),
                'total_likes_received' => $likesReceived->count()
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
