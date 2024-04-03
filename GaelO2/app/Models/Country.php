<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $primaryKey = 'code';
    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'code' => 'string',
            'country_us' => 'string',
            'country_fr' => 'string'
        ];
    }
}
