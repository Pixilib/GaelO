<?php

namespace App\GaelO\UseCases\CreateReviewForm;

class CreateReview {

    public function __construct(){

    }

    public function execute(CreateReviewFormRequest $createReviewFormRequest, CreateReviewFormResponse $createReviewFormResponse){

    }

    private function checkAuthorization(){
        //Visit autorisé pour le role demandé
        // + Review available
    }

}
