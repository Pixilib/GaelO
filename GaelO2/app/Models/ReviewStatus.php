<?php

namespace App\Models;

use App\GaelO\Constants\Enums\ReviewStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewStatus extends Model
{
    use HasFactory;

    protected $table = 'reviews_status';
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'visit_id' => 'integer',
            'study_name' => 'string',
            'review_available' => 'boolean',
            'target_lesions' => 'array',
            'review_status' => ReviewStatusEnum::class,
            'review_conclusion_value' => 'string',
            'review_conclusion_date' => 'datetime'
        ];
    }

    //Default value because db does not accept default value json
    protected $attributes = [
        'target_lesions' => '{}',
    ];


    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id', 'id');
    }

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }
}
