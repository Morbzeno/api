<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Auth\Authenticatable;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;


class Sensor extends Model
{
    use HasApiTokens, Notifiable, Authenticatable;
    protected $connection= 'mongodb';
    protected $collection= 'sensors';
    protected $fillable = [
        'lux', 'humity', 'temp', 'smoke'];
}
