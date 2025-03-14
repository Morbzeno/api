<?php
namespace App\Http\Controllers;

use App\Models\Sell;
use App\Models\ProductsCart;
use App\Models\Product;
use App\Models\Cart;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


class SellController extends Controller
{
    // Crear una venta
  // Crear una venta
public function store(Request $request, $id)
{
    DB::beginTransaction();

    try {
        // Validar los datos
        $validated = $request->validate([
           //  'direction_id' => 'required|exists:directions,id',
           //  'purchase_method' => 'nullable|string',
        ]);

        // Obtener el carrito activo del cliente
        $cart = Cart::where('client_id', $id)
                    ->where('status', '!=', 'completed')
                    ->first();

        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró un carrito activo para este cliente.'], 404);
        }

        // Calcular el total
        $total = ProductsCart::where('cart_id', $cart->id)
                             ->where('state', 'waiting')
                             ->sum('subtotal');
        $iva = $total * 0.16;
        $totalConIva = $total + $iva;

        // Obtener los productos del carrito
        $productosEnCarrito = ProductsCart::where('cart_id', $cart->id)
                                          ->where('state', 'waiting')
                                          ->get();

        // Verificar que todos los productos tengan stock suficiente antes de continuar
        foreach ($productosEnCarrito as $productoCarrito) {
            $producto = Product::find($productoCarrito->product_id);
            
            if (!$producto) {
                throw new \Exception("El producto con ID {$productoCarrito->product_id} no existe.");
            }

            if ($producto->stock < $productoCarrito->quantity) {
                throw new \Exception("No hay suficiente stock de: {$producto->name}. Stock actual: {$producto->stock}, solicitado: {$productoCarrito->quantity}");
            }
        }

        // Crear la venta
        $sell = Sell::create([
            'cart_id' => $cart->id,
            'client_id' => $id,
           // 'direction_id' => $request->direction_id,
            'iva' => $iva,
           // 'purchase_method' => $request->purchase_method,
        ]);

        // Reducir el stock de cada producto ahora que sabemos que hay suficiente
        foreach ($productosEnCarrito as $productoCarrito) {
            $producto = Product::find($productoCarrito->product_id);
            $producto->stock -= $productoCarrito->quantity;
            $producto->save();
        }

        // Actualizar el estado de los productos en el carrito
        ProductsCart::where('cart_id', $cart->id)
                    ->where('state', 'waiting')
                    ->update(['state' => 'sell', 'sell_id' => $sell->id]);

        // Marcar el carrito como "completed"
        $cart->status = 'completed';
        $cart->save();

        // Crear un nuevo carrito para futuras compras
        $newCart = Cart::create([
            'client_id' => $id,
            'total' => 0,
            'status' => 'pending'
        ]);

        DB::commit();

        return response()->json([
            'status' => 'success',
            'sell' => $sell,
            'new_cart' => $newCart
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => 'No se pudo completar la venta: ' . $e->getMessage()], 500);
    }
}

    
    // Mostrar todas las ventas


    // Mostrar todas las ventas
    public function index()
    {
         
        $sells = Cart::with([
            'producto_cart.producto.brand',
        ])->where('status', 'completed')->get();
    
        return response()->json([
            'status' => 'success',
            'data' => $sells
        ], 200);
    }
    

    // Mostrar una venta específica
    public function show($id)
    {
        $sells = Sell::with([
            'carts.producto_cart.product:id.brand:id,name',
            // 'carts.producto_cart.producto.category'
        ])->where('client_id', $id)->get();

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
    public function gananciasMensuales(){
        $sells = Sell::whereRaw("MONTH(CONVERT_TZ(created_at, '+00:00', @@session.time_zone)) = ?", [2])
            ->whereRaw("YEAR(CONVERT_TZ(created_at, '+00:00', @@session.time_zone)) = ?", [2025])
            ->sum('total');
    
        return response()->json(['ventas del mes' => $sells]);
    }
    }

