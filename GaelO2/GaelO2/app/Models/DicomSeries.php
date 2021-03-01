<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DicomSeries extends Model
{
    use SoftDeletes, HasFactory;

    protected $primaryKey = 'series_uid';
    protected $keyType = 'string';
    public $incrementing = false;

    public function dicomStudy(){
        return $this->belongsTo('App\Models\DicomStudy', 'study_uid', 'study_uid');
    }
}
