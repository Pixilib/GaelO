<?php

namespace App\GaelO\Interfaces\Repositories;

interface CenterRepositoryInterface {

    public function getAll() : array ;

    public function createCenter(int $code, string $name, string $countryCode) : void ;

    public function isExistingCenterName(string $name) : bool ;

    public function getCenterByCode(int $code) : array ;

    public function getCentersFromCodeArray(array $centerCodes) : array ;

    public function isKnownCenter(int $code) : bool ;

    public function updateCenter(int $code, String $name, String $countryCode) : void ;

    public function getUsersOfCenter(int $code);

    public function getPatientsOfCenter(int $code);

    public function deleteCenter(int $code);
}
