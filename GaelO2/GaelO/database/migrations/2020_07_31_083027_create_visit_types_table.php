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
        Schema::create('visit_types', function (Blueprint $table) {
            $table->id();
            $table->integer('visit_group_id')->nullable(false);
            $table->string('name')->nullable(false);
            $table->string('table_review_specific')->nullable(false);
            $table->integer('visit_order')->nullable(false);
            $table->boolean('local_form_needed')->default(true)->nullable(false);
            $table->boolean('qc_needed')->default(true)->nullable(false);
            $table->boolean('review_needed')->default(true)->nullable(false);
            $table->boolean('optional')->default(false)->nullable(false);
            $table->integer('limit_low_days')->nullable(false);
            $table->integer('limit_up_days')->nullable(false);
            $table->enum('anon_profile', ['Default', 'Full'])->default('Default')->nullable(false);
            //Foreign keys
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
