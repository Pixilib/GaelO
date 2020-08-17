<?php

namespace App\GaelO\Interfaces;

interface PersistenceInterface {

   public function update($id, array $data);

   public function create(array $data);

   public function find($id);  
   
   public function delete($id);
}

?>