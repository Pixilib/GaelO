<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ReviewStatus extends Model
{
    protected $table = 'reviews_status';
    protected $primaryKey = ['visit_id', 'study_name'];
    public $incrementing = false;
}
