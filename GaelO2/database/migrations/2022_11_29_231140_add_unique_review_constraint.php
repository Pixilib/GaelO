<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('reviews', function (Blueprint $table) {
            //A user can only have on local and expert review per visit in the study (to avoid duplicated form creation)
            $table->unique(['study_name', 'visit_id', 'user_id', 'local'], 'unique_review_per_user');
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
            $table->dropUnique('unique_review_per_user');
        });
    }
};
