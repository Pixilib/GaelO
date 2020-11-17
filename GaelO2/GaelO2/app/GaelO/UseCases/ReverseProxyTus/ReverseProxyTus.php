<?php

namespace App\GaelO\UseCases\ReverseProxyTus;

use App\GaelO\Services\AuthorizationService;

class ReverseProxyTus{

    public function __construct(AuthorizationService $authorizationService )
    {
        $this->authorizationService = $authorizationService;
    }

    public function execute(ReverseProxyTusRequest $reverseProxyTusRequest, ReverseProxyTusResponse $reverseProxyTusResponse){
        //Authorization check que Investigateur dans la study ?
    }

}
