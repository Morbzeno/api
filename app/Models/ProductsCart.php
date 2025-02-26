<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
class ProductsCart extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection = 'mongodb';
    protected $collection = 'products_cart';

    protected $fillable = ['cart_id', 'product_id', 'quantity', 'subtotal', 'state'];

    protected $casts = [
        'quantity' => 'integer',
        'subtotal' => 'float'
    ];
    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', '_id');
    }

    public function producto()
{
    return $this->belongsTo(Product::class, 'product_id', '_id'); // Ajusta el campo si es necesario
}
}
