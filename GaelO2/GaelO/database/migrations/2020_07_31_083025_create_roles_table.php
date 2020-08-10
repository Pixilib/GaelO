<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->enum('role_name', ['Investigator', 'Monitor', 'Controller', 'Supervisor'])->nullable(false);
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->string('study_name')->nullable(false);;
            $table->timestamps();
            //Dependencies
            $table->primary(['role_name', 'user_id', 'study_name']);
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
        Schema::dropIfExists('roles');
    }
}
