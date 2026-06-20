<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DatosBasicos extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'address',
        'cellphonecode',
        'cellphone',
    ];

    protected $appends = [
        'telefono',
    ];

    public function getTelefonoAttribute()
    {
        return $this->cellphonecode . $this->cellphone;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
