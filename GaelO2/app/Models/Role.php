<?php

namespace App\Models;

use App\GaelO\Constants\Enums\RoleEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'name' => RoleEnum::class,
            'user_id' => 'integer',
            'study_name' => 'string',
            'validated_documentation_version' => 'string'
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function study()
    {
        return $this->belongsTo(Study::class, 'study_name', 'name');
    }
}
