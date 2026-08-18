<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Paciente extends Model
{
    use HasFactory;

    protected $fillable = [
        'identificationNac', 'identificationNumber', 'names', 'lastname', 'name',
        'email', 'phonecell', 'avatar', 'role', 'external_id', 'external_auth', 'active',
    ];
}
