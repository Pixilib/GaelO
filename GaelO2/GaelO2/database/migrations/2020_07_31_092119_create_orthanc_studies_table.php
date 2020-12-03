<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrthancStudiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orthanc_studies', function (Blueprint $table) {
            $table->string('orthanc_id', 44)->primary();
            $table->unsignedBigInteger('visit_id')->nullable(false);
            $table->unsignedBigInteger('uploader_id')->nullable(false);
            $table->dateTime('upload_date', 6)->nullable(false);
            $table->date('acquisition_date')->nullable(true);
            $table->time('acquisition_time')->nullable(true);
            $table->string('anon_from_orthanc_id', 44)->nullable(false);
            $table->string('study_uid', 256)->nullable(false);
            $table->text('study_description')->nullable(true);
            $table->string('patient_orthanc_id', 44)->nullable(false);
            $table->text('patient_name')->nullable(true);
            $table->text('patient_id')->nullable(true);
            $table->integer('number_of_series')->nullable(false);
            $table->integer('number_of_instances')->nullable(false);
            $table->integer('disk_size')->nullable(false);
            $table->integer('uncompressed_disk_size')->nullable(false);
            $table->softDeletes();
            $table->timestamps();
            //Dependencies
            $table->foreign('visit_id')->references('id')->on('visits');
            $table->foreign('uploader_id')->references('id')->on('users');
            $table->unique('study_uid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orthanc_studies');
    }
}
