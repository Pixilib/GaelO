<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class VisitType extends Model
{
    public function visits(){
        $this->hasMany('App\Visit', 'visit_type_id');
    }

    public function visitGroup(){
        $this->belongsTo('App\VisitGroup', 'visit_group_id' , 'id');
    }
}
