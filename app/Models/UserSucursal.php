<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSucursal extends Model
{
    use HasFactory;

    protected $fillable = [
        'router_id', 'user_id', 
    ];

    /**
     * Get the router associated with the user's sucursal assignment.
     */
    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'router_id');
    }
}
