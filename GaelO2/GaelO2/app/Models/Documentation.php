<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    public function study(){
        return $this->belongsTo('App\Models\Study', 'study_name', 'name');
    }
}
