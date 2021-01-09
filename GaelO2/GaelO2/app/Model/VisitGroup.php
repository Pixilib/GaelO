<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VisitGroup extends Model
{
    public function study(){
        return $this->belongsTo('App\Model\Study', 'study_name', 'name');
    }

    public function visitTypes(){
        return $this->hasMany('App\Model\VisitType', 'visit_group_id');
    }
}
