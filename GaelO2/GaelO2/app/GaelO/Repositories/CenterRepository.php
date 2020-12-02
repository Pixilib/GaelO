<?php

namespace App\GaelO\Repositories;

use App\Center;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;

class CenterRepository implements PersistenceInterface {

    public function __construct(Center $center){
        $this->center = $center;
    }

    public function create(array $data){
        $center = new Center();
        $model = Util::fillObject($data, $center);
        $model->save();
    }

    public function createCenter(int $code, string $name, string $countryCode) : void {
        $data = [
            'code' => $code,
            'name' => $name,
            'country_code' => $countryCode
        ];

        $this->create($data);
    }

    public function update($code, array $data) : void{
        $model = $this->center->find($code);
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function find($id){
        return $this->center->find($id);
    }

    public function delete($id) : void{
        $this->center->find($id)->delete();
    }

    public function getAll() : array {
        $centers = $this->center->get();
        return empty($centers) ? [] : $centers->toArray();
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
        return empty($this->find($code)) ? false : true;
    }

    public function updateCenter(String $name, int $code, String $countryCode) : void {
        $data = [
            'code' => $code,
            'name' => $name,
            'country_code' => $countryCode
        ];
        $this->update($code, $data);

    }

    public function getExistingCenter() : array {
        $centers = $this->center->get()->pluck('code');
        return empty($centers) ? [] : $centers->toArray();
    }

}

?>
