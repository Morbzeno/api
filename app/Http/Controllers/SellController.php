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
        ]);

        // Obtener el carrito activo del cliente
        $cart = Cart::where('client_id', $id)
                    ->where('status', '!=', 'completed')
                    ->first();

        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'No se encontró un carrito activo para este cliente.'], 404);
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
                            'currency_code' => 'USD',
                            'value' => $totalConIva
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


    public function paypalSuccess(Request $request)
    {
        $paypal_client_id = config('services.paypal.client_id');
        $paypal_secret = config('services.paypal.secret');
        $paypal_url = config('services.paypal.mode') === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com/v2/checkout/orders/'
            : 'https://api-m.paypal.com/v2/checkout/orders/';
    
        // Obtener los parámetros de PayPal
        $paypal_order_id = $request->query('token');
    
        if (!$paypal_order_id) {
            return response()->json(['error' => 'No se encontró el token de PayPal.'], 400);
        }
    
        // Verificar la orden en PayPal
        $response = Http::withBasicAuth($paypal_client_id, $paypal_secret)
            ->get($paypal_url . $paypal_order_id);
    
        if ($response->failed()) {
            return response()->json(['error' => 'No se pudo verificar la orden en PayPal.'], 500);
        }
    
        $orderData = $response->json();
    
        if ($orderData['status'] !== 'COMPLETED') {
            return response()->json(['error' => 'El pago no fue completado aún.'], 400);
        }
    
        DB::beginTransaction();
    
        try {
            // Buscar la venta en la base de datos
            $sell = Sell::where('paypal_order_id', $paypal_order_id)->first();
    
            if (!$sell) {
                return response()->json(['error' => 'No se encontró la venta asociada a esta orden de PayPal.'], 404);
            }
    
            // Marcar la venta como completada
            $sell->status = 'completed';
            $sell->save();
    
            // Actualizar stock de los productos comprados
            $productosEnCarrito = ProductsCart::where('cart_id', $sell->cart_id)
                                              ->where('state', 'waiting')
                                              ->get();
    
            foreach ($productosEnCarrito as $productoCarrito) {
                $producto = Product::find($productoCarrito->product_id);
    
                if ($producto) {
                    $producto->stock -= $productoCarrito->quantity;
                    $producto->save();
                }
            }
    
            // Marcar los productos en el carrito como "vendidos"
            ProductsCart::where('cart_id', $sell->cart_id)
                        ->where('state', 'waiting')
                        ->update(['state' => 'sold']);
    
            // Marcar el carrito como completado
            $cart = Cart::find($sell->cart_id);
            if ($cart) {
                $cart->status = 'completed';
                $cart->save();
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Pago completado exitosamente',
                'sell' => $sell
            ]);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al procesar el pago: ' . $e->getMessage()], 500);
        }
    }
    public function paypalCancel()
    {
        return response()->json(['message' => 'Pago cancelado por el usuario']);
    }

    }