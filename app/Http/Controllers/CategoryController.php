<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return response()->json(Category::all());
        $categories = Category::with('products')->get();
        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'tags' => 'required|array',
            'description' => 'required|string|max:50'
        ]);

        $categories = new Category();
        $categories->name = $request->name;
        $categories->tags = $request->tags;
        $categories->description = $request->description;
        $categories->save();
        return response()->json([
            'message' => 'categoria insertada correctamente',
            'data' => $categories
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $categories = Category::with('products')->find($id);
        return $categories ? response()->json($categories) : response()->json(['error' => 'categorie no encontrado'], 404);

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $categories = Category::find($id);

        if(!$categories){
            return response()->json([
                'message' => 'no se encontro la categoria'
            ], 400);
        }
        $validation=$request->validate([
            'name' => 'required|string|max:50',
            'tags' => 'required|array',
            'description' => 'required|string|max:50'
        ]);

        $categories->update($validation);
        return response()->json([
            'message' => 'categoria actualizada correctamente',
            'data' => $categories
        ], 201);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $categories = Category::findorfail($id);
        if(!$categories){
            return response()->json(['message' => 'categoria no encontrata'], 400);
        }
        $categories->delete();
        return response()->json(['message' => 'categoria eliminada'], 200);
    }
}
