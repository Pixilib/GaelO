<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class Register extends Controller
{
    //
    public function register(Request $request)
    {
        //error_log(print_r($request, true));
        return response()->json([
            'name' => 'Abigail',
            'state' => 'CA',
        ]);
        //
    }

}
