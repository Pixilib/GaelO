<?php

namespace Database\Factories;

use App\Models\Center;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterFactory extends Factory
{

    protected $model = Center::class;

    public function definition()
    {
        return [
            'code' => ($this->faker->unique()->randomNumber() + 10),
            'name' => $this->faker->unique()->word,
            'country_code' => 'FR'
        ];
    }

    public function code(int $centerCode)
    {
        return $this->state(function (array $attributes) use ($centerCode) {
            return [
                'code' => $centerCode,
            ];
        });
    }

    public function name(string $centerName)
    {
        return $this->state(function (array $attributes) use ($centerName) {
            return [
                'name' => $centerName,
            ];
        });
    }
}
