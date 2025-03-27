<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductsCart;
use App\Models\Sell;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;


class CartController extends Controller
// implements HasMiddleware
{
    // public static function middleware()
    // {
    //         return [
    //                 new Middleware('auth:sanctum')
    //             ];
    //         }
    public function get(Request $request)
    {
         $user = $request->user();
         $client_id = $request->input('client_id');
         if (!$client_id) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'El campo client_id es obligatorio.'
             ], 400);
         }
         $cart = Cart::with(['client', 'producto_cart' => function ($query) {
             $query->where('state', 'waiting')->with('producto');
         }])->where('status', '!=', 'completed')->where('client_id', $client_id)->get();
        
         if ($cart->isEmpty()) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Carrito no encontrado',
                 'data' => 0
             ], 404);
         }
        
    
        
        // Pasa los datos del carrito a la vista.
        return response()->json($cart);
        

    }
    public function show($id)
    {
        // $cart = Cart::with(['client', 'producto_cart' => function ($query) {
        //     $query->where('state', 'waiting')->with('producto');
        // }])->where('status', '!=', 'completed')->where('client_id', $id)->get();
    
        // if ($cart->isEmpty()) {
        //     return response()->json([
        //         'status' => 'error',
        //         'message' => 'Carrito no encontrado'
        //     ], 404);
        // }
    
        // return response()->json([
        //     'status' => 'success',
        //     'data' => $cart
        // ], 200);
        $cart = Cart::with(['client', 'producto_cart' => function ($query) {
            $query->where('state', 'waiting')->with('producto');
        }])->where('status', '!=', 'completed')->where('client_id', $id)->get();
    
        if ($cart->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Carrito no encontrado',
                "data" => 0
            ], 404);
        }
        $total = $cart->sum(fn($c)=>$c->producto_cart->count());
        return response()->json([
            'status' => 'success',
            'data' => $cart,
            'number_of_products:' => $total
        ], 200);   
    }
    

    public function add(Request $request)
    {
        $client_id = $request->input('client_id');
        $quantity = $request->input('quantity');
        if (!$client_id) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ], 400);
        }
        if (!$quantity){
            $request->quantity = 1;
        }

        try {
            // Validar los datos del formulario
            $request->validate([
                'id' => 'required|string', // MongoDB usa strings para los IDs
            ]);
    
            // Buscar el carrito activo del cliente (sin estado "completed") o crear uno nuevo
            $cart = Cart::where('client_id', $client_id)
                        ->where('status', '!=', 'completed')
                        ->first();
            
            if (!$cart) {
                $cart = Cart::create([
                    'client_id' => $client_id,
                    'total' => 0,
                    'status' => 'pending'
                ]);
            }
    
            // Buscar el producto
            $product = Product::find($request->id);
            if (!$product) {
                return response()->json(['error' => 'Producto no encontrado'], 404);
            }
    
            $price = $product->retail_price; // Obtener el precio desde la BD
    
            // Buscar si el producto ya está en el carrito
            $productCart = ProductsCart::where('cart_id', $cart->id)
                ->where('product_id', $request->id)
                ->where('state', 'waiting')
                ->first();
    
            if ($productCart) {
                // Si ya existe, actualizar la cantidad y el subtotal
                $productCart->quantity += $request->quantity;
                $productCart->unit_price = $price; // Asegurar que el precio siempre se actualice
                $productCart->subtotal = $productCart->quantity * $price;
                $productCart->save();
            } else {
                // Si no existe, crear un nuevo registro en `products_cart`
                $productCart = ProductsCart::create([
                    'cart_id' => $cart->id,
                    'product_id' => $request->id,
                    'quantity' => $request->quantity,
                    'unit_price' => $price, // Agregar el precio correcto
                    'subtotal' => $price * 1, // Corregir el cálculo del subtotal
                    'state' => 'waiting'
                ]);
            }
    
            // Calcular el nuevo total sumando solo los productos "waiting"
            $total = ProductsCart::where('cart_id', $cart->id)
                ->where('state', 'waiting')
                ->sum('subtotal');
    
            // Actualizar el total del carrito
            $cart->total = $total;
            $cart->save();
    
            return response()->json([
                'status' => 'success',
                'cart' => $cart
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Hubo un problema al añadir el producto: ' . $e->getMessage()], 500);
        }
    }

    public function quitItem(Request $request, $id)
{
    // Acceder al client_id desde los parámetros de consulta (query parameters)
    $client_id = $request->query('client_id'); 

    if (!$client_id) {
        return response()->json([
            'message' => 'Cliente no encontrado'
        ]);
    }

    try {
        // Buscar el carrito del cliente
        $cart = Cart::where('client_id', $client_id)->where('status', '!=', 'completed')->first();

        if (!$cart) {
            return response()->json([
                'status' => 'error',
                'message' => 'No se encontró un carrito activo para el cliente.'
            ], 404);
        }

        // Eliminar el producto del carrito
        $deleted = ProductsCart::where('cart_id', $cart->id)
            ->where('product_id', $id)
            ->where('state', 'waiting')
            ->delete();

        // Si no se eliminó ningún producto, retornar error
        if ($deleted === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'El producto no estaba en el carrito o ya fue procesado.'
            ], 404);
        }

        // Recalcular el total del carrito
        $total = ProductsCart::where('cart_id', $cart->id)
            ->where('state', 'waiting')
            ->sum('subtotal');

        $cart->total = $total;
        $cart->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Producto eliminado correctamente.'
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Hubo un problema al eliminar el producto: ' . $e->getMessage()
        ], 500);
    }
}

    public function more(Request $request, $id)
    {
        
        $client_id = $request->input('client_id');
        if (!$client_id) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ]);
        }
        // Obtener el carrito del cliente
        $cart = Cart::where('client_id', $client_id)->where('status', '!=', 'completed')->with('producto_cart')->first();
        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'Carrito no encontrado para este cliente.'], 404);
        }
    
        // Verificar si el producto existe en la base de datos
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Producto no encontrado.'], 404);
        }
    
        // Buscar el producto en el carrito con estado 'waiting'
        $productCart = ProductsCart::where('cart_id', $cart->id)
            ->where('product_id', $id)
            ->first();
    
        if (!$productCart) {
            return response()->json(['status' => 'error', 'message' => 'Producto no encontrado en el carrito con el estado requerido.'], 404);
        }
    
        // Actualizar la cantidad y el subtotal del producto
        $productCart->quantity += 1;
        $productCart->subtotal = $productCart->quantity * $product->retail_price; // Precio directo de la base de datos
        $productCart->save();
    
        // Recalcular el total del carrito sumando todos los subtotales de los productos en el carrito
        $cart->total = ProductsCart::where('cart_id', $cart->id)->where('state', 'waiting')->with('producto')->sum('subtotal');
        $cart->save();
    
        return response()->json(['status' => 'success', 'data' => $productCart], 200);
    }
    
    public function less(Request $request, $id)
    {
        $client_id = $request->input('client_id');
        if (!$client_id) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ]);
        }
  
        // Obtener el carrito del cliente
        $cart = Cart::where('client_id', $client_id)->where('status', '!=', 'completed')->with('producto_cart')->first();
        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'Carrito no encontrado para este cliente.'], 404);
        }
    
        // Verificar si el producto existe en la base de datos
        $product = Product::find($id);
        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Producto no encontrado.'], 404);
        }
    
        // Buscar el producto en el carrito con estado 'waiting'
        $productCart = ProductsCart::where('cart_id', $cart->id)
            ->where('product_id', $id)
            ->first();
    

        if (!$productCart) {
            return response()->json(['status' => 'error', 'message' => 'Producto no encontrado en el carrito con el estado requerido.'], 404);
        }
        if ($productCart->quantity <= 1) {
            return response()->json([
                'status' => 'error',
                'message' => 'La cantidad mínima permitida es 1.'
            ], 422); 
        }
        // Actualizar la cantidad y el subtotal del producto
        $productCart->quantity -= 1;
        $productCart->subtotal = $productCart->quantity * $product->retail_price; // Precio directo de la base de datos
        $productCart->save();
    
        // Recalcular el total del carrito sumando todos los subtotales de los productos en el carrito
        $cart->total = ProductsCart::where('cart_id', $cart->id)->where('state', 'waiting')->with('producto')->sum('subtotal');
        $cart->save();
    
        return response()->json(['status' => 'success', 'data' => $productCart], 200);
    }
    
    public function clear(Request $request)
    {
        $client_id = $request->input('client_id');
        if (!$client_id) {
            return response()->json([
                'message' => 'Cliente no encontrado'
            ]);
        }
    
        try {
            // Buscar el carrito del cliente
            $cart = Cart::where('client_id', $client_id)->where('status', '!=', 'completed')->first();
    
            if (!$cart) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No se encontró un carrito activo para el cliente.'
                ], 404);
            }
    
            // Eliminar el producto del carrito
            $deleted = ProductsCart::where('cart_id', $cart->id)
                ->delete();
    
            // Si no se eliminó ningún producto, retornar error
            if ($deleted === 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'El producto no estaba en el carrito o ya fue procesado.'
                ], 404);
            }
    
            // Recalcular el total del carrito
            $total = ProductsCart::where('cart_id', $cart->id)
                ->sum('subtotal');
    
            $cart->total = $total;
            $cart->save();
    
            return response()->json([
                'status' => 'success',
                'message' => 'Carrito borrado'
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hubo un problema al borrar el carrito: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createPaypalOrder(Request $request)
    {
        $client_id = $request->input('client_id');
        $cart = Cart::with(['producto_cart' => function ($query) {
            $query->where('state', 'waiting')->with('producto');
        }])->where('client_id', $client_id)->first();

        if (!$cart) {
            return response()->json(['status' => 'error', 'message' => 'Carrito no encontrado'], 404);
        }

        // Calcular el total del carrito (sólo los productos en estado 'waiting')
        $total = ProductsCart::where('cart_id', $cart->id)->where('state', 'waiting')->sum('subtotal');

        // Si el carrito está vacío
        if ($total <= 0) {
            return response()->json(['status' => 'error', 'message' => 'El carrito está vacío'], 400);
        }

        // Obtener la configuración de PayPal
        $paypal_client_id = Config::get('services.paypal.client_id');
        $paypal_secret = Config::get('services.paypal.secret');
        $paypal_url = "https://api-m.sandbox.paypal.com/v2/checkout/orders";

        // Crear la solicitud de pago a PayPal
        $response = Http::withBasicAuth($paypal_client_id, $paypal_secret)
            ->post($paypal_url, [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD', // O la moneda que uses
                            'value' => $total
                        ],
                        'description' => 'Compra de productos en tu carrito'
                    ]
                ],
                'application_context' => [
                    'return_url' => url('/api/paypal/return'),
                    'cancel_url' => url('/api/paypal/cancel')
                ]
            ]);

        // Verificar la respuesta de PayPal
        if ($response->failed()) {
            return response()->json(['status' => 'error', 'message' => 'Error al crear la orden de PayPal'], 500);
        }

        // Devolver el ID de la orden o el enlace a PayPal
        $paypal_order = $response->json();

        return response()->json([
            'status' => 'success',
            'order_id' => $paypal_order['id'],
            'paypal_url' => $paypal_order['links'][1]['href'] // URL para redirigir al usuario a PayPal
        ], 200);
    }

    // Método para manejar la respuesta exitosa de PayPal (cuando el usuario paga)
    public function paypalSuccess(Request $request)
    {
        DB::beginTransaction();
    
        // Obtener el token de la orden desde PayPal
        $order_id = $request->query('token');
        if (!$order_id) {
            return response()->json(['error' => 'Orden de PayPal no encontrada'], 400);
        }
    
        // Credenciales de PayPal
        $paypal_client_id = config('services.paypal.client_id');
        $paypal_secret = config('services.paypal.secret');
        $paypal_mode = config('services.paypal.mode') === 'sandbox' ? 'sandbox' : 'live';
    
        $paypal_base_url = $paypal_mode === 'sandbox'
            ? 'https://api-m.sandbox.paypal.com/v2/checkout/orders/'
            : 'https://api-m.paypal.com/v2/checkout/orders/';
    
        // Consultar el estado de la orden en PayPal
        $response = Http::withBasicAuth($paypal_client_id, $paypal_secret)
            ->get($paypal_base_url . $order_id);
    
        if ($response->failed()) {
            \Log::error('Error al verificar la orden en PayPal: ', $response->json());
            return response()->json(['error' => 'No se pudo verificar el pago en PayPal'], 500);
        }
    
        $orderData = $response->json();
    
        // Verificar si la orden está aprobada antes de capturar el pago
        if (!isset($orderData['status']) || $orderData['status'] !== 'APPROVED') {
            \Log::warning("Orden de PayPal no aprobada: ", $orderData);
            return response()->json(['error' => 'El pago aún no ha sido aprobado'], 400);
        }
    
        // Buscar la venta asociada a la orden de PayPal
        $sell = Sell::where('paypal_order_id', $order_id)->first();
        if (!$sell) {
            \Log::error("Venta no encontrada para la orden PayPal: {$order_id}");
            return response()->json(['error' => 'Venta no encontrada'], 404);
        }
    
        // Confirmar la venta y actualizar el estado
        $sell->update(['status' => 'completed']);
    
        // 🔹 Actualizar el estado del carrito a "completed"
        $cart = Cart::find($sell->cart_id);
        if ($cart) {
            $cart->update(['status' => 'completed']);
        } else {
            \Log::error("Carrito no encontrado para la venta: {$sell->cart_id}");
        }
    
        // Obtener productos del carrito
        $productosEnCarrito = ProductsCart::where('cart_id', $sell->cart_id)->get();
    
        foreach ($productosEnCarrito as $productoCarrito) {
            $producto = Product::find($productoCarrito->product_id);
            if ($producto) {
                // Reducir stock asegurando que no sea negativo
                if ($producto->stock >= $productoCarrito->quantity) {
                    $producto->decrement('stock', $productoCarrito->quantity);
                    $productoCarrito->update(['state' => 'sold']); // Marcar los productos como vendidos
                } else {
                    \Log::warning("Stock insuficiente para el producto: {$producto->id}");
                }
            } else {
                \Log::error("Producto no encontrado: {$productoCarrito->product_id}");
            }
        }
    
        DB::commit();
    
        // Construir la URL de redirección al frontend
        $status = 'success';
        $message = 'Pago confirmado, venta y carrito completados';
        $frontendUrl = "http://localhost:5173/paypal/success?status=$status&message=" . urlencode($message);
    
        // Redirigir al frontend con los datos en la URL
        return redirect()->away($frontendUrl);
    }
    
    
    // Método para manejar la cancelación de PayPal (cuando el usuario cancela el pago)
    public function paypalCancel(Request $request)
    {
        $order_id = $request->query('token');
        if (!$order_id) {
            return response()->json(['error' => 'Orden de PayPal no encontrada'], 400);
        }
        $sell = Sell::where('paypal_order_id', $order_id)->first();
        if (!$sell) {
            \Log::error("Venta no encontrada para la orden PayPal: {$order_id}");
            return response()->json(['error' => 'Venta no encontrada'], 404);
        }
        $sell->delete();
        return response()->json(['status' => 'error', 'message' => 'Pago cancelado por el usuario'],200);
    }
    
    
}