<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
class User extends Model implements AuthenticatableContract
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection= 'mongodb';
    protected $collection= 'Users';
    protected $fillable = [
        'name',
        'lastName',
        'image',
        'email',
        'phone',
        'rfc',
        'socialMedia',
        'password',
        'role',
        'google_id'
    ];

    public function direction()
    {
        return $this->hasMany(Direction::class, 'user_id', '_id'); 
    }
    public function carts(){
        return $this->hasOne(Cart::class, 'Cart_id', '_id' );
    }
}
