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
            'status' => 'Activated',
            'administrator' => false,
            'center_code' => 0,
            'attempts' =>0,
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


    public function password(string $password){

        return $this->state(function (array $attributes) use ($password) {
            return [
                'password' => $password,
            ];
        });

    }

    public function passwordTemporary(string $password){

        return $this->state(function (array $attributes) use ($password) {
            return [
                'password_temporary' => $password,
            ];
        });

    }

    public function passwordPrevious1(string $password){

        return $this->state(function (array $attributes) use ($password) {
            return [
                'password_previous1' => $password,
            ];
        });

    }

    public function passwordPrevious2(string $password){

        return $this->state(function (array $attributes) use ($password) {
            return [
                'password_previous2' => $password,
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
