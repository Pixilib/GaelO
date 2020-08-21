<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VisitGroup extends Model
{
    public function study(){
        $this->hasOne('App\Study', 'name', 'study_name');
    }

    public function visit_types(){
        $this->hasMany('App\VisitType', 'visit_group_id');
    }
}
