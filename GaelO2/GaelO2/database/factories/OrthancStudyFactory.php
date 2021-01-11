<?php

namespace Database\Factories;

use App\Models\OrthancStudy;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrthancStudyFactory extends Factory
{

    protected $model = OrthancStudy::class;

    public function definition()
    {
        return [
            'orthanc_id' =>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'visit_id'=> Visit::factory()->create()->id,
            'uploader_id'=> User::factory()->create()->id,
            'upload_date'=>now(),
            'acquisition_date'=>$this->faker->date(),
            'acquisition_time'=>$this->faker->time(),
            'anon_from_orthanc_id'=>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'study_uid'=>$this->faker->unique()->word,
            'study_description'=>$this->faker->word,
            'patient_orthanc_id'=>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'patient_name'=>$this->faker->word,
            'patient_id'=>$this->faker->word,
            'number_of_series'=>$this->faker->randomNumber,
            'number_of_instances'=>$this->faker->randomNumber,
            'disk_size'=>$this->faker->randomNumber,
            'uncompressed_disk_size'=>$this->faker->randomNumber
        ];
    }

    public function visitId(int $visitId){

        return $this->state(function (array $attributes) use ($visitId) {
            return [
                'uploader_id' => $visitId,
            ];
        });
    }

    public function uploaderId(int $uploaderId){

        return $this->state(function (array $attributes) use ($uploaderId) {
            return [
                'visit_id' => $uploaderId,
            ];
        });
    }
}
