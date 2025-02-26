<?php
namespace App\Http\Middleware; 
use Closure;
use App\Models\User;

class AuthMiddleware
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization');

        if (!$token) {
            return response()->json(['message' => 'No token provided'], 401);
        }

        $token = str_replace('Bearer ', '', $token);
        $admin = User::where('token', hash('sha256', $token))->first();
        

        if (!$admin) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $request->admin = $admin;
        return $next($request);
    }
}
