<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DicomController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ExportDBController;
use App\Http\Controllers\FormController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ReverseProxyController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\VisitGroupController;
use App\Http\Controllers\VisitTypeController;
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
    Route::delete('login', [AuthController::class, 'logout']);

    //User related Routes
    Route::get('users/{id?}',  [UserController::class, 'getUser']);
    Route::post('users', [UserController::class, 'createUser']);
    Route::delete('users/{id}', [UserController::class, 'deleteUser'] );
    Route::post('users/{id}/affiliated-centers', [UserController::class, 'addAffiliatedCenter'] );
    Route::put('users/{id}', [UserController::class, 'modifyUser'] );
    Route::patch('users/{id}', [UserController::class, 'modifyUserIdentification'] );
    Route::patch('users/{id}/reactivate', [UserController::class, 'reactivateUser'] );
    Route::get('users/{id}/roles/{study?}', [UserController::class, 'getRoles'] );
    Route::post('users/{id}/roles/{study}', [UserController::class, 'createRole'] );
    Route::delete('users/{id}/roles/{study}/{roleName}', [UserController::class, 'deleteRole'] );

    Route::get('users/{id}/affiliated-centers', [UserController::class, 'getAffiliatedCenter'] );
    Route::delete('users/{id}/affiliated-centers/{centerCode}', [UserController::class, 'deleteAffiliatedCenter'] );
    Route::get('studies/{studyName}/users', [UserController::class, 'getUserFromStudy'] );

    //Study Routes
    Route::get('studies', [StudyController::class, 'getStudy'] );
    Route::delete('studies/{studyName}', [StudyController::class, 'deleteStudy'] );
    Route::patch('studies/{studyName}/reactivate', [StudyController::class, 'reactivateStudy'] );
    Route::patch('studies/{studyName}/creatable-visits', [StudyController::class, 'creatableVisits'] );
    Route::get('studies/{studyName}/patients', [StudyController::class, 'getPatientFromStudy'] );
    Route::get('studies/{studyName}/visits-tree', [StudyController::class, 'getVisitsTree'] );
    Route::post('studies/{studyName}/import-patients', [StudyController::class, 'importPatients'] );
    Route::get('studies/{studyName}/orthanc-study-id/{orthancStudyID}', [StudyController::class, 'isKnownOrthancId'] );
    Route::get('studies/{studyName}/possible-uploads', [StudyController::class, 'getPossibleUploads'] );

    //Study Routes
    Route::post('studies', [StudyController::class, 'createStudy'] );

    //Centers Routes
    Route::post('centers', [CenterController::class, 'createCenter'] );
    Route::get('centers/{code?}', [CenterController::class, 'getCenter'] );
    Route::patch('centers/{code}', [CenterController::class, 'modifyCenter'] );

    //Countries Routes
    Route::get('countries/{code?}', [CountryController::class, 'getCountry'] );

    //Preferences Routes
    Route::get('preferences', [PreferenceController::class, 'getPreference'] );

    //VisitGroup Routes
    Route::post('studies/{studyName}/visit-groups', [VisitGroupController::class, 'createVisitGroup'] );
    Route::get('visit-groups/{visitGroupId}', [VisitGroupController::class, 'getVisitGroup'] );
    Route::delete('visit-groups/{visitGroupId}', [VisitGroupController::class, 'deleteVisitGroup'] );

    //VisitType Routes
    Route::post('visit-groups/{visitGroupId}/visit-types', [VisitTypeController::class, 'createVisitType'] );
    Route::get('visit-types/{visitTypeId}', [VisitTypeController::class, 'getVisitType'] );
    Route::delete('visit-types/{visitTypeId}', [VisitTypeController::class, 'deleteVisitType'] );

    //Patients Routes
    Route::get('patients/{code?}', [PatientController::class, 'getPatient'] );
    Route::patch('patients/{code?}', [PatientController::class, 'modifyPatient'] );
    Route::patch('patients/{code?}/inclusion-status', [PatientController::class, 'modifyPatientInclusionStatus'] );
    Route::get('studies/{studyName}/patients/{patientCode}/visits', [PatientController::class, 'getPatientVisit'] );
    Route::get('studies/{studyName}/patients/{patientCode}/creatable-visits', [PatientController::class, 'getCreatableVisits'] );

    //Visits Routes
    Route::post('visits/{id}/validate-dicom', [VisitController::class, 'validateDicom'] );
    Route::patch('visits/{id}/quality-control', [VisitController::class, 'modifyQualityControl'] );
    Route::patch('visits/{id}/quality-control/reset', [VisitController::class, 'modifyQualityControlReset'] );
    Route::patch('visits/{id}/corrective-action', [VisitController::class, 'modifyCorrectiveAction'] );
    Route::delete('visits/{id}', [VisitController::class, 'deleteVisit'] );
    Route::post('studies/{studyName}/visit-groups/{visitGroupId}/visit-types/{visitTypeId}/visits', [VisitController::class, 'createVisit'] );
    Route::get('studies/{studyName}/visits/{id}', [VisitController::class, 'getVisit'] );

    //Dicom Routes
    Route::delete('dicom-series/{seriesInstanceUID}', [DicomController::class, 'deleteSeries'] );
    Route::patch('dicom-series/{seriesInstanceUID}', [DicomController::class, 'reactivateSeries'] );
    Route::patch('dicom-study/{studyInstanceUID}', [DicomController::class, 'reactivateStudy'] );
    Route::get('visits/{id}/dicoms', [DicomController::class, 'getVisitDicoms'] );

    //Form routes
    Route::post('studies/{study}/visits/{id}/ask-unlock', [FormController::class, 'askUnlock'] );

    //upload Routes
    Route::any('tus/{filename?}', [ReverseProxyController::class, 'tusUpload'] );

    //DicomWeb Routes
    Route::get('orthanc/{path?}', [ReverseProxyController::class, 'dicomWebReverseProxy'] )->where(['path' => '.*']);

    //Tracker Routes
    Route::get('tracker', [TrackerController::class, 'getTracker'] );
    Route::get('studies/{studyName}/tracker', [TrackerController::class, 'getStudyTracker'] );


    //Documentations routes
    Route::get('studies/{studyName}/documentations', [DocumentationController::class, 'getDocumentationsFromStudy'] );
    Route::post('studies/{studyName}/documentations', [DocumentationController::class, 'createDocumentation'] );
    Route::post('documentations/{id}/file', [DocumentationController::class, 'uploadDocumentation'] );
    Route::delete('documentations/{id}', [DocumentationController::class, 'deleteDocumentation'] );
    Route::patch('studies/{studyName}/documentations/{id}', [DocumentationController::class, 'modifyDocumentation'] );
});


/*
|--------------------------------------------------------------------------
| Binary Routes
| Doesn't support refresh token
|--------------------------------------------------------------------------
|
*/
Route::middleware('auth:api')->post('export-db', [ExportDBController::class, 'exportDB'] );
Route::middleware('auth:api')->get('documentations/{id}/file', [DocumentationController::class, 'getDocumentationFile'] );


Route::middleware('auth:api')->get('visits/{id}/dicoms/file', [DocumentationController::class, 'getVisitDicomsFile'] );

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
*/

//Request Route
Route::post('request', [RequestController::class, 'sendRequest'] );

//Login and password Route
Route::post('login', [AuthController::class, 'login'] )->name('login');
Route::put('users/{id}/password', [UserController::class, 'changeUserPassword'] );
Route::post('tools/reset-password', [ToolsController::class, 'resetPassword'] );
