<?php

use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\Type;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function __construct()
    {
        //Needed to be allowed to update enum values (https://stackoverflow.com/questions/63209831/how-to-alter-enum-type-column-in-migration-in-testing-laravel)
        if (!Type::hasType('enum')) {
            Type::addType('enum', StringType::class);
        }
        DB::getDoctrineConnection()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('visit_groups', function (Blueprint $table) {
            $table->enum('modality', ['PT', 'MR', 'CT', 'US', 'NM', 'RTSTRUCT'])->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visit_groups', function (Blueprint $table) {
            $table->enum('modality', ['PT', 'MR', 'CT', 'US', 'NM', 'RT'])->nullable(false)->change();
        });
    }
};
