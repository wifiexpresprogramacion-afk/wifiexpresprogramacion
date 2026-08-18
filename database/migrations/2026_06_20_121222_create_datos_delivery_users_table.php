<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDatosDeliveryUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('datos_delivery_users', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('user_id');
            $table->string('identificationNac');
            $table->string('identificationNumber');
            $table->string('names');
            $table->string('surnames');
            $table->string('cellphonecode');
            $table->string('cellphone');
            $table->bigInteger('country_id');
            $table->bigInteger('state_id');
            $table->bigInteger('city_id');
            $table->bigInteger('deliveryarea_id');
            $table->string('deliveryarea');
            $table->decimal('costeenvio', 12, 2);
            $table->string('zipcode');
            $table->string('address');
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
        Schema::dropIfExists('datos_delivery_users');
    }
}
