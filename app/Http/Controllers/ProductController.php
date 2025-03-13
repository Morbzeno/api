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
        if (!$products){
            return response()->json(['message' => 'no se encuentran productos'], 400);
        }
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
            'bar_code' => '',
            'stock' => 'required',
            'description' => 'required',
            'state' => 'required',
            'wholesale_price' => 'required',
            'sku' => '',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048' // Validación de imágenes múltiples

        ]);
    
        $product = new Product();
        $product->category_id = $request->category_id;
        $product->name = $request->name;
        $product->brand_id = $request->brand_id;
        $product->sell_price = $request->sell_price;
        $product->buy_price = $request->buy_price;
        $product->bar_code = $request->bar_code;
        $product->stock = $request->stock;
        $product->description = $request->description;
        $product->state = $request->state;
        $product->sku = $request->sku;
        $product->wholesale_price = $request->wholesale_price;
        $product->save();
        // Procesar imágenes si se suben
        $imagenes = [];
        $contador=1;
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $nuevoNombre = 'product_' . $product->id . '.' . $contador . $image->extension();
                $ruta = $image->storeAs('images/product', $nuevoNombre, 'public');
                $rutaCompleta = asset('storage/' . $ruta);
                $imagenes[] = $rutaCompleta;
                $contador++;
            }
        }
    
        $product->image = $imagenes; // Guardar imágenes como un array
        $product->save();
    
        return response()->json([
            'message' => 'Producto insertado correctamente',
            'data' => $product
        ], 201);
    }
    
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $products = Product::find($id);
        if (!$products){
            return response()->json(['message' => 'producto no encontrado'], 400);
        }
        return $products ? response()->json($products) : response()->json(['error' => 'producto no encontrado'], 404);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::find($id);
        if (!$product){
            return response()->json(['message' => 'producto no encontrado'], 400);
        }
        $validation=$request->validate([
            'category_id' => '',
            'name' => '',
            'brand_id' => '',
            'sell_price' => '',
            'buy_price' => '',
            'bar_code' => '',
            'stock' => '',
            'description' => '',
            'state' => '',
            'wholesale_price' => '',
            'sku'=> '',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048' // Validación de imágenes múltiples
        ]);
        $product->update($validation);
        // Procesar imágenes si se suben
        $imagenes = [];
        $contador=1;
        if ($request->hasFile('image')) {
            foreach ($request->file('image') as $image) {
                $nuevoNombre = 'product_' . $product->id . '.' . $contador . $image->extension();
                $ruta = $image->storeAs('images/product', $nuevoNombre, 'public');
                $rutaCompleta = asset('storage/' . $ruta);
                $imagenes[] = $rutaCompleta;
                $contador++;
            }
        }
    
        $product->image = $imagenes; // Guardar imágenes como un array
        $product->update();
    
        return response()->json([
            'message' => 'Producto insertado correctamente',
            'data' => $product
        ], 201);
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
