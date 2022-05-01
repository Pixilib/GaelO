<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('visit_groups', function (Blueprint $table) {
            $table->id();
            $table->string('study_name')->nullable(false);
            $table->string('name')->nullable(false);
            $table->enum('modality', ['PT', 'MR', 'CT', 'US', 'NM', 'RT'])->nullable(false);
            $table->timestamps();

            $table->unique(['study_name', 'name']);
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
        Schema::dropIfExists('visit_groups');
    }
}
