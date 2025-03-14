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
            'email' => 'required|email|unique:users,id',
            'password' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'socialMedia' => 'required|string|max:255',
            'phone' => 'required|integer',
            // 'status' => 'required|string|max:255',
            // 'address' => 'required|string|max:255'
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
        // $User->status = $request->status;
        // $User->address = $request->address;
    
        // Verificar si hay una image en la solicitud
        if ($request->hasFile('image')) {
            $img = $request->file('image');
            $nuevoNombre = 'user_' . $user->id . '.' . $img->extension();
            $ruta = $img->storeAs('images/user', $nuevoNombre, 'public');
            $rutaCompleta = asset('storage/' . $ruta);

            $user->image = $rutaCompleta;
            $user->update();
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
        // Validar los datos de entrada
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);
    
        // Buscar el usuario en la base de datos
        $User = User::where('email', $request->email)->first();
    
        // Comprobar si el usuario existe y si la contraseña es correcta
        if (!$User || !Hash::check($request->password, $User->password)) {
            return response()->json(['message' => 'Credenciales inválidas'], 401);
        }
    
        // Generar un token único para la sesión
        $token = Str::random(60);
    
        // Guardar el token en la base de datos (hashing del token por seguridad)
        $User->token = hash('sha256', $token);
        $User->save();
    
        // Eliminar el campo 'token' del objeto User para que no se devuelva en la respuesta
        $User->makeHidden(['token']); // Esto evita que el token antiguo se incluya en la respuesta
    
        // Retornar la respuesta con el token generado
        return response()->json([
            'message' => 'Login correcto',
            'token'   => $token,
            'User'    => $User
        ]);
    }
    

    



    // Cierre de sesión
    public function logout(Request $request)
    {
        $user = Auth::user();
        
        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }
    
        $user->token = null; // Eliminar token manualmente
        $user->save();
    
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

