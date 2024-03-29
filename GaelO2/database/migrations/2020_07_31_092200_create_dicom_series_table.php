<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDicomSeriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dicom_series', function (Blueprint $table) {
            $table->string('series_uid', 256)->primary();
            $table->string('study_instance_uid', 256)->nullable(false);
            $table->string('orthanc_id', 44)->nullable(false);
            $table->date('acquisition_date')->nullable(true)->default(null);
            $table->time('acquisition_time')->nullable(true)->default(null);
            $table->text('modality')->nullable(true)->default(null);
            $table->text('series_description')->nullable(true)->default(null);
            $table->bigInteger('injected_dose')->nullable(true)->default(null);
            $table->text('radiopharmaceutical')->nullable(true)->default(null);
            $table->bigInteger('half_life')->nullable(true)->default(null);
            $table->time('injected_time', 0)->nullable(true)->default(null);
            $table->dateTimeTz('injected_datetime', 0)->nullable(true)->default(null);
            $table->bigInteger('injected_activity')->nullable(true)->default(null);
            $table->integer('patient_weight')->nullable(true)->default(null);
            $table->integer('number_of_instances')->nullable(false);
            $table->text('series_number')->nullable(true)->default(null);
            $table->integer('disk_size')->nullable(false);
            $table->integer('uncompressed_disk_size')->nullable(false);
            $table->text('manufacturer')->nullable(true)->default(null);
            $table->text('model_name')->nullable(true)->default(null);
            $table->softDeletes();
            $table->timestamps();
            //Dependencies
            $table->foreign('study_instance_uid')->references('study_uid')->on('dicom_studies');
            $table->unique('orthanc_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dicom_series');
    }
}
