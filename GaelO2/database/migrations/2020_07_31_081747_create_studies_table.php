<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('studies', function (Blueprint $table) {
            $table->string('name')->primary();
            $table->string('code')->unique()->nullable(false);
            $table->integer('patient_code_length')->nullable(false);
            $table->string('contact_email')->nullable(false);
            $table->boolean('controller_show_all')->default(false)->nullable(false);
            $table->boolean('monitor_show_all')->default(false)->nullable(false);
            $table->string('ancillary_of')->default(null)->nullable(true);
            $table->foreign('ancillary_of')->references('name')->on('studies');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('studies');
    }
}
