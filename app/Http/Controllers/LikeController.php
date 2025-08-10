<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

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

            $product = Product::findOrFail($productId);

            if ($product->user_id === $user->id) {
                return response()->json([
                    'message' => 'No puedes dar like a tu propio producto'
                ], 400);
            }

            if ($user->hasLiked($productId)) {
                return response()->json([
                    'message' => 'Ya has dado like a este producto'
                ], 400);
            }

            Like::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            return response()->json([
                'message' => 'Like agregado exitosamente',
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    // Contar likes reales en BD
                    'total_likes' => Like::where('product_id', $product->id)->count()
                ]
            ], 201);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
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
            $product = Product::findOrFail($productId);

            $like = Like::where('user_id', $user->id)
                        ->where('product_id', $productId)
                        ->first();

            if (!$like) {
                return response()->json([
                    'message' => 'No has dado like a este producto'
                ], 400);
            }

            $like->delete();

            return response()->json([
                'message' => 'Like eliminado exitosamente',
                'product' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    // Contar likes reales en BD
                    'total_likes' => Like::where('product_id', $product->id)->count()
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error interno del servidor',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
