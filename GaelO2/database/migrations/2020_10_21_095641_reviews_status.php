<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ReviewsStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews_status', function (Blueprint $table) {

            $table->id();
            $table->unsignedBigInteger('visit_id');
            $table->string('study_name')->nullable(false);
            $table->boolean('review_available')->nullable(false)->default(false);
            $table->json('target_lesions')->nullable(true);
            $table->string('review_status')->nullable(false)->default('Not Done');
            $table->text('review_conclusion_value')->nullable(true)->default(null);
            $table->dateTimeTz('review_conclusion_date', 6)->nullable(true)->default(null);
            $table->timestamps();

            //A visit ID in a Study have only one status
            $table->unique(['study_name', 'visit_id']);
            $table->foreign('visit_id')->references('id')->on('visits');
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
        Schema::dropIfExists('reviews_status');
    }
}
