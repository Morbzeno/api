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
        $products = Product::with('category')->get();
        return response()->json($products->toArray(), 200);
    }
    
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required',
            'name' => 'required|string|max:50'
            // 'brand_id' => 'required|',
            // 'sell_price' => 'required|double',
            // 'buy_price' => 'required|double',
            // 'bar_code' => 'required',
            // 'stock' => 'required|number',
            // 'description' => 'required|string|max:200',
            // 'state' => 'required',
            // 'wholesare_price' => 'required|double',
            // 'image' =>  'required|array'
        ]);
        $products = new Product();
        $products->category_id = $request->category_id;
        $products->name = $request->name;
        // $products->brand_id = $request->brand_id;
        // $products->sell_price = $request->sell_price;
        // $products->bar_code = $request->bar_code;
        // $products->stock = $request->stock;
        // $products->description = $request->description;
        // $products->state = $request->state;
        // $products->wholesare_price = $request->wholesare_price;
        // $products->image = $request->image;
        $products->save();
        return response()->json([
            'message' => 'producto insertado correctamente',
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
