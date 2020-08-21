<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    public function country(){
        return $this->hasOne('App\Country', 'code', 'country_code');
    }
}
