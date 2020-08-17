<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('users', 'UserController@createUser');
Route::get('users/{id?}', 'UserController@getUser');
Route::patch('users', 'UserController@changeUserPassword');
Route::delete('users/{id}', 'UserController@deleteUser');
Route::post('login', 'UserController@login');
Route::post('register', 'RegisterController@register');
Route::get('testClean', 'UserController@loginClean');
