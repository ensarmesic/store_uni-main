<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $guarded = [];

    protected $casts = ['email_verified_at' => 'datetime', 'shopping_preferences' => 'array'];
}
