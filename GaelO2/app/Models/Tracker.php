<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tracker extends Model
{
    use HasFactory;

    protected $casts = [
        'id' => 'integer',
        'study_name' => 'string',
        'user_id' => 'integer',
        //Create issues in test because of sub milisec difference not seen
        //'date' => 'datetime',
        'role' => 'string',
        'visit_id' => 'integer',
        'action_type' =>  'string',
        'action_details' => 'array'
    ];

    protected $attributes = [
        'action_details' => '{}',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->withTrashed();
    }

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class, 'visit_id', 'id')->withTrashed();
    }
}
