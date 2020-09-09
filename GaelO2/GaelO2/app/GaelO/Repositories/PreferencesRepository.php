<?php


namespace App\GaelO\Repositories;

use App\GaelO\Exceptions\GaelOException;
use App\GaelO\Interfaces\PersistenceInterface;
use App\GaelO\Util;
use App\Preference;

class PreferencesRepository implements PersistenceInterface {

    public function __construct(Preference $preferences) {
        $this->preferences = $preferences;
    }

    public function update($id, array $data){
        $model = $this->preferences->get()->first();
        $model = Util::fillObject($data, $model);
        $model->save();
    }

    public function create(array $data){
        throw new GaelOException('Only One Record in Preferences');
    }

    public function find(int $id){
        throw new GaelOException('Not Searchable');
    }

    public function getAll(){
        return $this->preferences->get()->first()->toArray();
    }

    public function delete($id){
        throw new GaelOException('Not Deletable');
    }

    public function updatePreferences(int $patientCodeLength, String $parseDateImport, String $parseCountryName){
        $data = [
        'patient_code_length'=>$patientCodeLength,
        'parse_date_import'=>$parseDateImport,
        'parse_country_name'=>$parseCountryName
        ];

        $this->update(1, $data);
    }
}
