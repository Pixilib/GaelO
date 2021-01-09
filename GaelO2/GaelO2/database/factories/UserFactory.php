<?php

namespace Database\Factories;

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
            'username' => $this->faker->unique()->userName,
            'email'=> $this->faker->unique()->safeEmail,
            'password' => $this->faker->password,
            'password_temporary'=> $this->faker->password,
            'password_previous1'=> $this->faker->password,
            'password_previous2'=> $this->faker->password,
            'phone' => $this->faker->phoneNumber,
            'last_password_update' => now(),
            'creation_date' => now(),
            'status' => $this->faker->randomElement(['Unconfirmed', 'Activated', 'Blocked']),
            'administrator' => false,
            'center_code' => 0,
            'job' => $this->faker->randomElement(['CRA', 'Monitor', 'Nuclearist','PI', 'Radiologist', 'Study nurse', 'Supervision' ]),
            'orthanc_address' => $this->faker->domainName,
            'orthanc_login' => $this->faker->userName,
            'orthanc_password' => $this->faker->password,
        ];
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

    public function accountState(string $state){

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

}
