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
        'category_id', 'name', 'brand_id', 'sell_price', 'buy_price',
        'bar_code', 'stock', 'description', 'state', 'wholesale_price', 'image'];

        protected $casts = [
            'image' => 'array',
        ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', '_id');
    }
}
