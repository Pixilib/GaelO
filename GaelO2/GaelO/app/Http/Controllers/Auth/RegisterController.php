<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class RegisterController extends Controller
{

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'username' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    public function register(){
        print(request());
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    protected function create(array $data)
    {
        return User::create([
            'lastname' => $data['lastname'],
            'firstname' => $data['firstname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'last_password_update' => now(),
            'creation_date'=> now(),
            'status' => "Unconfirmed",
            'center_code' => $data['centerCode'],
            'job_name' => $data['role'],
            'administrator' => $data['administrator'],
            'password' => Hash::make($data['password']),
            'api_token' => Str::random(60),
        ]);
        return response()->json(['success' => true]);
    }
}
