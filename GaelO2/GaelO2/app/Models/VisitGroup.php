<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VisitGroup extends Model
{
    public function study(){
        return $this->belongsTo('App\Models\Study', 'study_name', 'name');
    }

    public function visitTypes(){
        return $this->hasMany('App\Models\VisitType', 'visit_group_id');
    }
}
