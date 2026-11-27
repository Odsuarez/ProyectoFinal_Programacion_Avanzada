<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mesa extends Model
{
    protected $table = 'mesas';
    protected $fillable = ['numero', 'capacidad', 'estado'];
    protected $casts = ['capacidad' => 'integer'];

    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'mesa_id');
    }
}

