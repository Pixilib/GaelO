<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewStatus extends Model
{
    use HasFactory;

    protected $table = 'reviews_status';

    protected $casts = [
        'target_lesions' => 'array',
    ];

    //Default value because db does not accept default value json
    protected $attributes = [
        'target_lesions' => '{}',
    ];

    protected $guarded = [];

    public function visit(){
        return $this->belongsTo(Visit::class, 'visit_id', 'id');
    }

    public function study(){
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }

}
