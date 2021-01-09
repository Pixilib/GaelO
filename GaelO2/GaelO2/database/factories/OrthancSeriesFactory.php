<?php

namespace Database\Factories;

use App\Model\OrthancSeries;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrthancSeriesFactory extends Factory
{

    protected $model = OrthancSeries::class;

    public function definition()
    {
        return [
            'orthanc_id' =>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'orthanc_study_id' =>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'acquisition_date'=>$this->faker->date(),
            'acquisition_time'=>$this->faker->time(),
            'modality'=>$this->faker->word,
            'series_description'=>$this->faker->word,
            'injected_dose'=>$this->faker->randomNumber,
            'radiopharmaceutical'=>$this->faker->word,
            'half_life'=>$this->faker->randomNumber,
            'injected_datetime'=>$this->faker->dateTime(),
            'injected_activity'=>$this->faker->randomNumber,
            'patient_weight'=>$this->faker->randomNumber,
            'number_of_instances'=>$this->faker->randomNumber,
            'series_uid'=>$this->faker->word,
            'series_number'=>$this->faker->word,
            'disk_size'=>$this->faker->randomNumber,
            'uncompressed_disk_size'=>$this->faker->randomNumber,
            'manufacturer'=>$this->faker->word,
            'model_name'=>$this->faker->word,
        ];
    }
}
