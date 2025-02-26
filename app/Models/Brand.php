<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
class Brand extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection= 'mongodb';
    protected $collection= 'brands';

    protected $fillable = [
        "name",
        "description",
        "contact",
        "image"
    ];
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', '_id'); 
    }
}

