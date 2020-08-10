<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->unsignedBigInteger('code')->primary();
            $table->string('first_name')->default(null);
            $table->string('last_name')->default(null);
            $table->string('gender')->default(null);
            $table->integer('birth_day')->default(null);
            $table->integer('birth_month')->default(null);
            $table->integer('birth_year')->default(null);
            $table->date('registration_date')->nullable(false);
            $table->string('investigator_name')->default(null);
            $table->unsignedInteger('center_code')->default(null);
            $table->string('study_name')->default(null);
            $table->boolean('withdraw')->default(false)->nullable(false);
            $table->string('withdraw_reason')->default(null);
            $table->date('withdraw_date')->default(null);
            $table->timestamps();
            //Dependencies
            $table->foreign('study_name')->references('name')->on('studies');
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
        Schema::dropIfExists('patients');
    }
}
