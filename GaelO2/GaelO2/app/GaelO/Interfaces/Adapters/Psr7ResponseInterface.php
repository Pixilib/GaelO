<?php

namespace App\GaelO\Interfaces\Adapters;

Interface Psr7ResponseInterface {
    public function getStatusCode() : int ;
    public function getBody() : string ;
    public function getReasonPhrase() :string ;
    public function getHeaders()  : array ;
    public function getJsonBody() : array ;
}
