<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitaMedica extends Model
{
    use HasFactory;
    protected $fillable = [
        'paciente_id', 'doctor_id', 'fecha', 'hora', 'consultorio_id',
    ];
}
