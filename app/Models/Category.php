<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;

class Category extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection= 'mongodb';
    protected $collection= 'categories';
    protected $fillable = ['name', 'tags', 'description'];
    protected $casts = [
        'tags' => 'array',
    ];
    public function products()
    {
        return $this->hasMany(Product::class, 'category_id', '_id'); 
    }
}
