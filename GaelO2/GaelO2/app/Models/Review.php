<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use SoftDeletes, HasFactory;

    protected $casts = [
        'sent_files' => 'array',
        'review_data' => 'array',
    ];

    //Default value because db does not accept default value json
    protected $attributes = [
        'review_data' => '{}',
        'sent_files' => '{}'
    ];

    public function user(){
        return $this->belongsTo('App\Models\User', 'user_id', 'id');
    }

    public function visit(){
        return $this->belongsTo('App\Models\Visit', 'visit_id', 'id');
    }

}
