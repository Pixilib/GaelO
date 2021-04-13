<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Center extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;

    public function country(){
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }
}
