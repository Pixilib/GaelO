<?php

namespace App\GaelO\Interfaces;

interface PersistenceInterface {

   public function updateUser($id, array $data);

   public function createUser(array $data);

   public function retrieveData($id);    
}

?>