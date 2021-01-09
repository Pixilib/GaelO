<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;

    public function country(){
        return $this->belongsTo('App\Model\Country', 'country_code', 'code');
    }
}
