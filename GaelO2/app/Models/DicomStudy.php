<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DicomStudy extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'dicom_studies';
    protected $primaryKey = 'study_uid';
    protected $keyType = 'string';
    public $incrementing = false;

    public function dicomSeries()
    {
        return $this->hasMany(DicomSeries::class, 'study_instance_uid', 'study_uid');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id', 'id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }
}
