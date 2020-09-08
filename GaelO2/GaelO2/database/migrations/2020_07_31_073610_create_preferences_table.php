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
            $table->smallInteger('patient_code_length')->nullable(false);
            $table->string('plateform_name')->nullable(false);
            $table->text('admin_email')->nullable(false);
            $table->text('email_reply_to')->nullable(false);
            $table->string('corporation')->nullable(false);
            $table->text('url')->nullable(false);
            $table->enum('parse_date_import', ['m.d.Y', 'd.m.Y'])->nullable(false);
            $table->enum('parse_country_name', ['US', 'FR'])->nullable(false);
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
