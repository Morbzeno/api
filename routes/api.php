<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DirectionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\SellController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/ping', function (Request $request) {    
    $connection = DB::connection('mongodb');
    $msg = 'MongoDB is accessible!';
    
    try {  
        $connection->command(['ping' => 1]);  
    } catch (\Exception $e) {  
        $msg = 'MongoDB is not accessible. Error: ' . $e->getMessage();
    }

    return response()->json(['msg' => $msg]);
});
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});





Route::get('/category', [CategoryController::class, 'index']);
Route::post('/category', [CategoryController::class, 'store']);
Route::get('/category/{id}', [CategoryController::class, 'show']);
Route::put('/category/{id}', [CategoryController::class, 'update']);
Route::delete('/category/{id}', [CategoryController::class, 'destroy']);



Route::get('/product', [ProductController::class, 'index']);
Route::get('/product/{id}', [ProductController::class, 'show']);
Route::delete('/product/{id}', [ProductController::class, 'destroy']);
Route::post('/product', [ProductController::class, 'store']);

Route::get('/brand', [BrandController::class, 'index']);
Route::get('/brand/{id}', [BrandController::class, 'show']);
Route::post('/brand', [BrandController::class, 'store']);
Route::delete('/brand/{id}', [BrandController::class, 'destroy']);
Route::put('/brand/{id}', [BrandController::class, 'update']);

Route::get('/direction', [DirectionController::class, 'index']);
Route::get('/direction/{id}', [DirectionController::class, 'show']);
Route::post('/direction', [DirectionController::class, 'store']);
Route::delete('/direction/{id}', [DirectionController::class, 'destroy']);
Route::put('/direction/{id}', [DirectionController::class, 'update']);

Route::middleware(['auth.client'])->group(function () {
    Route::get('/user', [UserController::class, 'index']);
    Route::get('/user/{id}', [UserController::class, 'show']);
    Route::post('/user', [UserController::class, 'store']);
    Route::delete('/user/{id}', [UserController::class, 'destroy']);
    Route::put('/user/{id}', [UserController::class, 'update']);
});
Route::post('/register',[AuthController::class, 'register']);
Route::post('/login',[AuthController::class, 'login']);


Route::prefix('cart')->group(function () {
    Route::get('/', [CartController::class, 'get']);
    Route::get('/{id}', [CartController::class, 'show']);
    Route::post('/add', [CartController::class, 'add']);
    Route::delete('/{id}', [CartController::class, 'quitItem']);
    Route::put('/{id}/more', [CartController::class, 'more']);
    Route::put('/{id}/less', [CartController::class, 'less']);
    Route::delete('/', [CartController::class, 'clear']);
});

Route::prefix('sells')->group(function () {
    Route::get('/', [SellController::class, 'index']);        // Obtener todas las ventas
    Route::get('/{id}', [SellController::class, 'show']);     // Obtener una venta específica
    Route::post('/{id}', [SellController::class, 'store']);       // Crear una nueva venta
    Route::delete('/{id}', [SellController::class, 'destroy']); // Eliminar una venta específica
});