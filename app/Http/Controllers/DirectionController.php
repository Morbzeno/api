<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Direction;

class DirectionController extends Controller
{
    public function index (Request $request){
        $query = Direction::query();
        if ($request->has('search')){
            $search = $request->input('search');

            $query->where('name', 'regexp', new \MongoDB\BSON\Regex($search, 'i'));
        
    }
        // return response()->json(Direction::all());
        //->pagination(15)
        $direction = $query->with('user')->get();
        return response()->json($direction);
    
    }

    public function show($id){
        $direction = Direction::with('user')->find($id);
        if(!$direction){
            return response()->json([
                'response' => 'direccion no encontrada'
            ], 404);
        }
        return response()->json([
            $direction
        ],200);
    }
    public function store(Request $request){
        $request->validate([
            'user_id' => 'required',
            'state' => 'required',
            'city' => 'required',
              'postal_code' => 'required',
             'name' => 'required',
        'residence' => 'required',
        'description' => 'required',
        ]);
        $direction = new Direction();
        $direction->user_id = $request->user_id;
        $direction->state = $request->state;
        $direction->city = $request->city;
        $direction->postal_code = $request->postal_code;
        $direction->name = $request->name;
        $direction->residence = $request->residence;
        $direction->description = $request->description;
        $direction->save();
        return response()->json([
            'response' => 'direccion insertada con exito',
            'datos' => $direction
        ],201);

    }
    public function update(Request $request, $id){
        $direction = Direction::find($id);
        if(!$direction){
            return response()->json([
                'response' => 'no se encontro la direccion'
            ],400); 
        }
        $validate=$request->validate([
            'state' => '',
            'city' => '',
              'postal_code' => '',
             'name' => '',
        'residence' => '',
        'description' => '',
        ]);
        $direction->update($validate);
        return response()->json([
            'response' => 'direccion insertada con exito',
            'datos' => $direction
        ],200);
    }
    public function destroy($id){
        $direction = Direction::find($id);
        if(!$direction){
            return response()->json([
                'response' => 'direccion no encontrada'
            ], 400);
        }
        $direction->delete();
        return response()->json([
            'response' => 'direccion borrada con exito'
        ],200);
    }
}
