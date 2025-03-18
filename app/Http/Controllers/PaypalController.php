<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PayPalController extends Controller
{
    public function createOrder(Request $request)
    {
        // Validar y decodificar el JSON recibido
        $data = $request->json()->all();

        $validated = $request->validate([
            'client_id' => 'required|integer',
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'required|integer|min:1',
            'total' => 'required|numeric|min:0',
        ]);

        try {
            // Configurar PayPal
            $paypal = new PayPalClient;
            $paypal->setApiCredentials(config('paypal'));
            $paypal->setAccessToken($paypal->getAccessToken());

            // Construir los productos para la orden
            $paypalItems = collect($data['items'])->map(function ($item) {
                return [
                    'name' => $item['name'],
                    'unit_amount' => [
                        'currency_code' => 'USD',
                        'value' => number_format($item['price'], 2, '.', ''),
                    ],
                    'quantity' => $item['quantity'],
                ];
            })->toArray();

            // Crear los datos de la orden
            $orderData = [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => 'USD',
                            'value' => number_format($data['total'], 2, '.', ''),
                            'breakdown' => [
                                'item_total' => [
                                    'currency_code' => 'USD',
                                    'value' => number_format($data['total'], 2, '.', ''),
                                ],
                            ],
                        ],
                        'items' => $paypalItems,
                    ],
                ],
                'application_context' => [
                    'return_url' => route('api.paypal.success'),
                    'cancel_url' => route('api.paypal.cancel'),
                ],
            ];

            // Crear la orden en PayPal
            $response = $paypal->createOrder($orderData);

            if ($response['status'] === 'CREATED') {
                return response()->json([
                    'status' => 'success',
                    'approval_url' => $response['links'][1]['href'], // Link para que el cliente apruebe
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo crear la orden en PayPal.',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function success(Request $request)
    {
        try {
            $paypal = new PayPalClient;
            $paypal->setApiCredentials(config('paypal'));
            $paypal->setAccessToken($paypal->getAccessToken());

            $response = $paypal->capturePaymentOrder($request->input('token'));

            if ($response['status'] === 'COMPLETED') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Pago completado correctamente.',
                    'data' => $response,
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo completar el pago.',
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al capturar el pago: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function cancel()
    {
        return response()->json([
            'status' => 'error',
            'message' => 'El pago fue cancelado por el usuario.',
        ], 400);
    }
}
