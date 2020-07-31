<?php

use Illuminate\Database\Seeder;

class SelectRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('select_roles')->insert(array(
            array('role_name' => 'Controller'),
            array('role_name' => 'Investigator'),
            array('role_name' => 'Monitor'),
            array('role_name' => 'Supervisor')
        ));
    }
}
