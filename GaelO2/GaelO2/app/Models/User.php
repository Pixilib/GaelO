<?php

namespace App\Models;

use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable, SoftDeletes, HasApiTokens, HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'email', 'password', 'phone', 'administrator', 'center_code', 'job', 'orthanc_address', 'orthanc_login', 'orthanc_password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'remember_token', //'password', 'password_previous1', 'password_previous2'
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
        return $this-> hasMany(Role::class, 'user_id');
    }

    public function affiliatedCenters(){
        return $this->hasManyThrough(Center::class, CenterUser::class, 'user_id', 'code', 'id', 'center_code');
    }

    public function mainCenter(){
        return $this->belongsTo(Center::class, 'code','center_code');
    }

    //Override by custom notification
    public function sendEmailVerificationNotification() {
        $this->notify(new VerifyEmailNotification());
    }

}
