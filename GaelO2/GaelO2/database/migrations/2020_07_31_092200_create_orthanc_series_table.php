<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrthancSeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orthanc_series', function (Blueprint $table) {
            $table->string('orthanc_id', 44)->primary();
            $table->string('orthanc_study_id', 44)->nullable(false);
            $table->date('acquisition_date')->nullable(true)->default(null);
            $table->time('acquisition_time')->nullable(true)->default(null);
            $table->text('modality')->nullable(true)->default(null);
            $table->text('series_description')->nullable(true)->default(null);
            $table->bigInteger('injected_dose')->nullable(true)->default(null);
            $table->text('radiopharmaceutical')->nullable(true)->default(null);
            $table->bigInteger('half_life')->nullable(true)->default(null);
            $table->dateTime('injected_datetime', 0)->nullable(true)->default(null);
            $table->bigInteger('injected_activity')->nullable(true)->default(null);
            $table->integer('patient_weight')->nullable(true)->default(null);
            $table->integer('number_of_instances')->nullable(false);
            $table->text('series_uid')->nullable(false);
            $table->text('series_number')->nullable(true)->default(null);
            $table->integer('series_disk_size')->nullable(false);
            $table->integer('series_uncompressed_disk_size')->nullable(false);
            $table->text('manufacturer')->nullable(true)->default(null);
            $table->text('model_name')->nullable(true)->default(null);
            $table->softDeletes();
            $table->timestamps();
            //Dependencies
            $table->foreign('orthanc_study_id')->references('orthanc_id')->on('orthanc_studies');
            $table->unique('series_uid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orthanc_series');
    }
}
