<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSucursal extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 'user_id', 
    ];
}
