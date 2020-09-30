<?php

use App\Study;
use Illuminate\Database\Seeder;

class StudySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        factory(Study::class, 10)->create();
    }
}
