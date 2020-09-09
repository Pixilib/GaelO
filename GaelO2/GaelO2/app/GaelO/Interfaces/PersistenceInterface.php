<?php

namespace App\GaelO\Interfaces;

interface PersistenceInterface {

   public function update($id, array $data) : void;

   public function create(array $data);

   public function find(int $id);

   public function getAll() : array;

   public function delete($id) : void;
}

?>
