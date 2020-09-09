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
Route::middleware(['auth:api', 'admin'])->get('users/{id}/roles/{study?}', 'UserController@getRoles');
Route::middleware(['auth:api', 'admin'])->post('users/{id}/roles/{study}', 'UserController@createRole');
Route::middleware(['auth:api', 'admin'])->delete('users/{id}/roles/{study}/{roleName}', 'UserController@deleteRole');

//Study Routes
Route::middleware(['auth:api', 'admin'])->post('studies', 'StudyController@createStudy');
Route::middleware(['auth:api', 'admin'])->get('studies', 'StudyController@getStudy');
Route::middleware(['auth:api', 'admin'])->delete('studies/{studyName}', 'StudyController@deleteStudy');

//Preferences Routes
Route::middleware(['auth:api', 'admin'])->get('preferences', 'PreferenceController@getPreference');
Route::middleware(['auth:api', 'admin'])->put('preferences', 'PreferenceController@modifyPreference');

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
