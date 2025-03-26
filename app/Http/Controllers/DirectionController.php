<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Direction;
use App\Models\User;

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

    public function show(string $id){
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
            'user_id' => 'required|exists:users,id',
            'state' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'postal_code' => 'required|digits:5', // Suponiendo que es código postal de 5 dígitos
            'name' => 'required|string|max:255',
            'residence' => 'required|string|max:255',
            'description' => 'required|string|max:500',
            'default' => 'sometimes|boolean', // Permite omitirlo si no es obligatorio
        ]);
        

        $total = Direction::where('user_id', $request->user_id)->count();

        if ($total >=3){
            return response()->json(['message'=> 'no se pueden meter mas de 3 direcciones'],400);
        }

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
    public function update(Request $request, string $id){
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
            'default' => 'boolean'
        ]);
        $direction->update($validate);
        return response()->json([
            'response' => 'direccion insertada con exito',
            'datos' => $direction
        ],200);
    }
    public function destroy(string $id){
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