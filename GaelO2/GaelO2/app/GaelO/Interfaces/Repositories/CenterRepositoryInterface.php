<?php

namespace App\GaelO\Interfaces\Repositories;

interface CenterRepositoryInterface {

    public function find($id) : array ;

    public function getAll() : array ;

    public function createCenter(int $code, string $name, string $countryCode) : void ;

    public function isExistingCenterName(string $name) : bool ;

    public function getCenterByCode(int $code) : array ;

    public function getCentersFromCodeArray(array $centerCodes) : array ;

    public function isKnownCenter(int $code) : bool ;

    public function updateCenter(int $code, String $name, String $countryCode) : void ;

}
