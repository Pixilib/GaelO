<?php

namespace App\GaelO\Interfaces;

interface PersistenceInterface {

   public function update($id, array $data);

   public function create(array $data);

   public function find(int $id);

   public function getAll();
   
   public function delete($id);
}

?>