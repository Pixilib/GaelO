<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Documentation extends Model
{
    use HasFactory;

    public function study(){
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }
}
