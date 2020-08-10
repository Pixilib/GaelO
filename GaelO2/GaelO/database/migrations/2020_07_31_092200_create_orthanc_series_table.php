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
            $table->string('series_orthanc_id', 44)->primary();
            $table->string('study_orthanc_id', 44);
            $table->text('acquisition_date');
            $table->text('acquisition_time');
            $table->text('modality');
            $table->dateTime('acquisition_datetime', 0)->default(null);
            $table->text('series_description');
            $table->bigInteger('injected_dose')->default(null);
            $table->text('radiopharmaceutical')->default(null);
            $table->bigInteger('half_life')->default(null);
            $table->dateTime('injected_datetime', 0)->default(null);
            $table->bigInteger('injected_activity')->default(null);
            $table->integer('patient_weight')->default(null);
            $table->integer('number_of_instances')->nullable(false);
            $table->text('series_uid')->nullable(false);
            $table->text('series_number');
            $table->bigInteger('series_disk_size')->nullable(false);
            $table->bigInteger('series_uncompressed_disk_size')->nullable(false);
            $table->text('manufacturer');
            $table->text('model_name');
            $table->boolean('deleted')->default(false)->nullable(false);
            $table->timestamps();
            //Dependencies
            $table->foreign('study_orthanc_id')->references('study_orthanc_id')->on('orthanc_studies');
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
