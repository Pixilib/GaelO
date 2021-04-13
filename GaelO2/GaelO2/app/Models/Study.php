<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Study extends Model
{
    use SoftDeletes, HasFactory;

    protected $primaryKey = 'name';
    protected $keyType = 'string';
    public $incrementing = false;

    public function patients(){
        return $this->hasMany(Patient::class, 'study_name');
    }

    public function visitGroups(){
        return $this->hasMany(VisitGroup::class, 'study_name');
    }

    public function visitGroupDetails(){
    return $this->hasMany(VisitGroup::class, 'study_name')->with('visitTypes');
    }

}
