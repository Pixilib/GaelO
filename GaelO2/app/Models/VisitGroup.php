<?php

namespace App\Models;

use App\GaelO\Constants\Enums\ModalityEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VisitGroup extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'study_name' => 'string',
            'name' => 'string',
            'modality' => ModalityEnum::class
        ];
    }

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }

    public function visitTypes()
    {
        return $this->hasMany(VisitType::class, 'visit_group_id');
    }
}
