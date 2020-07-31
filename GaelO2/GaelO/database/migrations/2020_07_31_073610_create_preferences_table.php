<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('preferences', function (Blueprint $table) {
            //EO No primary key?
            $table->smallInteger('patient_code_length')->nullable(false);
            $table->string('name')->nullable(false);
            $table->text('admin_email')->nullable(false);
            $table->text('email_reply_to')->nullable(false);
            $table->string('corporation')->nullable(false);
            $table->text('address')->nullable(false);
            $table->string('parse_date_import')->nullable(false)->default('m.d.Y');
            $table->string('parse_country_name')->nullable(false)->default('US');
            $table->text('orthanc_exposed_internal_address')->nullable(false);
            $table->integer('orthanc_exposed_internal_port')->nullable(false);
            $table->text('orthanc_exposed_external_address')->nullable(false);
            $table->integer('orthanc_exposed_external_port')->nullable(false);
            $table->text('orthanc_exposed_internal_login')->nullable(false);
            $table->text('orthanc_exposed_internal_password')->nullable(false);
            $table->text('orthanc_exposed_external_login')->nullable(false);
            $table->text('orthanc_exposed_external_password')->nullable(false);
            $table->text('orthanc_pacs_address')->nullable(false);
            $table->integer('orthanc_pacs_port')->nullable(false);
            $table->text('orthanc_pacs_login')->nullable(false);
            $table->text('orthanc_pacs_password')->nullable(false);
            $table->tinyInteger('use_smtp')->nullable(false);
            $table->text('smtp_host')->nullable(false);
            $table->integer('smtp_port')->nullable(false);
            $table->text('smtp_user')->nullable(false);
            $table->text('smtp_password')->nullable(false);
            $table->string('smtp_secure')->nullable(false)->default('ssl');
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
        Schema::dropIfExists('preferences');
    }
}
