<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class VisitType extends Model
{
    public function visits(){
        return $this->hasMany('App\Model\Visit', 'visit_type_id');
    }

    public function visitGroup(){
        return $this->belongsTo('App\Model\VisitGroup', 'visit_group_id' , 'id');
    }
}
