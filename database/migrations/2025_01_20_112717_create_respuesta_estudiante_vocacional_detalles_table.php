<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRespuestaEstudianteVocacionalDetallesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('respuesta_estudiante_vocacional_detalles', function (Blueprint $table) {
            $table->id();
            $table->char("nro_documento",30);
            $table->smallInteger('puntaje');
            $table->enum("tipo", ["0", "1"])->comment("0:No  1:Si");

            $table->unsignedBigInteger('preguntas_id');
            $table->foreign('preguntas_id')->references('id')->on('preguntas_vocacionales');

            $table->unsignedBigInteger('respuesta_id');
            $table->foreign('respuesta_id')->references('id')->on('respuesta_estudiante_vocacional');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('respuesta_estudiante_vocacional_detalles');
    }
}
