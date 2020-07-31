<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTrackerTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tracker', function (Blueprint $table) {
            $table->dateTime('date');
            $table->string('user_name');
            $table->string('role');
            $table->primary(['date', 'user_name', 'role']);
            $table->string('study_name')->default(null);
            $table->integer('id_visit')->default(null);
            $table->string('action_type')->nullable(false);
            $table->text('action_details');
            $table->timestamps();
            //Dependencies
            $table->foreign('user_name')->references('username')->on('users');
            $table->foreign('study_name')->references('name')->on('studies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tracker');
    }
}
