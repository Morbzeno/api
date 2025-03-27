<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();
        if ($request->has('search')){
            $search = $request->input('search');

            $query->where('name', 'regexp', new \MongoDB\BSON\Regex($search, 'i'));

        }
        $product = $query->with('category:id,name', 'brand:id,name')->get();
        if ($product->isEmpty()){
            return response()->json(['message' => 'productos no encontrados'
        ],400);
        }
        return response()->json([
            'message'=>'productos encontrados:',
            'data' => $product
        ],200);
    }

    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required',
            'brand_id' => 'required|exists:brands,id',
            'retail_price' => 'required|numeric',
            'buy_price' => 'required|numeric',
            'bar_code' => 'unique:products,bar_code',
            'stock' => 'required|integer',
            'description' => 'required',
            'state' => 'required',
            'wholesale_price' => 'required',
            'sku' => 'unique:products,sku',
            'image.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048' // Validación de imágenes múltiples

        ]);
    
        $product = new Product();
        $product->category_id = $request->category_id;
        $product->name = $request->name;
        $product->brand_id = $request->brand_id;
        $product->retail_price = $request->retail_price;
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
        $product->update();
    
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
        $products = Product::with('category:id,name', 'brand:id,name')->find($id);
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
            'category_id' => 'exists:categories,id',
            'name' => '',
            'brand_id' => 'exists:brands,id',
            'retail_price' => 'numeric',
            'buy_price' => 'numeric',
            'bar_code' => '',
            'stock' => 'integer',
            'description' => '',
            'state' => '',
            'wholesale_price' => 'numeric',
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
    public function moreStock(string $id, Request $request){
        $product = Product::find($id);
        if (!$product){
            return response()->json(['message' => 'producto no encontrado'], 400);
        }
        $request->validate([
            'aumento' => 'required|integer'
        ]);
        $product->stock += $request->aumento;
        $product->save();
        return response()->json([
            'message' => "se añadieron: {$request->aumento} de: {$product->name}"
        ]);
    }
}
