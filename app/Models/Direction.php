<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
class Direction extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection = 'mongodb';
    protected $collection = 'directions';

    protected $fillable = [
        'user_id',
        'state',
        'city',
        'postal_code',
        'name',
        'residence',
        'description',
        'default',
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', '_id'); 
    }
    public function sell()
    {
        return $this->hasOne(Sell::class, 'direction_id', '_id');
    }
}
