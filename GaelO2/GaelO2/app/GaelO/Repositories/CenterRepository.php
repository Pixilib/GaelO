<?php

namespace App\GaelO\Repositories;

use App\GaelO\Interfaces\CenterRepositoryInterface;
use App\Models\Center;
use App\GaelO\Util;

class CenterRepository implements CenterRepositoryInterface {

    public function __construct(Center $center){
        $this->center = $center;
    }

    private function create(array $data){
        $center = new Center();
        $model = Util::fillObject($data, $center);
        $model->save();
    }

    private function update($code, array $data) : void{
        $model = $this->center->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id) : array {
        return $this->center->findOrFail($id);
    }

    public function getAll() : array {
        $centers = $this->center->get();
        return empty($centers) ? [] : $centers->toArray();
    }

    public function createCenter(int $code, string $name, string $countryCode) : void {
        $data = [
            'code' => $code,
            'name' => $name,
            'country_code' => $countryCode
        ];

        $this->create($data);
    }

    public function getCenterByName(string $name) : array {
        $center = $this->center->where('name', $name)->get()->first();
        return $center !== null  ? $center->toArray() : [];
    }

    public function getCenterByCode(int $code) : array {
        $center = $this->center->find($code)->toArray();
        return $center;
    }

    public function isKnownCenter(int $code) : bool {
        return empty($this->center->find($code)) ? false : true;
    }

    public function updateCenter(int $code, String $name, String $countryCode) : void {
        $data = [
            'name' => $name,
            'country_code' => $countryCode
        ];
        $this->update($code, $data);

    }

}

?>
