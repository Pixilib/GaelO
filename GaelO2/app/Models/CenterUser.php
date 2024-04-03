<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CenterUser extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'center_code' => 'integer'
        ];
    }

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'center_user';
}
