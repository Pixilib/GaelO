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


//Export DB Route (download binary doesn't support refresh token)
Route::middleware('auth:api')->post('export-db', 'ExportDBController@exportDB');
Route::middleware('auth:api')->get('documentations/{id}/file', 'DocumentationController@getDocumentationFile');

//Routes that need authentication and to be admin
Route::middleware(['auth:api', 'refresh_token'])->group(function () {

    //User related Routes
    Route::post('users', 'UserController@createUser');
    Route::put('users/self/{id}', 'UserController@modifyUserIdentification');
    Route::delete('users/{id}', 'UserController@deleteUser');
    Route::post('users/{id}/affiliated-centers', 'UserController@addAffiliatedCenter');
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

    //Study Routes
    Route::post('studies', 'StudyController@createStudy');

    //Centers Routes
    Route::post('centers', 'CenterController@createCenter');

    //Preferences Routes
    Route::get('preferences', 'PreferenceController@getPreference');

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

    //studies Routes
    Route::get('patients/{code?}', 'PatientController@getPatient');
    Route::get('studies/{studyName}/patients', 'PatientController@getPatientFromStudy');
    Route::post('studies/{studyName}/import-patients', 'StudyController@importPatients');
    Route::get('studies/{studyName}/documentations', 'DocumentationController@getDocumentationsFromStudy');

    //Visit Routes
    Route::post('studies/{studyName}/visit-groups/{visitGroupId}/visit-types/{visitTypeId}/visits', 'VisitController@createVisit');
    Route::get('visits/{id}', 'VisitController@getVisit');
    Route::get('visits/{id}/patients/{patientCode}', 'VisitController@getPatientVisit');

    //Documentations routes
    Route::post('studies/{studyName}/documentations', 'DocumentationController@createDocumentation');
    Route::post('documentations/{id}/file', 'DocumentationController@uploadDocumentation');
    Route::delete('documentations/{id}', 'DocumentationController@deleteDocumentation');
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
