<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function register(Request $request){
        $this->create($request->all());
        return response()->json("cii");
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     */
    private function create(array $data)
    {
        return User::create([
            'lastname' => isset($data['lastname']) ? $data['lastname'] : null,
            'firstname' => isset($data['firstname']) ? $data['firstname'] : null,
            'username' => $data['username'],
            'email' => $data['email'],
            'last_password_update' => now(),
            'creation_date'=> now(),
            'status' => "Unconfirmed",
            'center_code' => $data['centerCode'],
            'job_name' => isset($data['job']) ? $data['job'] : null,
            'administrator' => isset($data['administrator']) ? $data['administrator'] : false,
            'password' => Hash::make($data['password']),
            'api_token' => Str::random(60),
        ]);
        
    }
}
