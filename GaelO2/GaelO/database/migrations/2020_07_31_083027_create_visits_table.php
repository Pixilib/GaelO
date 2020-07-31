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
        Schema::table('visits', function (Blueprint $table) {
            $table->bigIncrements('id_visit')->primary();
            $table->string('creator_name')->default(null);
            $table->dateTime('creation_date')->default(null);
            $table->bigInteger('patient_code')->nullable(false);
            $table->date('acquisition_date')->default(null);
            $table->integer('visit_type_id')->nullable(false);
            $table->enum('status_done', ['Not Done','Done'])->nullable(false)->default('Not Done');
            $table->text('reason_for_not_done');
            $table->enum('upload_status', ['Not Done','Processing','Done'])->nullable(false)->default('Not Done');
            $table->enum('state_investigator_form', ['Not Done','Draft','Done'])->nullable(false)->default('Not Done');
            $table->enum('state_quality_control', ['Not Done','Wait Definitive Conclusion','Corrective Action Asked','Refused','Accepted'])->nullable(false)->default('Not Done');
            $table->string('controller_username')->default(null);
            $table->dateTime('control_date')->default(null);
            $table->tinyInteger('image_quality_control',1)->nullable(false)->default('0');
            $table->tinyInteger('form_quality_control',1)->nullable(false)->default('0');
            $table->text('image_quality_comment');
            $table->text('form_quality_comment');
            $table->string('corrective_action_username')->default(null);
            $table->dateTime('corrective_action_date')->default(null);
            $table->tinyInteger('corrective_action_new_upload',1)->nullable(false)->default('0');
            $table->tinyInteger('corrective_action_investigator_form',1)->default(null);
            $table->text('corrective_action_other');
            $table->tinyInteger('corrective_action_decision',1)->default(null);
            $table->tinyInteger('review_available',1)->nullable(false)->default('0');
            //EO pas de 'set' en postgresql (équivalent 'bit' mais pas supporté par Laravel)
            $table->enum('review_status', ['Not Done','Ongoing','Wait Adjudication','Done'])->nullable(false)->default('Not Done');
            $table->text('review_conclusion_value');
            $table->dateTime('review_conclusion_date')->default(null);
            $table->dateTime('last_reminder_upload')->default(null);
            $table->$table->tinyInteger('deleted',1)->nullable(false)->default('0');
            $table->timestamps();
            //Dependencies
            $table->foreign('patient_code')->references('code')->on('patients');
            $table->foreign('creator_name')->references('username')->on('users');
            $table->foreign('visit_type_id')->references('id')->on('visit_types');
            $table->foreign('controller_username')->references('username')->on('users');
            $table->foreign('corrective_action_username')->references('username')->on('users');
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
