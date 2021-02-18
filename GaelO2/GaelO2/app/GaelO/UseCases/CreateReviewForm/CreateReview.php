<?php

namespace App\GaelO\UseCases\CreateReviewForm;

class CreateReview {

    public function __construct(){

    }

    public function execute(CreateReviewFormRequest $createReviewFromRequest, CreateReviewFormResponse $createReviewFormResponse){

    }

    private function checkAuthorization(){
        //Visit autorisé pour le role demandé
        // + Review available
    }

}
