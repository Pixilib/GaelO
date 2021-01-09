<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Passport\HasApiTokens;
class User extends Authenticatable
{
    use Notifiable, SoftDeletes, HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'username', 'email', 'password', 'phone', 'administrator', 'center_code', 'job', 'orthanc_address', 'orthanc_login', 'orthanc_password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token', //'password', 'password_previous1', 'password_previous2', 'password_temporary'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'administrator' => 'boolean'
    ];

    public function roles() {
        return $this-> hasMany('App\Models\Role', 'user_id');
    }

    public function affiliatedCenters(){
        return $this->hasManyThrough('App\Models\Center', 'App\Models\CenterUser', 'user_id', 'code', 'id', 'center_code');
    }

    public function mainCenter(){
        return $this->belongsTo('App\Models\Center', 'code','center_code');
    }

}
