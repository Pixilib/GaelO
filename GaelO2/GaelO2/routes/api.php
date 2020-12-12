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

//Routes that need authentication
Route::middleware(['auth:api', 'refresh_token'])->group(function () {

    //Logout Route
    Route::delete('login', 'AuthController@logout');

    //User related Routes
    Route::get('users/{id?}', 'UserController@getUser');
    Route::post('users', 'UserController@createUser');
    Route::delete('users/{id}', 'UserController@deleteUser');
    Route::post('users/{id}/affiliated-centers', 'UserController@addAffiliatedCenter');
    Route::put('users/{id}', 'UserController@modifyUser');
    Route::patch('users/{id}', 'UserController@modifyUserIdentification');
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
    Route::patch('studies/{studyName}/creatable-visits', 'StudyController@creatableVisits');
    Route::get('studies/{studyName}/patients', 'PatientController@getPatientFromStudy');
    Route::get('studies/{studyName}/visits-tree', 'StudyController@getVisitsTree');
    Route::post('studies/{studyName}/import-patients', 'StudyController@importPatients');
    Route::get('studies/{studyName}/orthanc-study-id/{orthancStudyID}', 'StudyController@isKnownOrthancId');
    Route::get('studies/{studyName}/possible-uploads', 'StudyController@getPossibleUploads');

    //Study Routes
    Route::post('studies', 'StudyController@createStudy');

    //Centers Routes
    Route::post('centers', 'CenterController@createCenter');

    //Countries Routes
    Route::get('countries/{code?}', 'CountryController@getCountry');

    //Preferences Routes
    Route::get('preferences', 'PreferenceController@getPreference');

    //Centers Routes
    Route::get('centers/{code?}', 'CenterController@getCenter');
    Route::patch('centers/{code}', 'CenterController@modifyCenter');

    //VisitGroup Routes
    Route::post('studies/{studyName}/visit-groups', 'VisitGroupController@createVisitGroup');
    Route::get('visit-groups/{visitGroupId}', 'VisitGroupController@getVisitGroup');
    Route::delete('visit-groups/{visitGroupId}', 'VisitGroupController@deleteVisitGroup');

    //VisitType Routes
    Route::post('visit-groups/{visitGroupId}/visit-types', 'VisitTypeController@createVisitType');
    Route::get('visit-types/{visitTypeId}', 'VisitTypeController@getVisitType');
    Route::delete('visit-types/{visitTypeId}', 'VisitTypeController@deleteVisitType');

    //Patients Routes
    Route::get('patients/{code?}', 'PatientController@getPatient');
    Route::patch('patients/{code?}', 'PatientController@modifyPatient');
    Route::patch('patients/{code?}/inclusion-status', 'PatientController@modifyPatientInclusionStatus');
    Route::get('patients/{patientCode}/visits', 'PatientController@getPatientVisit');
    Route::get('patients/{patientCode}/creatable-visits', 'PatientController@getCreatableVisits');

    //Visits Routes
    Route::post('visits/{id}/validate-dicom', 'VisitController@validateDicom');
    Route::patch('visits/{id}/quality-control', 'VisitController@modifyQualityControl');
    Route::patch('visits/{id}/quality-control/reset', 'VisitController@modifyQualityControlReset');
    Route::patch('visits/{id}/corrective-action', 'VisitController@modifyCorrectiveAction');
    Route::delete('visits/{id}', 'VisitController@deleteVisit');
    Route::post('studies/{studyName}/visit-groups/{visitGroupId}/visit-types/{visitTypeId}/visits', 'VisitController@createVisit');
    Route::get('visits/{id}', 'VisitController@getVisit');
    Route::get('visits/{id}/dicoms', 'DicomController@getVisitDicoms');

    //Dicom Routes
    Route::delete('dicom-series/{seriesInstanceUID}', 'DicomController@deleteSeries');

    //Form routes
    Route::post('studies/{study}/visits/{id}/ask-unlock', 'FormController@askUnlock');

    //upload Routes
    Route::any('tus/{filename?}', 'ReverseProxyController@tusUpload');

    //DicomWeb Routes
    Route::get('orthanc/{path?}', 'ReverseProxyController@dicomWebReverseProxy')->where(['path' => '.*']);;

    //Tracker Routes
    Route::get('tracker', 'TrackerController@getTracker');

    //Documentations routes
    Route::get('studies/{studyName}/documentations', 'DocumentationController@getDocumentationsFromStudy');
    Route::post('studies/{studyName}/documentations', 'DocumentationController@createDocumentation');
    Route::post('documentations/{id}/file', 'DocumentationController@uploadDocumentation');
    Route::delete('documentations/{id}', 'DocumentationController@deleteDocumentation');
});


/*
|--------------------------------------------------------------------------
| Binary Routes
| Doesn't support refresh token
|--------------------------------------------------------------------------
|
*/
Route::middleware('auth:api')->post('export-db', 'ExportDBController@exportDB');
Route::middleware('auth:api')->get('documentations/{id}/file', 'DocumentationController@getDocumentationFile');


Route::middleware('auth:api')->get('visits/{id}/dicoms/file', 'DicomController@getVisitDicomsFile');

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
*/

//Request Route
Route::post('request', 'RequestController@sendRequest');

//Login and password Route
Route::post('login', 'AuthController@login')->name('login');
Route::put('users/{id}/password', 'UserController@changeUserPassword');
Route::post('tools/reset-password', 'ToolsController@resetPassword');
