<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes, HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'study_name' => 'string',
            'visit_id' => 'integer',
            'user_id' => 'integer',
            'review_date' => 'datetime',
            'validated' => 'boolean',
            'local' => 'boolean',
            'adjudication' => 'boolean',
            'sent_files' => 'array',
            'review_data' => 'array',
        ];
    }

    //Default value because db does not accept default value json
    protected $attributes = [
        'review_data' => '{}',
        'sent_files' => '{}'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id', 'id');
    }
}
