<?php

namespace App\GaelO\UseCases\Request;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;

class SendRequest {

    public function __construct(MailServices $mailService)
    {
        $this->mailService = $mailService;
    }

    public function execute(RequestRequest $requestRequest, RequestResponse $requestResponse){

        try{

            $this->checkEmpty($requestRequest->name, 'name');
            $this->checkEmpty($requestRequest->email, 'email');
            $this->checkEmpty($requestRequest->center, 'center');
            $this->checkEmpty($requestRequest->request, 'request');

            $this->mailService->sendRequestMessage( get_object_vars ($requestRequest) );

            $requestResponse->status = 200;
            $requestResponse->statusText = 'OK';

        }catch (GaelOException $e) {
            $requestResponse->body = ['errorMessage' => $e->getMessage()];
            $requestResponse->status = 500;
            $requestResponse->statusText = "Internal Server Error";
        }

        return $requestResponse;

    }

    private function checkEmpty($inputData, string $name){
        if(empty($inputData)){
            throw new GaelOException('Missing'+$name);
        }
    }


}
