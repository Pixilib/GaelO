<?php

namespace App\GaelO\UseCases\CreateReview;

class CreateReview {

    public function __construct(){

    }

    public function execute(CreateReviewRequest $createReviewRequest, CreateReviewResponse $createReviewResponse){

    }

    private function checkAuthorization(){
        //Visit autorisé pour le role demandé
        // + Review available
    }

}
