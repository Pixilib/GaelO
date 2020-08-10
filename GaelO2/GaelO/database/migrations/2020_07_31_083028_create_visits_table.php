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
            $table->unsignedBigInteger('creator_user_id')->default(null);
            $table->dateTime('creation_date')->default(null);
            $table->unsignedBigInteger('patient_code')->nullable(false);
            $table->date('acquisition_date')->default(null);
            $table->unsignedBigInteger('visit_type_id')->nullable(false);
            $table->enum('status_done', ['Not Done','Done'])->nullable(false)->default('Not Done');
            $table->text('reason_for_not_done');
            $table->enum('upload_status', ['Not Done','Processing','Done'])->nullable(false)->default('Not Done');
            $table->enum('state_investigator_form', ['Not Done', 'Not Needed', 'Draft', 'Done'])->nullable(false)->default('Not Done');
            $table->enum('state_quality_control', ['Not Done', 'Not Needed', 'Wait Definitive Conclusion','Corrective Action Asked','Refused','Accepted'])->nullable(false)->default('Not Done');
            $table->unsignedBigInteger('controller_user_id')->default(null);
            $table->dateTime('control_date')->default(null);
            $table->boolean('image_quality_control')->nullable(false)->default(false);
            $table->boolean('form_quality_control')->nullable(false)->default(false);
            $table->text('image_quality_comment');
            $table->text('form_quality_comment');
            $table->unsignedBigInteger('corrective_action_user_id')->default(null);
            $table->dateTime('corrective_action_date')->default(null);
            $table->boolean('corrective_action_new_upload')->nullable(false)->default(false);
            $table->boolean('corrective_action_investigator_form')->default(null);
            $table->text('corrective_action_other');
            $table->boolean('corrective_action_decision')->default(null);
            $table->boolean('review_available')->nullable(false)->default(false);
            //EO pas de 'set' en postgresql (équivalent 'bit' mais pas supporté par Laravel)
            $table->enum('review_status', ['Not Done', 'Not Needed', 'Ongoing','Wait Adjudication','Done'])->nullable(false)->default('Not Done');
            $table->text('review_conclusion_value');
            $table->dateTime('review_conclusion_date')->default(null);
            $table->dateTime('last_reminder_upload')->default(null);
            $table->tinyInteger('deleted')->nullable(false)->default('0');
            $table->timestamps();
            //Dependencies
            $table->foreign('patient_code')->references('code')->on('patients');
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
