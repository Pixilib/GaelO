<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Documentation extends Model
{
    use SoftDeletes, HasFactory;

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'document_date' => 'datetime',
        'study_name' => 'string',
        'version' => 'string',
        'investigator' => 'boolean',
        'controller' => 'boolean',
        'monitor' => 'boolean',
        'reviewer' => 'boolean',
        'path' => 'string'
    ];

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }
}
