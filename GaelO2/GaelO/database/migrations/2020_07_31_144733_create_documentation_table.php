<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDocumentationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('documentation', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(false);
            $table->date('document_date')->nullable(false);
            $table->string('study_name')->nullable(false);
            $table->string('version')->nullable(false);
            $table->boolean('investigator')->default(false)->nullable(false);
            $table->boolean('controller')->default(false)->nullable(false);
            $table->boolean('monitor')->default(false)->nullable(false);
            $table->boolean('reviewer')->default(false)->nullable(false);
            $table->boolean('deleted')->default(false)->nullable(false);
            $table->timestamps();
            //EO convention nom clé multiple?
            $table->foreign('study_name')->references('name')->on('studies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('documentation');
    }
}
