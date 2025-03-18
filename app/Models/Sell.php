<?php

namespace App\Models;


use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
class Sell extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection='mongodb';
    protected $collection='sells';
    protected $fillable = [
        'cart_id', 'client_id', 'direction_id', 'total', 'iva', 'purchase_method', 'paypal_order_id', 'status'
    ]; 
    public function carts()
        {
            return $this->belongsTo(Cart::class, 'cart_id', '_id');
        }
    
    public function client()
        {
            return $this->belongsTo(User::class, 'client_id', '_id');
        }
        public function products()
        {
            return $this->hasMany(ProductCart::class, 'sell_id', '_id')->where('state', 'sell');
        }
        public function direction()
        {
            return $this->belongsTo(Direction::class, 'direction_id', '_id');
        }

}
