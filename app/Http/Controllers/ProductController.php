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
        $products = Product::with('user:id,name,email,rating,colonia,municipio')->get();
        
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
        ]);

        // Crear el producto asociado al usuario autenticado
        $product = $request->user()->products()->create($request->all());

        // Cargar la relación del usuario
        $product->load('user:id,name,email,rating,colonia,municipio');

        return response()->json([
            'message' => 'Producto creado correctamente',
            'data' => $product,
        ], 201);
    }
}
