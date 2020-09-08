<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Study extends Model
{
    use SoftDeletes;

    protected $primaryKey = 'name';
    protected $keyType = 'string';
    public $incrementing = false;

    public function patients(){
        $this->hasMany('App\Patient', 'study_name');
    }
}
