<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAffiliatedCentersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('affiliated_centers', function (Blueprint $table) {
            $table->id();
            $table->string('user_name')->nullable(false);
            $table->integer('center_code')->nullable(false);
            $table->timestamps();
            //Dependencies
            $table->foreign('center_code')->references('code')->on('centers');
            $table->foreign('user_name')->references('username')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affiliated_centers');
    }
}
