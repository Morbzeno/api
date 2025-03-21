<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Product extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection= 'mongodb';
    protected $collection= 'products';
    protected $fillable = [
        'category_id', 'name', 'brand_id', 'retail_price', 'buy_price', 'sku',
        'bar_code', 'stock', 'description', 'state', 'wholesale_price', 'image'];

        protected $casts = [
            'image' => 'array',
        ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', '_id');
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', '_id');
    }
    public function producto_cart()
 {
     return $this->hasMany(ProductsCart::class, 'product_id', '_id'); // Ajusta el nombre de la clave foránea si es necesario
 }
    protected $hidden = ['category_id', 'brand_id'];
}
