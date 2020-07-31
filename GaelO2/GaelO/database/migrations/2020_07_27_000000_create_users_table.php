<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Renamed for testing purposes
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('lastname')->nullable(true);
            $table->string('firstname')->nullable(true);
            $table->string('username')->unique();
            $table->string('email')->unique()->nullable(false);
            $table->string('password')->nullable(false);
            $table->string('password_previous1')->nullable(true);
            $table->string('password_previous2')->nullable(true);
            $table->string('password_temporary')->nullable(true);
            $table->string('phone')->nullable(true);
            $table->dateTime('last_password_update')->nullable(false);
            $table->dateTime('creation_date')->nullable(false);
            $table->dateTime('last_connexion')->nullable(true);
            //EO pas de 'set' en postgresql (équivalent 'bit' mais pas supporté par Laravel)
            $table->enum('status', ['Blocked','Deactivated','Unconfirmed','Activated'])->default('Unconfirmed')->nullable(false);
            $table->integer('attempts')->default(0)->nullable(false);
            $table->boolean('administrator')->default(false)->nullable(false);
            $table->integer('center_code')->nullable(false);
            $table->string('job_name')->nullable(false);
            $table->string('orthanc_address')->nullable(true);
            $table->string('orthanc_login')->nullable(true);
            $table->string('orthanc_password')->nullable(true);
            $table->string('api_token', 80) ->unique()->nullable()->default(null);
            //SK rememberToken sert a CSRF, peut etre pas utile si JWT a documenter
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('center_code')->references('code')->on('centers');
            $table->foreign('job_name')->references('name')->on('jobs');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
