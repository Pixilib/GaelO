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
        Schema::table('visit_groups', function (Blueprint $table) {
            $table->id()->primary();
            $table->string('study_name')->nullable(false);
            //EO pas de 'set' en postgresql (équivalent 'bit' mais pas supporté par Laravel)
            $table->enum('group_modality', ['PT', 'MR', 'CT'])->nullable(false);
            //Dependencies
            $table->foreign('study_name')->references('name')->on('study');
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
