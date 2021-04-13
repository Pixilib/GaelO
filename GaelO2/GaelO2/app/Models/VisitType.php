<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitType extends Model
{
    use HasFactory;

    public function visits(){
        return $this->hasMany(Visit::class, 'visit_type_id');
    }

    public function visitGroup() {
        return $this->belongsTo(VisitGroup::class, 'visit_group_id' , 'id');
    }
}
