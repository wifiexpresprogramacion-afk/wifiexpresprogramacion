<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosDeliveryUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'identificationNac',
        'identificationNumber',
        'names',
        'surnames',
        'cellphonecode',
        'cellphone',
        'country_id',
        'state_id',
        'city_id',
        'deliveryarea_id',
        'deliveryarea',
        'costeenvio',
        'zipcode',
        'address',
    ];
}
