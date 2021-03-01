<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DicomStudy extends Model
{
    use SoftDeletes, HasFactory;

    protected $primaryKey = 'study_uid';
    protected $keyType = 'string';
    public $incrementing = false;

    public function dicomSeries(){
        return $this->hasMany('App\Models\DicomSeries', 'study_uid', 'study_uid');
    }

    public function visit(){
        return $this->belongsTo('App\Models\Visit', 'visit_id', 'id');
    }


    public function uploader(){
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }
}
