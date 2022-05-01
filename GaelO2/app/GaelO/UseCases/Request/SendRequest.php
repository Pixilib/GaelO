<?php

namespace App\GaelO\UseCases\Request;

use App\GaelO\Exceptions\GaelOBadRequestException;
use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Services\MailServices;

class SendRequest {

    private MailServices $mailService;

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

            $this->mailService->sendRequestMessage($requestRequest->name, $requestRequest->email, $requestRequest->center, $requestRequest->request);

            $requestResponse->status = 200;
            $requestResponse->statusText = 'OK';

        }catch (GaelOException $e) {
            $requestResponse->body = $e->getErrorBody();
            $requestResponse->status = $e->statusCode;
            $requestResponse->statusText = $e->statusText;
        }

        return $requestResponse;

    }

    private function checkEmpty($inputData, string $name){
        if(empty($inputData)){
            throw new GaelOBadRequestException('Request Missing '.$name);
        }
    }


}
