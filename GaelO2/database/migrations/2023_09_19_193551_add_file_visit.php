<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->json('sent_files')->nullable(true);
        });

        DB::table('visits')->whereNull('sent_files')->update(['sent_files'=>'{}']);
        
        Schema::table('visits', function (Blueprint $table) {
            $table->json('sent_files')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visits', function (Blueprint $table) {
            $table->dropColumn('sent_files');
        });
    }
};
