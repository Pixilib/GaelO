<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrthancSeries extends Model
{
    use SoftDeletes, HasFactory;

    protected $primaryKey = 'orthanc_id';
    protected $keyType = 'string';
    public $incrementing = false;

    public function orthancStudy(){
        return $this->belongsTo('App\Models\OrthancStudy', 'orthanc_study_id', 'orthanc_id');
    }
}
