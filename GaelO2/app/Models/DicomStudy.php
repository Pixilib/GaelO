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

    protected $casts = [
        'study_uid' => 'string',
        'orthanc_id' => 'string',
        'visit_id' => 'integer',
        'user_id' => 'integer',
        'upload_date' => 'datetime',
        'acquisition_date' => 'date',
        'acquisition_time' => 'datetime',
        'anon_from_orthanc_id' => 'string',
        'study_description' => 'string',
        'patient_orthanc_id' => 'string',
        'patient_name' => 'string',
        'patient_id' => 'string',
        'number_of_series' => 'integer',
        'number_of_instances' => 'integer',
        'disk_size' => 'integer',
        'uncompressed_disk_size' => 'integer'
    ];

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
