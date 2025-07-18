<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Obtener todos los productos
     * GET /api/products
     */
    public function index()
    {
        $products = Product::with([
            'user:id,name,email,rating,colonia,municipio',
            'images' => function ($query) {
                $query->orderBy('sort_order');
            }
        ])->get();
        
        return response()->json([
            'message' => 'Lista de productos',
            'data' => $products,
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

    /**
     * Crear un nuevo producto
     * POST /api/products
     */

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'required|string|min:10|max:255',
            'location' => 'required|string|max:100',
            'status' => 'required|in:disponible,intercambiado',
            'wanted_item' => 'required|string|max:255',
            'images' => 'required|array|min:1|max:5', // Mínimo 1, máximo 5 imágenes
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Máximo 2MB por imagen
        ]);

        DB::beginTransaction();
        
        try {
            // Crear el producto
            $product = $request->user()->products()->create([
                'name' => $request->name,
                'description' => $request->description,
                'location' => $request->location,
                'status' => $request->status,
                'wanted_item' => $request->wanted_item,
            ]);

            // Procesar las imágenes
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                
                foreach ($images as $index => $image) {
                    // Generar nombre único para la imagen
                    $imageName = time() . '_' . $index . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    
                    // Guardar la imagen en storage/app/public/products
                    $imagePath = $image->storeAs('products', $imageName, 'public');
                    
                    // Crear registro en la base de datos
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'original_name' => $image->getClientOriginalName(),
                        'file_size' => $image->getSize(),
                        'mime_type' => $image->getMimeType(),
                        'is_primary' => $index === 0, // La primera imagen es la principal
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            // Cargar las relaciones para la respuesta
            $product->load([
                'user:id,name,email,rating,colonia,municipio',
                'images' => function ($query) {
                    $query->orderBy('sort_order');
                }
            ]);

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
     * Mostrar un producto específico
     * GET /api/products/{id}
     */

    public function show($id)
    {
        $product = Product::with([
            'user:id,name,email,rating,colonia,municipio',
            'images' => function ($query) {
                $query->orderBy('sort_order');
            }
        ])->findOrFail($id);

        return response()->json([
            'message' => 'Producto encontrado',
            'data' => $product,
        ]);
    }

    /**
     * Actualizar un producto
     * PUT /api/products/{id}
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        // Verificar que el producto pertenece al usuario autenticado
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No tienes permiso para actualizar este producto'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|min:3|max:50',
            'description' => 'sometimes|required|string|min:10|max:255',
            'location' => 'sometimes|required|string|max:100',
            'status' => 'sometimes|required|in:disponible,intercambiado',
            'wanted_item' => 'sometimes|required|string|max:255',
            'images' => 'sometimes|array|min:1|max:5',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        DB::beginTransaction();
        
        try {
            // Actualizar campos del producto
            $product->update($request->only([
                'name', 'description', 'location', 'status', 'wanted_item'
            ]));

            // Si se enviaron nuevas imágenes, eliminar las anteriores y crear las nuevas
            if ($request->hasFile('images')) {
                // Eliminar imágenes anteriores del storage
                foreach ($product->images as $image) {
                    Storage::disk('public')->delete($image->image_path);
                }
                
                // Eliminar registros de imágenes anteriores
                $product->images()->delete();
                
                // Procesar nuevas imágenes
                $images = $request->file('images');
                
                foreach ($images as $index => $image) {
                    $imageName = time() . '_' . $index . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('products', $imageName, 'public');
                    
                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_path' => $imagePath,
                        'original_name' => $image->getClientOriginalName(),
                        'file_size' => $image->getSize(),
                        'mime_type' => $image->getMimeType(),
                        'is_primary' => $index === 0,
                        'sort_order' => $index,
                    ]);
                }
            }

            DB::commit();

            $product->load([
                'user:id,name,email,rating,colonia,municipio',
                'images' => function ($query) {
                    $query->orderBy('sort_order');
                }
            ]);

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

    /**
     * Eliminar un producto
     * DELETE /api/products/{id}
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        
        // Verificar que el producto pertenece al usuario autenticado
        if ($product->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'No tienes permiso para eliminar este producto'
            ], 403);
        }

        DB::beginTransaction();
        
        try {
            // Eliminar imágenes del storage
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image->image_path);
            }
            
            // Eliminar el producto (las imágenes se eliminarán automáticamente por la foreign key cascade)
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
}
