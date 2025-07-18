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
}
