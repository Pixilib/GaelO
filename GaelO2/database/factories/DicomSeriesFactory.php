<?php

namespace Database\Factories;

use App\Models\DicomSeries;
use App\Models\DicomStudy;
use Illuminate\Database\Eloquent\Factories\Factory;

class DicomSeriesFactory extends Factory
{

    protected $model = DicomSeries::class;

    public function definition()
    {
        return [
            'orthanc_id' => $this->faker->regexify('[A-Za-z0-9]{44}'),
            'study_instance_uid' => function () {
                return DicomStudy::factory()->create()->study_uid;
            },
            'acquisition_date' => $this->faker->date(),
            'acquisition_time' => $this->faker->time(),
            'modality' => $this->faker->word,
            'series_description' => $this->faker->word,
            'injected_dose' => $this->faker->randomNumber,
            'radiopharmaceutical' => $this->faker->word,
            'half_life' => $this->faker->randomNumber,
            'injected_datetime' => $this->faker->dateTime(),
            'injected_activity' => $this->faker->randomNumber,
            'patient_weight' => $this->faker->randomNumber,
            'number_of_instances' => $this->faker->randomNumber,
            'series_uid' => $this->faker->unique()->word,
            'series_number' => $this->faker->word,
            'disk_size' => $this->faker->randomNumber,
            'uncompressed_disk_size' => $this->faker->randomNumber,
            'manufacturer' => $this->faker->word,
            'model_name' => $this->faker->word,
        ];
    }

    public function studyInstanceUID(string $studyInstanceUID)
    {
        return $this->state(function (array $attributes) use ($studyInstanceUID) {
            return [
                'study_instance_uid' => $studyInstanceUID,
            ];
        });
    }

    public function orthancId(string $orthancId)
    {
        return $this->state(function (array $attributes) use ($orthancId) {
            return [
                'orthanc_id' => $orthancId,
            ];
        });
    }

    public function seriesUid(string $seriesInstanceUID)
    {
        return $this->state(function (array $attributes) use ($seriesInstanceUID) {
            return [
                'series_uid' => $seriesInstanceUID,
            ];
        });
    }
}
