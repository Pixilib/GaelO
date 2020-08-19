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
Route::get('users/{id?}', 'UserController@getUser');
Route::post('users', 'UserController@createUser');
//SK ICI a generaliser pour l'edition ou on fait un PUT ?
Route::patch('users', 'UserController@changeUserPassword');
Route::delete('users/{id}', 'UserController@deleteUser');

//Login Routes
Route::post('login', 'UserController@loginClean');
Route::get('countries/{code?}', 'CountryController@getCountry');
Route::get('centers/{code?}', 'CenterController@getCenter');
Route::get('mail', 'UserController@testMail');
