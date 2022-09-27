<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DicomSeries extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'dicom_series';
    protected $primaryKey = 'series_uid';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'series_uid' => 'string',
        'study_instance_uid' => 'string',
        'orthanc_id' => 'string',
        'acquisition_date' => 'date:Y-m-d',
        'acquisition_time' => 'datetime:H:i:s',
        'modality' => 'string',
        'series_description' => 'string',
        'injected_dose' => 'integer',
        'radiopharmaceutical' => 'string',
        'half_life' => 'integer',
        'injected_time' => 'datetime:H:i:s',
        'injected_datetime' => 'datetime',
        'injected_activity' => 'integer',
        'patient_weight' => 'integer',
        'number_of_instances' => 'integer',
        'series_number' => 'string',
        'disk_size' => 'integer',
        'uncompressed_disk_size' => 'integer',
        'manufacturer' => 'string',
        'model_name' => 'string'
    ];

    public function dicomStudy()
    {
        return $this->belongsTo(DicomStudy::class, 'study_instance_uid', 'study_uid');
    }
}
