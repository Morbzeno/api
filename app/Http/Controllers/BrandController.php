<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index(Request $request){
        $query = Brand::query();
        if ($request->has('search')){
            $search = $request = $request->input('search');

            $query->where('name', 'regexp', new \MongoDB\BSON\Regex($search, 'i'));
        
    }
        // return response()->json(Brand::all());
        //->pagination(15)
        $brand = $query->with('products')->get();
        if($brand->isEmpty()){
            return response()->json([
                'message' => 'brand no encontrada'
            ],400);
        }
        return response()->json([
            "message" => 'brand encontrada',
            "data"=>$brand]);
    }

    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:100|unique:brands,name', // Definir el campo específico 'name' en la tabla brands
            'description' => 'required|string|max:300',
            'contact' => 'required|string|max:100',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048' // Validación para imágenes
        ]);
        $brand = Brand::create($request->only('name', 'description', 'contact'));
        $brand->save();
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $nuevoNombre = 'brand_' . $brand->id . '.' . $img->extension();
            $ruta = $img->storeAs('images/brand', $nuevoNombre, 'public');
            $rutaCompleta = asset('storage/' . $ruta);

            $brand->image = $rutaCompleta;
            $brand->update();
        }
        // Create a new brand
        return response()->json($brand);
    }
    public function show($id){
        $brand = Brand::with('products')->find($id);
        if(!$brand){
            return response()->json([
                'message' => 'brand no encontrada'
            ],400);
        }
        return response()->json([
            "message" => 'brand encontrada',
            "data" => $brand
        ],200);

    }

    public function update(Request $request, $id){
        $brand = Brand::find($id);
        if(!$brand){
            return response()->json([
                'message' => 'brand no encontrada'
            ],400);
        }
        $validation = $request->validate([
            'name' => 'string|max:100|unique:brands,name', // Definir el campo específico 'name' en la tabla brands
            'description' => 'string|max:100',
            'contact' => 'string|max:100',
            'image' => 'image|mimes:jpeg,png,jpg,gif|max:2048' // Validación para imágenes
        ]);
        $brand->update($validation);
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $nuevoNombre = 'brand_' . $brand->id . '.' . $img->extension();
            $ruta = $img->storeAs('images/brand', $nuevoNombre, 'public');
            $rutaCompleta = asset('storage/' . $ruta);

            $brand->image = $rutaCompleta;
            $brand->update();
        }
        // Create a new brand
        return response()->json($brand);
    }

    public function destroy($id){
        $brand = Brand::find($id);
        if(!$brand){
            return response()->json([
                'message' => 'brand no encontrada'
            ],400);
        }
        $brand->delete();
        return response()->json([
            'message' => 'brand eliminada correctamente'
        ],200);
    }
}