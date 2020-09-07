<?php
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

//Users Routes
Route::middleware(['auth:api', 'admin'])->get('users/{id?}', 'UserController@getUser');
Route::middleware(['auth:api', 'admin'])->post('users', 'UserController@createUser');
Route::middleware(['auth:api', 'admin'])->put('users/{id}/password', 'UserController@changeUserPassword');
Route::middleware(['auth:api', 'admin'])->put('users/{id}', 'UserController@modifyUser');
Route::middleware(['auth:api', 'admin'])->delete('users/{id}', 'UserController@deleteUser');

//Centers Routes
Route::middleware(['auth:api', 'admin'])->get('centers/{code?}', 'CenterController@getCenter');
Route::middleware(['auth:api', 'admin'])->post('centers', 'CenterController@createCenter');

//Mail Route
Route::post('request', 'RequestController@sendRequest');

//Login-Logout Routes
Route::post('login', 'AuthController@login');
Route::middleware('auth:api')->delete('login', 'AuthController@logout');

//Miscellaneous Routes
Route::middleware('auth:api')->get('countries/{code?}', 'CountryController@getCountry');

//Tools Routes
Route::post('tools/reset-password', 'ToolsController@resetPassword');
