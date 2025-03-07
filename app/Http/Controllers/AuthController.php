<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Registro de Usere
    public function register(Request $request)
    {
        
        // Validar los datos, incluyendo la image
        $request->validate([
            'name' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|unique:admins',
            'password' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'socialMedia' => 'required|string|max:255',
            'phone' => 'required|integer',
            'status' => 'required|string|max:255',
            'address' => 'required|string|max:255'
             // Validación de image
        ]);
    
        // Crear una nueva instancia de User
        $User = new User();
        $User->name = $request->name;
        $User->lastName = $request->lastName;
        $User->email = $request->email;
        $User->password = Hash::make($request->password);
        $User->socialMedia = $request->socialMedia;
        $User->phone = $request->phone;
        $User->status = $request->status;
        $User->address = $request->address;
    
        // Verificar si hay una image en la solicitud
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $nombreImagen = time() . '_' . $image->getUserOriginalName();
            $ruta = $image->storeAs('admins', $nombreImagen, 'public'); // Guardar en storage/app/public/admins
            $User->image = $ruta; // Guardar la ruta en la base de datos
        }
    
        // Guardar en la base de datos
        $User->save();
    
        return response()->json([
            'message' => 'User insertado correctamente',
            'data' => $User
        ], 201);
    }

    // Inicio de sesión

public function login(Request $request)
{
    $request->validate([
        'email'    => 'required|email',
        'password' => 'required',
    ]);

    $User = User::where('email', $request->email)->first();

    if (!$User || !Hash::check($request->password, $User->password)) {
        return response()->json(['message' => 'Credenciales inválidas'], 401);
    }

    // Generar un token único
    $token = Str::random(60);

    // Guardar el token en el Usere (MongoDB)
    $User->token = hash('sha256', $token);
    $User->save();

    return response()->json([
        'message' => 'Login correcto',
        'token'   => $token,
        'User'  => $User
    ]);
}



    // Cierre de sesión
    public function logout()
    {
        Auth::user()->tokens()->delete();
        return response()->json(['message' => 'Cierre de sesión exitoso']);
    }


    // Redirigir a Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    // Manejar la respuesta de Google
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
            // dd($googleUser);
            // Buscar o crear usuario
            $user = User::updateOrCreate([
                'email' => $googleUser->getEmail(),
            ], [
                'name' => $googleUser->getName(),
                'password' => bcrypt(Str::random(16)), // No usar Google para contraseña
                'google_id' => $googleUser->getId(),
            ]);

            // Generar Token JWT (si usas Sanctum o Passport)
            $token = Str::random(60);

            // Guardar el token en el Usere (MongoDB)
            $user->token = hash('sha256', $token);
            $user->save();

            return response()->json([
                'user' => $user,
                'token' => $token
            ], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al autenticar'], 500);
        }
    }
}

