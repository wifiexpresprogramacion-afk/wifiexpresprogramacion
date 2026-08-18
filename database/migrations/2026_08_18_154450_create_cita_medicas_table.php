<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCitaMedicasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cita_medicas', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('paciente_id');
            $table->bigInteger('consultorio_id');
            $table->bigInteger('doctor_id');
            $table->date('fecha');
            $table->datetime('hora');
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
        Schema::dropIfExists('cita_medicas');
    }
}
