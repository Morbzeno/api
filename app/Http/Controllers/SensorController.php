<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sensor;

class SensorController extends Controller
{
    public function store(Request $request) {
        $data = Sensor::first();
    
        if ($data) {
            $data->update([
                'lux' => $request->input('lux'),
                'humity' => $request->input('humity'),
                'temp' => $request->input('temp'),
                'smoke' => $request->input('smoke'),
            ]);
        } else {
            $data = Sensor::create([
                'lux' => $request->input('lux'),
                'humity' => $request->input('humity'),
                'temp' => $request->input('temp'),
                'smoke' => $request->input('smoke'),
            ]);
        }
    
        return response()->json([
            'message' => 'Datos actualizados',
            'data' => $data
        ], 200);
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
