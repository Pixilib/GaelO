<?php

use App\VisitGroup;
use App\VisitType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VisitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('visit_groups')->insert([
            'study_name' => 'Study test',
            'modality' => 'PT'
        ]);
        DB::table('visit_types')->insert([
            'visit_group_id' => 1,
            'name' => 'Test',
            'visit_order' => 1,
            'limit_low_days' => 1,
            'limit_up_days' => 10,
        ]);
    }
}
