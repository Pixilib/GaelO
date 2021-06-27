<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', 'IndexController@getIndex');
Route::get('study/{any?}', 'IndexController@getIndex')->where(['any' => '.*']);
Route::get('administrator/{any?}', 'IndexController@getIndex')->where(['any' => '.*']);
Route::get('viewer-ohif/viewer/{studyInstanceUID}', 'IndexController@getOhif');

