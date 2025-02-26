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
        'last_name',
        'image',
        'email',
        'phone',
        'rfc',
        'password',
        'role',
        'google_id'
    ];

    public function directions()
    {
        return $this->hasMany(Direction::class, 'direction_id', '_id'); 
    }
}
