<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documentation extends Model
{
    use SoftDeletes, HasFactory;

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }
}
