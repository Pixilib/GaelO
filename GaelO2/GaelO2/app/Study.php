<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Study extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'name';

    public function patients(){
        $this->hasMany('App\Patient', 'study_name');
    }
}
