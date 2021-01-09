<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;

    public function country(){
        return $this->belongsTo('App\Models\Country', 'country_code', 'code');
    }
}
