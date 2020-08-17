<?php

namespace App\GaelO\Adapters;

use Illuminate\Support;

class CollectionAdapter extends Collection {

    public function __construct(mixed $items = []){
        parent::__construct($items);
    }

}