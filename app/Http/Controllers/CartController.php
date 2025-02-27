<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductsCart;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

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
        // $user = $request->user();
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
                'message' => 'Carrito no encontrado'
            ], 404);
        }
        
    
        
        // Pasa los datos del carrito a la vista.
        return response()->json($cart);
        

    }
    public function show($id)
    {
        $cart = Cart::with(['client', 'producto_cart' => function ($query) {
            $query->where('state', 'waiting')->with('producto');
        }])->where('status', '!=', 'completed')->where('client_id', $id)->get();
    
        if ($cart->isEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Carrito no encontrado'
            ], 404);
        }
    
        return response()->json([
            'status' => 'success',
            'data' => $cart
        ], 200);
    }
    

    public function add(Request $request)
    {
        $client_id = $request->input('client_id');
    
        try {
            // Validar los datos del formulario
            $request->validate([
                'id' => 'required|string', // MongoDB usa strings para los IDs
                'price' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:1',
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
    
            // Buscar si el producto ya está en el carrito
            $productCart = ProductsCart::where('cart_id', $cart->id)
                ->where('product_id', $request->id)
                ->where('state', 'waiting')
                ->first();
    
            if ($productCart) {
                // Si ya existe, actualizar la cantidad y el subtotal
                $productCart->quantity += $request->quantity;
                $productCart->unit_price += $request->price;
                $productCart->subtotal += $request->price * $request->quantity;
                $productCart->save();
            } else {
                // Si no existe, crear un nuevo registro en `products_cart`
                ProductsCart::create([
                    'cart_id' => $cart->id,
                    'product_id' => $request->id,
                    'quantity' => $request->quantity,
                    'subtotal' => $request->price * $request->quantity,
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
        $client_id = $request->input('client_id');
        try {
            // Eliminar el ítem del carrito de la biblioteca Cart


            // Eliminar el registro correspondiente en `products_cart`
            ProductsCart::where('cart_id', Cart::where('client_id', $client_id)->value('id'))
                        ->where('product_id', $id)->where('state', 'waiting')
                        ->delete();
                        $total=ProductsCart::where('cart_id', Cart::where('client_id', $client_id)->value('id'))->where('state', 'waiting')
                        ->sum('subtotal');


            // Actualizar el total del carrito
            $cart = Cart::where('client_id', $client_id)->first();
            if ($cart) {
                $cart->total = $total;
                $cart->save();
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Sell deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Hubo un problema al eliminar el producto: ' . $e->getMessage()]);
        }
    }

    public function more(Request $request, $id)
    {
        $client_id = $request->input('client_id');
        // Obtener el ID del cliente autenticado
        // $clientId = auth()->id();
        // if (!$clientId) {
        //     return response()->json(['status' => 'error', 'message' => 'Usuario no autenticado.'], 401);
        // }
    
        // Obtener el carrito del cliente
        $cart = Cart::where('client_id', $client_id)->first();
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
            ->where('state', 'waiting')->with('producto')
            ->first();
    
        if (!$productCart) {
            return response()->json(['status' => 'error', 'message' => 'Producto no encontrado en el carrito con el estado requerido.'], 404);
        }
    
        // Actualizar la cantidad y el subtotal del producto
        $productCart->quantity += 1;
        $productCart->subtotal = $productCart->quantity * $product->sell_price; // Precio directo de la base de datos
        $productCart->save();
    
        // Recalcular el total del carrito sumando todos los subtotales de los productos en el carrito
        $cart->total = ProductsCart::where('cart_id', $cart->id)->where('state', 'waiting')->sum('subtotal');
        $cart->save();
    
        return response()->json(['status' => 'success', 'data' => $cart], 200);
    }
    
    public function less(Request $request, $id)
    {
        $client_id = $request->input('client_id');
   // Obtener el ID del cliente autenticado
   
        // $clientId = auth()->id();
        // if (!$clientId) {
        //     return response()->json(['status' => 'error', 'message' => 'Usuario no autenticado.'], 401);
        // }
    
        // Obtener el carrito del cliente
        $cart = Cart::where('client_id', $client_id)->with('producto')->first();
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
            ->where('state', 'waiting')->where('quantity', '>', 1)
            ->first();
    
        if (!$productCart) {
            return response()->json(['status' => 'error', 'message' => 'Producto no encontrado en el carrito con el estado requerido.'], 404);
        }
    
        // Actualizar la cantidad y el subtotal del producto
        $productCart->quantity -= 1;
        $productCart->subtotal = $productCart->quantity * $product->sell_price; // Precio directo de la base de datos
        $productCart->save();
    
        // Recalcular el total del carrito sumando todos los subtotales de los productos en el carrito
        $cart->total = ProductsCart::where('cart_id', $cart->id)->where('state', 'waiting')->with('producto')->sum('subtotal');
        $cart->save();
    
        return response()->json(['status' => 'success', 'data' =>$cart, $productCart], 200);
    }
    
    public function clear(Request $request)
    {
        $client_id = $request->input('client_id');
        try {
            // Eliminar el ítem del carrito de la biblioteca Cart


            // Eliminar el registro correspondiente en `products_cart`
            ProductsCart::where('cart_id', Cart::where('client_id', $client_id)->value('id'))->where('state', 'waiting')
                        ->delete();
                        $total=ProductsCart::where('cart_id', Cart::where('client_id', $client_id)->value('id'))->where('state', 'waiting')
                        ->sum('subtotal');


            // Actualizar el total del carrito
            $cart = Cart::where('client_id', $client_id)->first();
            if ($cart) {
                $cart->total = 0;
                $cart->save();
            }
if(!$cart){
    return response()->json([
        'status' => 'success',
        'message' => 'cart not found'
    ], 404);
}
            return response()->json([
                'status' => 'success',
                'message' => 'cart deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Hubo un problema al eliminar el producto: ' . $e->getMessage()]);
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
    public function paypalReturn(Request $request)
    {
        // Aquí manejarías la confirmación de la transacción si el pago fue exitoso
        // Podrías verificar el pago con PayPal usando el 'order_id' o 'token' devuelto.
        return response()->json(['status' => 'success', 'message' => 'Pago realizado con éxito']);
    }

    // Método para manejar la cancelación de PayPal (cuando el usuario cancela el pago)
    public function paypalCancel(Request $request)
    {
        // Aquí manejarías si el usuario cancela el pago
        return response()->json(['status' => 'error', 'message' => 'Pago cancelado por el usuario']);
    }
}