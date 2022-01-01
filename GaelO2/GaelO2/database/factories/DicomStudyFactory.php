<?php

namespace Database\Factories;

use App\GaelO\Util;
use App\Models\DicomStudy;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Eloquent\Factories\Factory;

class DicomStudyFactory extends Factory
{

    protected $model = DicomStudy::class;

    public function definition()
    {
        return [
            'orthanc_id' =>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'visit_id'=> Visit::factory()->create()->id,
            'user_id'=> User::factory()->create()->id,
            'upload_date'=> Util::now(),
            'acquisition_date'=>$this->faker->date(),
            'acquisition_time'=>$this->faker->time(),
            'anon_from_orthanc_id'=>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'study_uid'=>$this->faker->unique()->word,
            'study_description'=>$this->faker->word,
            'patient_orthanc_id'=>$this->faker->regexify('[A-Za-z0-9]{44}'),
            'patient_name'=>$this->faker->word,
            'patient_id'=>$this->faker->word,
            'number_of_series'=> (1+$this->faker->randomNumber),
            'number_of_instances'=> (1+$this->faker->randomNumber),
            'disk_size'=> (1 + $this->faker->randomNumber),
            'uncompressed_disk_size'=> (1+$this->faker->randomNumber)
        ];
    }

    public function visitId(int $visitId){

        return $this->state(function (array $attributes) use ($visitId) {
            return [
                'visit_id' => $visitId,
            ];
        });
    }

    public function orthancStudy(string $patientOrthancId){

        return $this->state(function (array $attributes) use ($patientOrthancId) {
            return [
                'orthanc_id' => $patientOrthancId,
            ];
        });

    }

    public function uploaderId(int $uploaderId){

        return $this->state(function (array $attributes) use ($uploaderId) {
            return [
                'user_id' => $uploaderId,
            ];
        });
    }

    public function studyUid(string $studyInstanceUID){
        return $this->state(function (array $attributes) use ($studyInstanceUID) {
            return [
                'study_uid' => $studyInstanceUID,
            ];
        });
    }
}
