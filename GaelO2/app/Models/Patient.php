<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'id' => 'string',
        'code' => 'string',
        'firstname' => 'string',
        'lastname' => 'string',
        'gender' => 'string',
        'birth_day' => 'integer',
        'birth_month' => 'integer',
        'birth_year' => 'integer',
        'registration_date' => 'date:Y-m-d',
        'investigator_name' => 'string',
        'center_code' => 'integer',
        'study_name' => 'string',
        'inclusion_status' => 'string',
        'withdraw_reason' => 'string',
        'withdraw_date' => 'date:Y-m-d'
    ];


    public function center()
    {
        return $this->belongsTo(Center::class, 'center_code', 'code');
    }

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }

    public function visits()
    {
        return $this->hasMany(Visit::class, 'patient_id', 'id');
    }
}
