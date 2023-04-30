<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InscripcionCurso extends Model
{
    // public function curso(){
    //     return $this->belongsTo('App\Models\Curso','cursos_id');
    // }
    public function curso(){
        return $this->hasOneThrough(Curso::class,CurriculaDetalle::class,'id','id','curricula_detalles_id','cursos_id');
    }
}
