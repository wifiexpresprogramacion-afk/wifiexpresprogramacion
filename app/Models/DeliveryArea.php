<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeliveryArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'state_id',
        'city_id',
        'comercio_id',
        'name',
        'distance',
        'coste',
    ];

    public function comercios()
    {
        return $this->hasMany(Comercio::class, 'id', 'comercio_id');
    }    

    public function country()
    {
        return $this->hasOne(Country::class, 'id', 'country_id');
    }

    public function province()
    {
        return $this->hasOne(Estado::class, 'id', 'state_id');
    }

    public function city()
    {
        return $this->hasOne(Cities::class, 'id', 'city_id');
    }

    public function getCoste()
    {
        $settingComercio = SettingComercio::where('comercio_id', $this->comercio_id)->first();

        $setting = Setting::where('user_id', $this->user_id)->first();

        // if(auth()->user()){
        //     $settingComercio = SettingComercio::where('user_id', auth()->user()->id)->first();
        //     if($settingComercio){
        //         $currency = $settingComercio->currency;
        //     }else{
        //         $currency = $setting->currency;
        //     }            
        //     $currency = request()->cookie('currency');
        // }else{
        //     $currency = request()->cookie('currency');
        // }        
        $currency = request()->cookie('currency');

        if(empty($currency) || is_null($currency) ){
            $setting = SettingComercio::where('comercio_id', 1)->first();
            $currency = $setting->currency;
        }
        
        $tasaValues = Tasa::where('comercio_id', $this->comercio_id)->where('status', 'activo')->first();

        if(!$tasaValues){
            $tasa = 1;
        }else{
            $tasa = $tasaValues->tasa;
        }
        
        switch ($currency) {
            case 'Bs':
                return round($tasa * $this->coste, 2);
                break;            
            case '$':
                return round($this->coste, 2);                
                break;
        }
    }
}