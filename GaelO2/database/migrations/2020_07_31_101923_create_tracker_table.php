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
        Schema::create('trackers', function (Blueprint $table) {
            $table->id();
            $table->string('study_name')->nullable(true)->default(null);
            $table->unsignedBigInteger('user_id');
            $table->dateTimeTz('date', 6);
            $table->string('role')->nullable(false);
            $table->integer('visit_id')->nullable(true)->default(null);
            $table->string('action_type')->nullable(false);
            $table->json('action_details');
            $table->timestamps();
            //Dependencies
            $table->foreign('user_id')->references('id')->on('users');
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
        Schema::dropIfExists('trackers');
    }
}
