<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosFacturacion extends Model
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
        'address',
        'country_id',
        'state_id',
        'city_id',
        'deliveryarea_id',
        'zipcode',
    ];

    public function getCountry()
    {
        $country = Country::find($this->country_id);
        if($country){
            return $country->name;
        }else{
            return "";
        }           

    }

    public function getProvince()
    {
        $province = Estado::find($this->state_id);
        if($province){
            return $province->name;
        }else{
            return "";
        }
    }

    public function getCity()
    {
        $city = Cities::find($this->city_id);
        if($city){
            return $city->name;
        }else{
            return "";
        }
        
    }

    public function direccionCompleta()
    {
        return $this->identificationNac . $this->identificationNumber . ' / ' .  $this->names . ' ' . $this->surnames . ' / ' . $this->cellphonecode . $this->cellphone . ' / ' . $this->address . ' / '. $this->getCountry() . '-' . $this->getProvince() . '-' .  $this->getCity() . '-' .  $this->deliveryarea . '-' .  $this->zipcode;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}