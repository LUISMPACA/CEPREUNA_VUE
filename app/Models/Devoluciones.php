<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Devoluciones extends Model
{
    protected $fillable = [
        'nombres',
        'nro_documento',
        'fecha',
        'nro_registro',
        'procede',
        'pagos_id',
    ];
}
