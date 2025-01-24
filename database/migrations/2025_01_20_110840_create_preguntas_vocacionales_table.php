<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreguntasVocacionalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preguntas_vocacionales', function (Blueprint $table) {
            $table->id();
            $table->text("denominacion");
            $table->enum("tipo", ["1", "2"])->comment("1:vocacional  2:actitudinal");
            $table->enum('area', ['1', '2', '3'])->comment('1: Sociales, 2: Ingeniería, 3: Biología');
            $table->smallInteger('puntaje');
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
        Schema::dropIfExists('preguntas_vocacionales');
    }
}
