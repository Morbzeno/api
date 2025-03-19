<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        if(!$users){
            return response()->json([
                'message' => 'no se encontraron usuarios',
                ],400);
        }
        return response()->json([
            'message' => 'Todos los usuarios aquí',
            'data' => $users
        ],200);  //Index te devuelve todos los usuarios y solamente eso
    }

    public function show($id)
    {
        $user = User::find($id);
        if(!$user){
            return response()->json([
                'response' => 'usuarios no encontrados'
            ], 404);
        }
        return response()->json([
            "message" => 'usuario recuperado con exito',
            "data" => $user
        ],200);
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
        //se hashea la contraseña
        $user->password = Hash::make($request->password); 
        // aqui se manda la imagen a storage
        if ($request->hasFile('image')) { 
            $img = $request->file('image');
            $nuevoNombre = 'user_' . $user->id . '.' . $img->extension();
            $ruta = $img->storeAs('images/user', $nuevoNombre, 'public');
            $rutaCompleta = asset('storage/' . $ruta);

            $user->image = $rutaCompleta;
            $user->update();
        }

        $user->save(); 
        //respuesta json
        return response()->json([
            'message' => 'User insertado correctamente',
            'data' => $user
        ], 201); 
    }

    public function update(Request $request, $id)
    {
        //busca al usuario, si no encuentra manda error
        $user = User::find($id);

        if (!$user) {
            return response()->json(['error' => 'User no encontrado'], 404);
        }
        // los datos que se pueden alterar, esta en "sometimes" para que puedas modificar los campos que quieras, 
        // y los demas queden como estaban
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
        //en caso de que se quiera cambiar la contraseña
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }
        //en caso de que se queira cambiar la imagen
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $nuevoNombre = 'user_' . $user->id . '.' . $img->extension();
            $ruta = $img->storeAs('images/user', $nuevoNombre, 'public');
            $rutaCompleta = asset('storage/' . $ruta);

            $user->image = $rutaCompleta;
            $user->update();
        }

        $user->save();
        //respuesta con el usuario actualizado
        return response()->json([
            'message' => 'User actualizado correctamente',
            'data' => $user
        ], 200);
    }

    public function destroy($id)
    {  
        //busca el id del suuario
        $user = User::find($id);

        //respuesta si no lo encuentra
        if (!$user) {
            return response()->json(['error' => 'User no encontrado'], 404);
        }
        //lo elimina y respuesta de eliminado exitoso
        $user->delete();
        return response()->json(['message' => 'User eliminado correctamente'], 200);
    }
}
