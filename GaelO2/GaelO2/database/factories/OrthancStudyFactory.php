<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\OrthancStudy;
use Faker\Generator as Faker;

$factory->define(OrthancStudy::class, function (Faker $faker) {
    return [
        'orthanc_id' =>$faker->regexify('[A-Za-z0-9]{44}'),
        'visit_id'=>$faker->unique()->randomNumber,
        'uploader_id'=>$faker->randomNumber,
        'upload_date'=>now(),
        'acquisition_date'=>$faker->date(),
        'acquisition_time'=>$faker->time(),
        'anon_from_orthanc_id'=>$faker->regexify('[A-Za-z0-9]{44}'),
        'study_uid'=>$faker->word,
        'study_description'=>$faker->word,
        'patient_orthanc_id'=>$faker->regexify('[A-Za-z0-9]{44}'),
        'patient_name'=>$faker->word,
        'patient_id'=>$faker->word,
        'number_of_series'=>$faker->randomNumber,
        'number_of_instances'=>$faker->randomNumber,
        'disk_size'=>$faker->randomNumber,
        'uncompressed_disk_size'=>$faker->randomNumber
    ];
});
