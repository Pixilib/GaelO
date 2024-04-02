<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Center extends Model
{
    use HasFactory;

    protected $primaryKey = 'code';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'code' => 'integer',
            'name' => 'string',
            'country_code' => 'string'
        ];
    }
    
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_code', 'code');
    }

    public function patients()
    {
        return $this->hasMany(Patient::class, 'center_code', 'code');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'center_code', 'code');
    }
}
