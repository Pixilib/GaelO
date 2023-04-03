<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dicom_series', function (Blueprint $table) {
            $table->dropColumn('injected_activity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dicom_series', function (Blueprint $table) {
            $table->bigInteger('injected_activity')->nullable(true)->default(null);
        });
    }
};
