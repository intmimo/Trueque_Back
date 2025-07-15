<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::all();
        return response()->json([
            'message' => 'Lista de productos',
            'data' => $products,
        ]);
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|min:3|max:50',
            'description' => 'required|string|min:10|max:50',
            'location' => 'required|string|max:100',
            'publication_date' => 'required|date',
            'status' => 'required|in:disponible,intercambiado',
            'wanted_item' => 'required|string|max:255',
        ]);

        $product = Product::create($request->all());
        return response()->json([
            'message' => 'Producto creado correctamente',
            'data' => $product,
        ], 201);
    }
}
