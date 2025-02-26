<?php
namespace App\Http\Controllers;

use App\Models\Sell;
use App\Models\ProductsCart;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;

class SellController extends Controller
{
    // Crear una venta
    public function store(Request $request, $id)
    {
        // Validación de los campos de la solicitud
        $validated = $request->validate([
             'direction_id' => 'required|exists:directions,id',
             'purchase_method' => 'nullable|string',
        ]);

        // Obtener el carrito del cliente
        $cart = Cart::where('client_id', $id) // Considerando solo carritos activos
                    ->first();

        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró un carrito activo para este cliente.'], 404);
        }

        // Calcular el total del carrito sumando los subtotales de los productos en el carrito
        $total = ProductsCart::where('cart_id', $cart->id)
                             ->where('state', 'waiting') // Solo productos en estado "waiting"
                             ->sum('subtotal');

        // Agregar el IVA al total
        $iva = $total * 0.16;
        $totalConIva = $total + $iva;

        // Crear la venta con el total calculado y los datos proporcionados
        $sell = Sell::create([
            'cart_id' => $cart->id,
            'client_id' => $id,
            'direction_id' => $request->direction_id,
            'total' => $totalConIva, 
            'iva' => $iva,
            'purchase_method' => $request->purchase_method,
        ]);

        // Actualizar el estado de los productos en el carrito
        $cartItems = ProductsCart::where('state', 'waiting')
                                 ->where('cart_id', $cart->id)
                                 ->get();

        foreach ($cartItems as $cartItem) {
            $cartItem->state = 'sell';  // Cambiar el estado de "waiting" a "sell"
            $cartItem->sell_id = $sell->id; // Asociar el producto con la venta
            $cartItem->save();
        }

        // Actualizar el estado y total del carrito
        $cart->total = 0; // Guardar el total (sin IVA si así lo deseas)
        $cart->save();

        return response()->json([
            'status' => 'success',
            'data' => $sell
        ], 201);
    }

    // Mostrar todas las ventas


    // Mostrar todas las ventas
    public function index()
    {
         
        $sells = User::with([
            'carts.producto_cart' => function ($query) {
                $query->where('state', 'sell');
            },
            'carts.producto_cart.producto.brand',
            'carts.producto_cart.producto.category'
        ])->with('direction')->get();
    
        return response()->json([
            'status' => 'success',
            'data' => $sells
        ], 200);
    }
    

    // Mostrar una venta específica
    public function show($id)
    {
        $sells = Cart::with(['client', 'sell.direction', 'producto_cart' => function ($query) {
            $query->where('state', 'sell')->with('producto');
        }])->where('client_id', $id)->get();

        if ($sells->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'user not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $sells
        ], 200);
    }

    // Eliminar una venta
    public function destroy($id)
    {
        $sell = Sell::find($id);
        if (!$sell) {
            return response()->json([
                'status' => 'error',
                'message' => 'Sell not found'
            ], 404);
        }

        $sell->delete();  // Eliminar la venta

        return response()->json([
            'status' => 'success',
            'message' => 'Sell deleted successfully'
        ], 200);
    }
}
