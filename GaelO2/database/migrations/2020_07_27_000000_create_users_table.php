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
            $table->string('email')->unique()->nullable(false);
            $table->string('password')->nullable(true);
            $table->string('phone')->nullable(true);
            $table->dateTime('creation_date', 6)->nullable(false);
            $table->dateTime('last_connection', 6)->nullable(true);
            $table->integer('attempts')->default(0)->nullable(false);
            $table->boolean('administrator')->default(false)->nullable(false);
            $table->unsignedInteger('center_code')->nullable(false);
            $table->enum('job', ['CRA', 'Monitor', 'Nuclearist','PI', 'Radiologist', 'Study nurse', 'Supervision' ])->nullable(false);
            $table->string('orthanc_address')->nullable(true);
            $table->string('orthanc_login')->nullable(true);
            $table->string('orthanc_password')->nullable(true);
            $table->string('api_token', 80) ->unique()->nullable()->default(null);
            $table->dateTime('email_verified_at')->nullable()->default(null);
            $table->string('onboarding_version')->nullable(false)->default('0.0.0');
            $table->softDeletes();
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('center_code')->references('code')->on('centers');

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
