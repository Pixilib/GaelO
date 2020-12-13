<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\OrthancSeries;
use Faker\Generator as Faker;

$factory->define(OrthancSeries::class, function (Faker $faker) {
    return [
        'orthanc_id' =>$faker->regexify('[A-Za-z0-9]{44}'),
        'orthanc_study_id' =>$faker->regexify('[A-Za-z0-9]{44}'),
        'acquisition_date'=>$faker->date(),
        'acquisition_time'=>$faker->time(),
        'modality'=>$faker->word,
        'series_description'=>$faker->word,
        'injected_dose'=>$faker->randomNumber,
        'radiopharmaceutical'=>$faker->word,
        'half_life'=>$faker->randomNumber,
        'injected_datetime'=>$faker->dateTime(),
        'injected_activity'=>$faker->randomNumber,
        'patient_weight'=>$faker->randomNumber,
        'number_of_instances'=>$faker->randomNumber,
        'series_uid'=>$faker->word,
        'series_number'=>$faker->word,
        'disk_size'=>$faker->randomNumber,
        'uncompressed_disk_size'=>$faker->randomNumber,
        'manufacturer'=>$faker->word,
        'model_name'=>$faker->word,
    ];
});
