<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVisitTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visit_types', function (Blueprint $table) {
            $table->id()->primary();
            $table->integer('visit_group_id')->nullable(false);
            $table->string('name')->nullable(false);
            $table->string('table_review_specific')->nullable(false);
            $table->integer('visit_order')->nullable(false);
            $table->tinyInteger('local_form_needed',1)->nullable(false);
            $table->tinyInteger('qc_needed',1)->nullable(false);
            $table->tinyInteger('review_needed',1)->nullable(false);
            $table->tinyInteger('optional',1)->nullable(false);
            $table->integer('limit_low_days')->nullable(false);
            $table->integer('limit_up_days')->nullable(false);
            //EO pas de 'set' en postgresql (équivalent 'bit' mais pas supporté par Laravel)
            $table->enum('anon_profile', ['Default', 'Full'])->nullable(false);
            $table->foreign('visit_group_id')->references('id')->on('visit_groups');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('visit_types');
    }
}
