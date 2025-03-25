<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;

class SensorController extends Controller
{
    public function store (Request $request){
        $data = Sensor::Create([
            'lux' => $request->input('lux'),
            'humity' => $request->input('humity'),
            'temp' => $request->input('temp'),
            'smoke' => $request->input('smoke'),
        ]);
    return response()->json([
        'message' => 'Datos guardados',
        'data' => $data

    ],201);
    }

    public function index (){
        $data = Sensor::all();

        if(!$data){
            return response()->json([
                'message' => 'no se encontraron datos :p',
            ],404);
        }
        return response()->json([
            'message' => 'Datos de los sensores recuperados:',
            'data'=> $data
        ],200);
    }
}
