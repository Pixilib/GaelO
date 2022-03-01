<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class StudyFactory extends Factory
{

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->regexify('[A-Z0-9]{20}'),
            'code' => $this->faker->unique()->randomNumber(5),
            'patient_code_length' => $this->faker->randomNumber(5),
            'contact_email' => $this->faker->email(),
            'controller_show_all'=> false,
            'monitor_show_all' => false,
            'ancillary_of' => null
        ];
    }

    public function name(String $name){

        return $this->state(function (array $attributes) use($name) {
            return [
                'name' => $name
            ];
        });
    }

    public function patientCodeLength(int $length){

        return $this->state(function (array $attributes) use($length) {
            return [
                'patient_code_length' => $length
            ];
        });
    }

    public function code(string $code){

        return $this->state(function (array $attributes) use($code) {
            return [
                'code' => $code,
            ];
        });

    }

    public function controllerShowAll(){
        return $this->state(function (array $attributes) {
            return [
                'controller_show_all' => true,
            ];
        });
    }

    public function monitorShowAll(){
        return $this->state(function (array $attributes) {
            return [
                'monitor_show_all' => true,
            ];
        });
    }

    public function ancillaryOf(string $studyName){

        return $this->state(function (array $attributes) use($studyName) {
            return [
                'ancillary_of' => $studyName,
            ];
        });

    }


}
