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
            $table->string('study_orthanc_id')->primary();
            $table->text('modality');
            $table->text('acquisition_date');
            $table->text('acquisition_time');
            $table->dateTime('acquisition_datetime', 0)->default(null);
            $table->text('series_description');
            $table->bigInteger('injected_dose')->default(null);
            $table->text('radiopharmaceutical');
            $table->bigInteger('half_life')->default(null);
            $table->dateTime('injected_datetime', 0)->default(null);
            $table->text('injected_time');
            $table->bigInteger('injected_activity')->default(null);
            $table->integer('patient_weight')->default(null);
            $table->string('series_orthanc_id')->nullable(false);
            $table->integer('number_of_instances')->nullable(false);
            $table->text('series_uid')->nullable(false);
            $table->text('series_number');
            $table->integer('series_disk_size')->nullable(false);
            $table->integer('series_uncompressed_disk_size')->nullable(false);
            $table->text('manufacturer');
            $table->text('model_name');
            $table->integer('deleted')->default(0)->nullable(false);
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
        Schema::dropIfExists('orthanc_series');
    }
}
