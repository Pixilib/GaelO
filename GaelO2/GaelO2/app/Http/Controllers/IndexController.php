<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class IndexController extends Controller
{
    public function getIndex()
    {
        return File::get(public_path() . '/index.html');
    }

    public function getOhif()
    {
        return File::get(public_path() . '/viewer-ohif/index.html');
    }
}
