<?php

namespace Database\Factories;

use App\Models\Documentation;
use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentationFactory extends Factory
{

    protected $model = Documentation::class;

    public function definition()
    {
        return [
            'id'=>$this->faker->unique()->randomNumber,
            'name'=>$this->faker->unique()->word,
            'document_date'=>now(),
            'study_name'=>$this->faker->word,
            'version'=>$this->faker->word,
            'investigator'=> false,
            'controller'=> false,
            'monitor'=> false,
            'reviewer'=> false,
            'path'=> $this->faker->word
        ];
    }

    public function studyName($studyName){

        return $this->state(function (array $attributes) use ($studyName) {
            return [
                'study_name' => $studyName,
            ];
        });
    }

    public function investigator(){

        return $this->state(function (array $attributes) {
            return [
                'investigator' => true,
            ];
        });
    }

    public function controller(){

        return $this->state(function (array $attributes) {
            return [
                'controller' => true,
            ];
        });
    }

    public function monitor(){

        return $this->state(function (array $attributes) {
            return [
                'monitor' => true,
            ];
        });
    }

    public function reviewer(){

        return $this->state(function (array $attributes) {
            return [
                'reviewer' => true,
            ];
        });
    }

    public function path($path){

        return $this->state(function (array $attributes) use($path) {
            return [
                'path' => $path,
            ];
        });
    }

}
