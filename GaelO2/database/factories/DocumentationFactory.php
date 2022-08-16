<?php

namespace Database\Factories;

use App\GaelO\Util;
use App\Models\Documentation;
use App\Models\Study;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentationFactory extends Factory
{

    protected $model = Documentation::class;

    public function definition()
    {
        return [
            'id' => $this->faker->unique()->randomNumber,
            'name' => $this->faker->unique()->word,
            'document_date' => Util::now(),
            'study_name' => function () {
                return Study::factory()->create()->name;
            },
            'version' => $this->faker->randomDigit().'.'.$this->faker->randomDigit().'.'.$this->faker->randomDigit(),
            'investigator' => false,
            'controller' => false,
            'monitor' => false,
            'reviewer' => false,
            'path' => $this->faker->word
        ];
    }

    public function name($name)
    {
        return $this->state(function (array $attributes) use ($name) {
            return [
                'name' => $name,
            ];
        });
    }

    public function studyName($studyName)
    {
        return $this->state(function (array $attributes) use ($studyName) {
            return [
                'study_name' => $studyName,
            ];
        });
    }

    public function version($version)
    {
        return $this->state(function (array $attributes) use ($version) {
            return [
                'version' => $version,
            ];
        });
    }

    public function investigator()
    {
        return $this->state(function (array $attributes) {
            return [
                'investigator' => true,
            ];
        });
    }

    public function controller()
    {
        return $this->state(function (array $attributes) {
            return [
                'controller' => true,
            ];
        });
    }

    public function monitor()
    {
        return $this->state(function (array $attributes) {
            return [
                'monitor' => true,
            ];
        });
    }

    public function reviewer()
    {
        return $this->state(function (array $attributes) {
            return [
                'reviewer' => true,
            ];
        });
    }

    public function path($path)
    {
        return $this->state(function (array $attributes) use ($path) {
            return [
                'path' => $path,
            ];
        });
    }
}
