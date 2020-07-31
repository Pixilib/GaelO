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
            $table->integer('id_review')->primary();
            $table->integer('id_visit')->nullable(false);
            $table->string('user_name')->nullable(false);
            $table->dateTime('review_date')->nullable(false);
            $table->tinyInteger('validated')->nullable(false)->default(0);
            $table->tinyInteger('is_local')->nullable(false)->default(1);
            $table->tinyInteger('is_adjudication')->nullable(false)->default(0);
            $table->text('sent_files')->nullable(false);
            $table->tinyInteger('deleted')->nullable(false)->default(0);
            $table->timestamps();
            //Dependencies
            $table->foreign('id_visit')->references('id_visit')->on('visits');
            $table->foreign('user_name')->references('username')->on('users');
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
