<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->string('study_name')->nullable(false);
            $table->unsignedBigInteger('visit_id')->nullable(false);
            $table->unsignedBigInteger('user_id')->nullable(false);
            $table->dateTimeTz('review_date', 6)->nullable(false);
            $table->boolean('validated')->nullable(false);
            $table->boolean('local')->nullable(false);
            $table->boolean('adjudication')->nullable(false);
            $table->json('sent_files')->nullable(false);
            $table->json('review_data')->nullable(false);
            $table->softDeletes();
            $table->timestamps();
            //Dependencies
            $table->foreign('study_name')->references('name')->on('studies');
            $table->foreign('visit_id')->references('id')->on('visits');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reviews', function (Blueprint $table) {
            Schema::dropIfExists('reviews');
        });
    }
}
