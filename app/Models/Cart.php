<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
class Cart extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection = 'mongodb';
    protected $collection = 'carts';

    protected $fillable = ['client_id', 'product_id', 'total'];

    protected $casts = [
        'total' => 'float'
    ];

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id', '_id'); // Ajusta el nombre de la clave foránea si es necesario
    }

    public function products()
    {
        return $this->belongsTo(Product::class, 'product_id', '_id'); // Ajusta el campo si es necesario
    }
    public function producto_cart()
 {
     return $this->hasMany(ProductsCart::class, 'cart_id', '_id'); // Ajusta el nombre de la clave foránea si es necesario
 }
}
