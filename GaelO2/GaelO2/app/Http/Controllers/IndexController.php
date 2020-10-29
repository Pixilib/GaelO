<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class IndexController extends Controller
{
    public function getIndex(){
        return File::get(public_path() . '/index.html');
    }
}
