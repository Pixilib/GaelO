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
        Schema::table('roles', function (Blueprint $table) {
            $table->string('role_name')->primary();
            $table->string('user_name')->primary();
            $table->string('study_name')->primary();
            $table->timestamps();
            //Dependencies
            $table->forgein('role_name')->references('name')->on('roles');
            $table->forgein('user_name')->references('username')->on('users');
            $table->forgein('study_name')->references('name')->on('studies');
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
