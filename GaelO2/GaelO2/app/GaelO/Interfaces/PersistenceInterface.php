<?php

namespace App\GaelO\Interfaces;

interface PersistenceInterface {

   public function saveData($id, array $data);

   public function retrieveData($id);    
}

?>