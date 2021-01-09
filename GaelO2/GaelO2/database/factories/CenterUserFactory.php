<?php

namespace Database\Factories;

use App\Models\CenterUser;
use Illuminate\Database\Eloquent\Factories\Factory;

class CenterUserFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = CenterUser::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'=>$this->faker->randomNumber,
            'center_code'=>$this->faker->randomNumber
        ];
    }

    public function centerCode(int $centerCode){

        return $this->state(function (array $attributes) use ($centerCode) {
            return [
                'center_code' => $centerCode,
            ];
        });

    }

    public function userId(int $userId){

        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
            ];
        });

    }
}
