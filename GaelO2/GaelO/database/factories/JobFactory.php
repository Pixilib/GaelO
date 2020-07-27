<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Model;
use Faker\Generator as Faker;

$factory->define(Model::class, function (Faker $faker) {
    return array(
        array('name' => 'CRA'),
        array('name' => 'Monitor'),
        array('name' => 'Nuclearist'),
        array('name' => 'PI'),
        array('name' => 'Radiologist'),
        array('name' => 'Study nurse'),
        array('name' => 'Supervision')
    );
});
