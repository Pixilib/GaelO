<?php

namespace App\Models;

use App\GaelO\Constants\Enums\GenderEnum;
use App\GaelO\Constants\Enums\InclusionStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'id' => 'string',
            'code' => 'string',
            'firstname' => 'string',
            'lastname' => 'string',
            'gender' => GenderEnum::class,
            'birth_day' => 'integer',
            'birth_month' => 'integer',
            'birth_year' => 'integer',
            'registration_date' => 'date',
            'investigator_name' => 'string',
            'center_code' => 'integer',
            'study_name' => 'string',
            'inclusion_status' => InclusionStatusEnum::class,
            'withdraw_reason' => 'string',
            'withdraw_date' => 'date',
            'metadata' => 'array',
        ];
    }

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
