<?php
namespace App\Http\Controllers;

use App\Models\Sell;
use App\Models\ProductsCart;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\Http;
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
        // Validar los datos de la petición
        $validated = $request->validate([
            'purchase_method' => 'nullable|string',
            'direction_id' => 'required|exists:directions,id'
        ]);

        // Obtener el carrito activo del cliente
        $cart = Cart::where('client_id', $id)
                    ->where('status', '!=', 'completed')
                    ->first();

        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró un carrito activo para este cliente.'], 404);
        }
        if ($cart->total === 0) {
            return response()->json(['status' => 'error', 'message' => 'No hay productos en el carrito.'], 404);
        }

        // Calcular el total de la compra
        $total = ProductsCart::where('cart_id', $cart->id)
                             ->where('state', 'waiting')
                             ->sum('subtotal');
        $iva = $total * 0.16;
        $totalConIva = number_format($total + $iva, 2, '.', ''); // Formato correcto para PayPal

        // Obtener los productos del carrito
        $productosEnCarrito = ProductsCart::where('cart_id', $cart->id)
                                          ->where('state', 'waiting')
                                          ->get();

        // Verificar que todos los productos tengan stock suficiente
        foreach ($productosEnCarrito as $productoCarrito) {
            $producto = Product::find($productoCarrito->product_id);
            if (!$producto) {
                throw new \Exception("El producto con ID {$productoCarrito->product_id} no existe.");
            }
            if ($producto->stock < $productoCarrito->quantity) {
                return response()->json([
                    'status' => 'error',
                    'message' => "No hay suficiente stock de {$producto->name}. Stock actual: {$producto->stock}, solicitado: {$productoCarrito->quantity}"
                ], 400);
            }
        }

        // 🔹 **Crear la orden en PayPal**
        $paypal_client_id = config('services.paypal.client_id');
        $paypal_secret = config('services.paypal.secret');
        $paypal_url = config('services.paypal.mode') === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com/v2/checkout/orders'
            : 'https://api-m.paypal.com/v2/checkout/orders';

        $response = Http::withBasicAuth($paypal_client_id, $paypal_secret)
            ->post($paypal_url, [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'MXN',
                            'value' => $total
                        ],
                        'description' => 'Compra de productos en tu carrito'
                    ]
                ],
                'application_context' => [
                    'return_url' => url("/api/paypal/success"),
                    'cancel_url' => url("/api/paypal/cancel")
                ]
            ]);

        // Registrar la respuesta de PayPal en logs
        \Log::info('Respuesta de PayPal al crear orden:', $response->json());

        // Verificar si la solicitud falló
        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Error al crear la orden en PayPal', 'details' => $response->json()], 500);
        }

        $paypal_order = $response->json();

        // Guardar la orden pendiente en la base de datos
        $sell = Sell::create([
            'cart_id' => $cart->id,
            'client_id' => $id,
            'direction_id' => $request->direction_id,
            'iva' => $iva,
            'purchase_method' => 'paypal',
            'paypal_order_id' => $paypal_order['id'], // Guardar el ID de la orden de PayPal
            'status' => 'pending', // La venta se completa solo cuando el pago se confirma
        ]);

        DB::commit();

        return response()->json([
            'status' => 'pending_payment',
            'sell' => $sell,
            'paypal_url' => $paypal_order['links'][1]['href'] // URL para redirigir al usuario a PayPal
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        \Log::error('Error en la transacción de venta: ' . $e->getMessage());
        return response()->json(['error' => 'No se pudo completar la venta: ' . $e->getMessage()], 500);
    }
}

  
    
    // Mostrar todas las ventas


    // Mostrar todas las ventas
    public function index()
    {
        $sells = Cart::with([
            'producto_cart.producto.brand:id,name',
        ])->where('status', 'completed')->get();
    
        if(!$sells){
            return response()->json([
                "message" => 'no se encontraron ventas', 
                "data" => 0],400);
        }

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
                'message' => 'user not found',
                'data' => 0
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