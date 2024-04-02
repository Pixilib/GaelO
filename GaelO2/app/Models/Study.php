<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Study extends Model
{
    use SoftDeletes, HasFactory, HasRelationships;

    protected $primaryKey = 'name';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'name' => 'string',
            'code' => 'string',
            'patient_code_length' => 'integer',
            'contact_email' => 'string',
            'controller_show_all' => 'boolean',
            'monitor_show_all' => 'boolean',
            'ancillary_of' =>  'string',
            'documentation_mandatory' => 'boolean'
        ];
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'study_name');
    }

    public function visitGroups()
    {
        return $this->hasMany(VisitGroup::class, 'study_name');
    }

    public function visits()
    {
        return $this->hasManyDeep(Visit::class, [Patient::class]);
    }


    public function dicomStudies()
    {
        return $this->hasManyDeep(DicomStudy::class, [Patient::class, Visit::class]);
    }


    public function dicomSeries()
    {
        return $this->hasManyDeep(DicomSeries::class, [Patient::class, Visit::class, DicomStudy::class], [null, null, null, 'study_instance_uid'], [null, null, null, null]);
    }


    public function documentations()
    {
        return $this->hasMany(Documentation::class, 'study_name');
    }

    public function roles()
    {
        return $this->hasMany(Role::class, 'study_name');
    }
}
