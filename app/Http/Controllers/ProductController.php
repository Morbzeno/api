<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category', 'brand')->get();
        return response()->json($products->toArray(), 200);
    }
    
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'name' => 'required',
            'brand_id' => 'required',
            'sell_price' => 'required',
            'buy_price' => 'required',
            'bar_code' => 'required',
            'stock' => 'required',
            'description' => 'required',
            'state' => 'required',
            'wholesare_price' => 'required',
          //  'image' => 'required'
        ]);
    
        $products = new Product();
        $products->category_id = $request->category_id;
        $products->name = $request->name;
        $products->brand_id = $request->brand_id;
        $products->sell_price = $request->sell_price;
        $products->buy_price = $request->buy_price; // Se agregó
        $products->bar_code = $request->bar_code;
        $products->stock = $request->stock;
        $products->description = $request->description;
        $products->state = $request->state;
        $products->wholesare_price = $request->wholesare_price;
    
        // Guardar la imagen si se sube
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('images', 'public');
            $products->image = $imagePath;
        }
    
        $products->save();
    
        return response()->json([
            'message' => 'Producto insertado correctamente',
            'data' => $products
        ], 201);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $products = Product::find($id);
        return $products ? response()->json($products) : response()->json(['error' => 'producto no encontrado'], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $products = Product::find($id);
        if (!$products){
            return response()->json(['message' => 'producto no encontrado'], 400);
        }
        $products->delete();
        return response()->json(['message' => 'producto eliminado'], 200);
    }
}
