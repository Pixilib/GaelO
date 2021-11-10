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
            $table->string('number')->nullable(false);
            $table->string('firstname', 1)->nullable(true)->default(null);
            $table->string('lastname', 1)->nullable(true)->default(null);
            $table->enum('gender', ['M', 'F'])->nullable(true)->default(null);
            $table->integer('birth_day')->nullable(true)->default(null);
            $table->integer('birth_month')->nullable(true)->default(null);
            $table->integer('birth_year')->nullable(true)->default(null);
            $table->date('registration_date')->nullable(true);
            $table->string('investigator_name')->default(null);
            $table->unsignedInteger('center_code')->default(null);
            $table->string('study_name')->default(null);
            $table->enum('inclusion_status', ['Included', 'Not Included', 'Excluded', 'Withdrawn'])->default('Included')->nullable(false);
            $table->string('withdraw_reason')->nullable(true)->default(null);
            $table->date('withdraw_date')->nullable(true)->default(null);
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
