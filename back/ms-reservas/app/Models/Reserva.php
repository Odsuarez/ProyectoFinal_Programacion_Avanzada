<?php
declare(strict_types=1);
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reserva extends Model
{
    protected $table = 'reservas';
    protected $fillable = [
        'nombre_cliente', 'telefono_cliente', 'cantidad_personas',
        'fecha', 'hora', 'observaciones', 'estado', 'mesa_id',
    ];
    protected $casts = ['cantidad_personas' => 'integer', 'mesa_id' => 'integer'];

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }
}

