<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;

    public function country(){
        return $this->belongsTo('App\Country', 'country_code', 'code');
    }
}
