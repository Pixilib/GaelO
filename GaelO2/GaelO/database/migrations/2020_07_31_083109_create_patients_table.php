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
        Schema::table('patients', function (Blueprint $table) {
            $table->bigInteger('code')->primary();
            $table->string('first_name',1)->default(null);
            $table->string('last_name',1)->default(null);
            $table->string('gender',1)->default(null);
            $table->integer('birth_day')->default(null);
            $table->integer('birth_month')->default(null);
            $table->integer('birth_year')->default(null);
            $table->date('registration_date')->nullable(false);
            $table->text('investigator_name');
            $table->integer('center')->default(null);
            $table->string('study_name')->default(null);
            $table->text('withdraw_reason');
            $table->tinyInteger('withdraw',1)->default(0)->nullable(false);
            $table->date('withdraw_date')->default(null);
            $table->timestamps();
            //Dependencies
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
        Schema::dropIfExists('patients');
    }
}
