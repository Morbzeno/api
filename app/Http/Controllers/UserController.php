<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        return response()->json(User::all());  //Index te devuelve todos los usuarios
    }

    public function show($id)
    {
        $user = User::find($id);  //Show te manda un usuario en especifico en base a su id de mongo
        return $user ? response()->json($user) : response()->json(['error' => 'User no encontrado'], 404);
    }

    public function store(Request $request)
    {
    //estos son los campos que se deben de mandar, algunos pueden ser nulos y no pueden repetirse emails
        $request->validate([  
            'name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone' => 'required|string|max:20',
            'rfc' => 'nullable|string|max:20',
            'role' => 'required|string|max:50',
            'google_id' => 'nullable|string|max:255'
        ]);

        $user = new User();
        $user->fill($request->only(['name', 'last_name', 'email', 'phone', 'rfc', 'role', 'google_id']));
        $user->password = Hash::make($request->password);

        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $nuevoNombre = 'user_' . $user->id . '.' . $img->extension();
            $ruta = $img->storeAs('images/user', $nuevoNombre, 'public');
            $rutaCompleta = asset('storage/' . $ruta);

            $user->image = $rutaCompleta;
            $user->update();
        }

        $user->save();

        return response()->json([
            'message' => 'User insertado correctamente',
            'data' => $user
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User no encontrado'], 404);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $id,
            'password' => 'sometimes|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'phone' => 'sometimes|string|max:20',
            'rfc' => 'sometimes|string|max:20',
            'role' => 'sometimes|string|max:50',
            'google_id' => 'sometimes|string|max:255'
        ]);

        $user->fill($request->only(['name', 'last_name', 'email', 'phone', 'rfc', 'role', 'google_id']));
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $nuevoNombre = 'user_' . $user->id . '.' . $img->extension();
            $ruta = $img->storeAs('images/user', $nuevoNombre, 'public');
            $rutaCompleta = asset('storage/' . $ruta);

            $user->image = $rutaCompleta;
            $user->update();
        }

        $user->save();

        return response()->json([
            'message' => 'User actualizado correctamente',
            'data' => $user
        ], 200);
    }

    public function destroy($id)
    {  
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User no encontrado'], 404);
        }

        $user->delete();
        return response()->json(['message' => 'User eliminado correctamente'], 200);
    }
}
