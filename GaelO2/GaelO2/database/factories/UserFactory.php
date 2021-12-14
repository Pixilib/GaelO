<?php

namespace Database\Factories;

use App\GaelO\Util;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{

    protected $model = User::class;

    public function definition()
    {
        return [
            'lastname' => $this->faker->lastname,
            'firstname' => $this->faker->firstname,
            'email'=> $this->faker->unique()->safeEmail,
            'password' => $this->faker->password,
            'phone' => $this->faker->phoneNumber,
            'creation_date' => Util::now(),
            'status' => 'Activated',
            'administrator' => false,
            'center_code' => 0,
            'attempts' =>0,
            'job' => $this->faker->randomElement(['CRA', 'Monitor', 'Nuclearist','PI', 'Radiologist', 'Study nurse', 'Supervision' ]),
            'orthanc_address' => $this->faker->domainName,
            'orthanc_login' => $this->faker->userName,
            'orthanc_password' => $this->faker->password,
            'email_verified_at' => now()
        ];
    }

    public function email(string $email){

        return $this->state(function (array $attributes) use ($email) {
            return [
                'email' => $email
            ];
        });

    }

    public function administrator(){

        return $this->state(function (array $attributes) {
            return [
                'administrator' => true
            ];
        });

    }

    public function job(string $job){

        return $this->state(function (array $attributes) use ($job) {
            return [
                'job' => $job
            ];
        });

    }

    public function passwordExpired(){

        return $this->state(function (array $attributes) {
            return [
                'password' => now()->subDays(100),
            ];
        });

    }

    public function status(string $state){

        return $this->state(function (array $attributes) use ($state) {
            return [
                'status' => $state,
            ];
        });

    }

    public function centerCode(int $centerCode){

        return $this->state(function (array $attributes) use ($centerCode) {
            return [
                'center_code' => $centerCode,
            ];
        });

    }

    public function attempts(int $attempts){

        return $this->state(function (array $attributes) use ($attempts) {
            return [
                'attempts' => $attempts,
            ];
        });

    }

}
