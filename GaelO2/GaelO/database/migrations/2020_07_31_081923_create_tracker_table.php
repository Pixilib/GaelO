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
        Schema::table('tracker', function (Blueprint $table) {
            $table->dateTime('date', 6)->primary();
            $table->string('user_name')->primary();
            $table->string('role_name')->primary();
            $table->string('study_name')->default(null);
            $table->integer('id_visit')->default(null);
            $table->string('action_type')->nullable(false);
            $table->text('action_details');
            $table->timestamps();
            //Dependencies
            $table->foreign('user_name')->references('username')->on('users');
            $table->foreign('role_name')->references('role_name')->on('users');
            $table->foreign('study_name')->references('study_name')->on('studies');
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
