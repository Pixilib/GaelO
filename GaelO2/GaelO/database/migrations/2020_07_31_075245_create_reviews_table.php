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
        Schema::table('reviews', function (Blueprint $table) {
            $table->integer('id_review')->primary();
            $table->integer('id_visit')->nullable(false);
            $table->string('user_name')->nullable(false);
            $table->dateTime('review_date', 0)->nullable(false);
            $table->tinyInteger('validated',1)->nullable(false)->default(0);
            $table->tinyInteger('is_local',1)->nullable(false)->default(1);
            $table->tinyInteger('is_adjudication',1)->nullable(false)->default(0);
            $table->text('sent_files')->nullable(false);
            $table->tinyInteger('deleted',1)->nullable(false)->default(0);
            $table->timestamps();
            //Dependencies
            $table->foreign('id_visit')->references('id_visit')->on('visits');
            $table->forgein('user_name')->references('username')->on('users');
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
