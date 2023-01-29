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
        Schema::table('users', function (Blueprint $table) {
            $table->string('job')->nullable(false)->change();
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->string('gender')->nullable(true)->default(null)->change();
            $table->string('inclusion_status')->default('Included')->nullable(false)->change();
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('visit_groups', function (Blueprint $table) {
            $table->string('modality')->nullable(false)->change();
        });

        Schema::table('visit_types', function (Blueprint $table) {
            $table->string('anon_profile')->default('Default')->nullable(false)->change();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {

    }
};
