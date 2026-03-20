<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'nickname',
        'password',
        'group_code',
        'is_host',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}