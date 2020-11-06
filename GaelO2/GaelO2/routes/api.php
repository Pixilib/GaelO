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

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
*/

//User related Routes
Route::middleware(['auth:api', 'refresh_token'])->post('users', 'UserController@createUser');
Route::middleware(['auth:api', 'refresh_token'])->put('users/self/{id}', 'UserController@modifyUserIdentification');
Route::middleware(['auth:api', 'refresh_token'])->delete('users/{id}', 'UserController@deleteUser');

Route::middleware(['auth:api', 'refresh_token'])->post('users/{id}/affiliated-centers', 'UserController@addAffiliatedCenter');

//Export DB Route (download binary doesn't support refresh token)
Route::middleware('auth:api')->post('export-db', 'ExportDBController@exportDB');

//Study Routes
Route::middleware('auth:api')->post('studies', 'StudyController@createStudy');

//Centers Routes
Route::middleware('auth:api')->post('centers', 'CenterController@createCenter');


//Routes that need authentication and to be admin
Route::middleware(['auth:api', 'admin', 'refresh_token'])->group(function () {

    //Users Routes
    Route::put('users/{id}', 'UserController@modifyUser');
    Route::patch('users/{id}/reactivate', 'UserController@reactivateUser');
    Route::get('users/{id}/roles/{study?}', 'UserController@getRoles');
    Route::post('users/{id}/roles/{study}', 'UserController@createRole');
    Route::delete('users/{id}/roles/{study}/{roleName}', 'UserController@deleteRole');

    Route::get('users/{id}/affiliated-centers', 'UserController@getAffiliatedCenter');
    Route::delete('users/{id}/affiliated-centers/{centerCode}', 'UserController@deleteAffiliatedCenter');
    Route::get('studies/{studyName}/users', 'UserController@getUserFromStudy');

    //Study Routes

    Route::get('studies', 'StudyController@getStudy');
    Route::delete('studies/{studyName}', 'StudyController@deleteStudy');
    Route::patch('studies/{studyName}/reactivate', 'StudyController@reactivateStudy');

    //Preferences Routes
    Route::get('preferences', 'PreferenceController@getPreference');
    Route::put('preferences', 'PreferenceController@modifyPreference');

    //Centers Routes
    Route::get('centers/{code?}', 'CenterController@getCenter');
    Route::put('centers/{code}', 'CenterController@modifyCenter');

    //VisitGroup Routes
    Route::post('studies/{studyName}/visit-groups', 'VisitGroupController@createVisitGroup');
    Route::get('visit-groups/{visitGroupId}', 'VisitGroupController@getVisitGroup');
    Route::delete('visit-groups/{visitGroupId}', 'VisitGroupController@deleteVisitGroup');

    //VisitType Routes
    Route::post('visit-groups/{visitGroupId}/visit-types', 'VisitTypeController@createVisitType');
    Route::get('visit-types/{visitTypeId}', 'VisitTypeController@getVisitType');
    Route::delete('visit-types/{visitTypeId}', 'VisitTypeController@deleteVisitType');

    //Tracker Routes
    Route::get('tracker', 'TrackerController@getTracker');



});


/*
|--------------------------------------------------------------------------
| Users Routes
|--------------------------------------------------------------------------
|
*/

//Routes that need authentication
Route::middleware(['auth:api', 'refresh_token'])->group(function () {
    Route::get('users/{id?}', 'UserController@getUser');
    //Logout Route
    Route::delete('login', 'AuthController@logout');
    //Miscellaneous Routes
    Route::get('countries/{code?}', 'CountryController@getCountry');

    //Patient Routes
    Route::get('patients/{code?}', 'PatientController@getPatient');
    Route::get('studies/{studyName}/patients', 'PatientController@getPatientFromStudy');
    Route::post('studies/{studyName}/import-patients', 'StudyController@importPatients');

    //Visit Routes
    Route::post('studies/{studyName}/visit-groups/{visitGroupId}/visit-types/{visitTypeId}/visits', 'VisitController@createVisit');
    Route::get('visits/{id}', 'VisitController@getVisit');
    Route::get('visits/{id}/patients/{patientCode}', 'VisitController@getPatientVisit');
});


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
*/

//Request Route
Route::post('request', 'RequestController@sendRequest');

//Login Route
Route::post('login', 'AuthController@login');
Route::put('users/{id}/password', 'UserController@changeUserPassword');

//Tools Routes
Route::post('tools/reset-password', 'ToolsController@resetPassword');
