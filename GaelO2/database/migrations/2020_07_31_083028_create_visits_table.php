<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visits', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('creator_user_id')->nullable(false);
            $table->dateTimeTz('creation_date', 6)->nullable(false);
            $table->string('patient_id')->nullable(false);
            $table->date('visit_date')->nullable(true)->default(null);
            $table->unsignedBigInteger('visit_type_id')->nullable(false);
            $table->string('status_done')->nullable(false)->default('Not Done');
            $table->string('reason_for_not_done', 256)->nullable(true)->default(null);
            $table->string('upload_status')->nullable(false)->default('Not Done');
            $table->string('state_investigator_form')->nullable(false)->default('Not Done');
            $table->string('state_quality_control')->nullable(false)->default('Not Done');
            $table->unsignedBigInteger('controller_user_id')->nullable(true)->default(null);
            $table->dateTimeTz('control_date', 6)->nullable(true)->default(null);
            $table->boolean('image_quality_control')->nullable(false)->default(false);
            $table->boolean('form_quality_control')->nullable(false)->default(false);
            $table->string('image_quality_comment', 256)->nullable(true)->default(null);
            $table->string('form_quality_comment', 256)->nullable(true)->default(null);
            $table->unsignedBigInteger('corrective_action_user_id')->nullable(true)->default(null);
            $table->dateTimeTz('corrective_action_date', 6)->nullable(true)->default(null);
            $table->boolean('corrective_action_new_upload')->nullable(false)->default(false);
            $table->boolean('corrective_action_investigator_form')->nullable(false)->default(false);
            $table->string('corrective_action_comment', 256)->nullable(true)->default(null);
            $table->boolean('corrective_action_applied')->nullable(true)->default(null);
            $table->dateTimeTz('last_reminder_upload', 6)->nullable(true)->default(null);
            $table->softDeletes();
            $table->timestamps();
            //Dependencies
            $table->foreign('patient_id')->references('id')->on('patients');
            $table->foreign('visit_type_id')->references('id')->on('visit_types');
            $table->foreign('creator_user_id')->references('id')->on('users');
            $table->foreign('controller_user_id')->references('id')->on('users');
            $table->foreign('corrective_action_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visits');
    }
}
