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
            $table->string('role_name');//PK
            $table->string('user_name');//PK
            $table->string('study_name');//PK
            $table->primary(['role_name', 'user_name', 'study_name']);
            $table->timestamps();
            //Dependencies
            $table->foreign('user_name')->references('username')->on('users');
            $table->foreign('study_name')->references('name')->on('studies');
            $table->foreign('role_name')->references('role_name')->on('select_roles');

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
