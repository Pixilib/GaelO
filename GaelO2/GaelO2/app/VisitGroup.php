<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VisitGroup extends Model
{
    public function study(){
        $this->belongsTo('App\Study', 'study_name', 'name');
    }

    public function visit_types(){
        $this->hasMany('App\VisitType', 'visit_group_id');
    }
}
