<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Obtener todos los productos disponibles
     * GET /api/products
     */
    public function index(Request $request)
{
    $excludeUserId = $request->query('exclude_user');

    $query = Product::where('status', 'disponible')
        ->with([
            'user:id,name,email,rating,colonia,municipio',
            'images' => function($query) {
                $query->orderBy('order');
            }
        ]);

    if ($excludeUserId) {
        $query->where('user_id', '!=', $excludeUserId);
    }

    $products = $query->get();

    return response()->json([
        'message' => 'Lista de productos disponibles',
        'data' => $products,
    ]);
}


    public function create()
    {
        return view('products.create');
    }

    /**
     * Crear un nuevo producto con imágenes
     * POST /api/products
     */
    public function store(Request $request)
    {
        // Validación
        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'required|string|min:10|max:255',
            'location' => 'required|string|max:100',
            'status' => 'required|in:disponible,intercambiado',
            'wanted_item' => 'required|string|max:255',
            'images' => 'nullable|array|max:10', // Máximo 10 imágenes
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Máximo 5MB por imagen
        ]);

        try {
            // Usar transacción para asegurar consistencia
            DB::beginTransaction();

            // Crear el producto asociado al usuario autenticado
            $productData = $request->only(['name', 'description', 'location', 'status', 'wanted_item']);
            $product = $request->user()->products()->create($productData);

            // Procesar las imágenes si existen
            if ($request->hasFile('images')) {
                $this->processImages($request->file('images'), $product);
            }

            // Cargar las relaciones
            $product->load([
                'user:id,name,email,rating,colonia,municipio',
                'images' => function($query) {
                    $query->orderBy('order');
                }
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Producto creado correctamente',
                'data' => $product,
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Error al crear el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Procesar y guardar las imágenes
     */
    private function processImages($images, $product)
    {
        foreach ($images as $index => $image) {
            // Generar nombre único para el archivo
            $fileName = time() . '_' . $index . '_' . uniqid() . '.' . $image->getClientOriginalExtension();

            // Guardar la imagen en storage/app/public/products
            $path = $image->storeAs('products', $fileName, 'public');

            // Crear registro en la base de datos
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'original_name' => $image->getClientOriginalName(),
                'order' => $index,
            ]);
        }
    }

    /**
     * Mostrar un producto específico
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $product = Product::with([
            'user:id,name,email,rating,colonia,municipio',
            'images' => function($query) {
                $query->orderBy('order');
            }
        ])->find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Producto no encontrado',
            ], 404);
        }

        return response()->json([
            'message' => 'Detalles del producto',
            'data' => $product,
        ]);
    }

    /**
     * Eliminar un producto y sus imágenes
     * DELETE /api/products/{id}
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Producto no encontrado',
            ], 404);
        }

        // Verificar que el usuario autenticado sea el dueño del producto
        if ($product->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'No tienes permisos para eliminar este producto',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Eliminar las imágenes del almacenamiento
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
                $image->delete();
            }

            // Eliminar el producto
            $product->delete();

            DB::commit();

            return response()->json([
                'message' => 'Producto eliminado correctamente',
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Error al eliminar el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener todos los productos de un usuario específico
     * GET /api/users/{userId}/products
     */
    public function getUserProducts($userId)
    {
        $products = Product::with([
            'user:id,name,email,rating,colonia,municipio',
            'images' => function($query) {
                $query->orderBy('order');
            }
        ])
        ->where('user_id', $userId)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'message' => 'Productos del usuario',
            'data' => $products,
        ]);
    }

    /**
     * Obtener todos los productos del usuario autenticado
     * GET /api/my-products
     */
    public function getMyProducts(Request $request)
    {
        $products = Product::with([
            'user:id,name,email,rating,colonia,municipio',
            'images' => function($query) {
                $query->orderBy('order');
            }
        ])
        ->where('user_id', $request->user()->id)
        ->orderBy('created_at', 'desc')
        ->get();

        return response()->json([
            'message' => 'Mis productos',
            'data' => $products,
        ]);
    }

    /**
     * Actualizar un producto existente
     * PUT/PATCH /api/products/{id}
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'message' => 'Producto no encontrado',
            ], 404);
        }

        // Verificar que el usuario autenticado sea el dueño del producto
        if ($product->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'No tienes permisos para editar este producto',
            ], 403);
        }

        // Validación
        $request->validate([
            'name' => 'sometimes|required|string|min:3|max:50',
            'description' => 'sometimes|required|string|min:10|max:255',
            'location' => 'sometimes|required|string|max:100',
            'status' => 'sometimes|required|in:disponible,intercambiado',
            'wanted_item' => 'sometimes|required|string|max:255',
            'images' => 'nullable|array|max:10', // Máximo 10 imágenes
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Máximo 5MB por imagen
            'remove_images' => 'nullable|array', // IDs de imágenes a eliminar
            'remove_images.*' => 'integer|exists:product_images,id',
        ]);

        try {
            DB::beginTransaction();

            // Actualizar solo los campos que se enviaron
            $updateData = $request->only(['name', 'description', 'location', 'status', 'wanted_item']);
            $product->update($updateData);

            // Eliminar imágenes específicas si se solicita
            if ($request->has('remove_images')) {
                $imagesToRemove = $product->images()->whereIn('id', $request->remove_images)->get();

                foreach ($imagesToRemove as $image) {
                    Storage::disk('public')->delete($image->image_path);
                    $image->delete();
                }
            }

            // Agregar nuevas imágenes si existen
            if ($request->hasFile('images')) {
                // Obtener el orden más alto actual para continuar la secuencia
                $maxOrder = $product->images()->max('order') ?? -1;

                foreach ($request->file('images') as $index => $image) {
                    $fileName = time() . '_' . ($maxOrder + $index + 1) . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $path = $image->storeAs('products', $fileName, 'public');

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $path,
                        'original_name' => $image->getClientOriginalName(),
                        'order' => $maxOrder + $index + 1,
                    ]);
                }
            }

            // Cargar las relaciones actualizadas
            $product->load([
                'user:id,name,email,rating,colonia,municipio',
                'images' => function($query) {
                    $query->orderBy('order');
                }
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Producto actualizado correctamente',
                'data' => $product,
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Error al actualizar el producto',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
