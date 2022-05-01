<?php

use App\Http\Controllers\AskUnlockController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CenterController;
use App\Http\Controllers\CountryController;
use App\Http\Controllers\DicomController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ExportDBController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ReverseProxyController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StudyController;
use App\Http\Controllers\TrackerController;
use App\Http\Controllers\ToolsController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\VisitGroupController;
use App\Http\Controllers\VisitTypeController;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
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

//Routes that need authentication
Route::middleware(['auth:sanctum', 'verified', 'activated'])->group(function () {

    //Logout Route
    Route::delete('login', [AuthController::class, 'logout']);

    //User related Routes
    Route::get('users/{id?}',  [UserController::class, 'getUser']);
    Route::post('users', [UserController::class, 'createUser']);
    Route::put('users/{id}', [UserController::class, 'modifyUser']);
    Route::patch('users/{id}', [UserController::class, 'modifyUserIdentification']);
    Route::delete('users/{id}', [UserController::class, 'deleteUser']);
    Route::patch('users/{id}/reactivate', [UserController::class, 'reactivateUser']);
    Route::get('users/{id}/affiliated-centers', [UserController::class, 'getAffiliatedCenter']);
    Route::post('users/{id}/affiliated-centers', [UserController::class, 'addAffiliatedCenter']);
    Route::delete('users/{id}/affiliated-centers/{centerCode}', [UserController::class, 'deleteAffiliatedCenter']);
    Route::get('users/{id}/studies',  [UserController::class, 'getStudiesFromUser']);
    Route::get('users/{id}/roles', [UserController::class, 'getRoles']);
    Route::post('users/{id}/roles', [UserController::class, 'createRole']);
    Route::delete('users/{id}/roles/{roleName}', [UserController::class, 'deleteRole']);
    Route::get('studies/{studyName}/users', [UserController::class, 'getUserFromStudy']);
    Route::post('user/{id}/magic-link', [AuthController::class, 'createMagicLink']);

    //Study Routes
    Route::post('studies', [StudyController::class, 'createStudy']);
    Route::get('studies', [StudyController::class, 'getStudies']);
    Route::get('studies/{studyName}/statistics', [StudyController::class, 'getStudyStatistics']);
    Route::get('studies/{studyName}/visit-types', [StudyController::class, 'getStudyDetails']);
    Route::delete('studies/{studyName}', [StudyController::class, 'deleteStudy']);
    Route::patch('studies/{studyName}/reactivate', [StudyController::class, 'reactivateStudy']);
    Route::get('studies/{studyName}/patients', [StudyController::class, 'getPatientFromStudy']);
    Route::get('studies/{studyName}/visits-tree', [StudyController::class, 'getVisitsTree']);
    Route::post('studies/{studyName}/import-patients', [StudyController::class, 'importPatients']);
    Route::get('studies/{studyName}/original-orthanc-study-id/{orthancStudyID}', [StudyController::class, 'isKnownOrthancId']);
    Route::get('studies/{studyName}/possible-uploads', [StudyController::class, 'getPossibleUploads']);
    Route::get('studies/{studyName}/review-progression', [StudyController::class, 'getStudyReviewProgression']);
    Route::get('studies/{studyName}/visits', [VisitController::class, 'getVisitsFromStudy']);
    Route::get('studies/{studyName}/reviews', [StudyController::class, 'getReviewsFromVisitType']);
    Route::get('studies/{studyName}/reviews/metadata', [StudyController::class, 'getReviewsMetadataFromVisitType']);
    Route::get('studies/{studyName}/investigator-forms', [StudyController::class, 'getInvestigatorFormsFromVisitType']);
    Route::get('studies/{studyName}/investigator-forms/metadata', [StudyController::class, 'getInvestigatorFormsMetadataFromVisitType']);
    Route::get('studies/{studyName}/dicom-studies', [StudyController::class, 'getDicomStudiesFromStudy']);
    Route::post('studies/{studyName}/send-reminder', [StudyController::class, 'sendReminder']);
    Route::post('send-mail', [StudyController::class, 'sendMail']);

    //Centers Routes
    Route::post('centers', [CenterController::class, 'createCenter']);
    Route::get('centers/{code?}', [CenterController::class, 'getCenter']);
    Route::patch('centers/{code}', [CenterController::class, 'modifyCenter']);
    Route::get('studies/{studyName}/centers', [CenterController::class, 'getCentersFromStudy']);

    //Countries Routes
    Route::get('countries/{code?}', [CountryController::class, 'getCountry']);

    //Preferences Routes
    Route::get('preferences', [PreferenceController::class, 'getPreference']);

    //VisitGroup Routes
    Route::post('studies/{studyName}/visit-groups', [VisitGroupController::class, 'createVisitGroup']);
    Route::get('visit-groups/{visitGroupId}', [VisitGroupController::class, 'getVisitGroup']);
    Route::delete('visit-groups/{visitGroupId}', [VisitGroupController::class, 'deleteVisitGroup']);

    //VisitType Routes
    Route::post('visit-groups/{visitGroupId}/visit-types', [VisitTypeController::class, 'createVisitType']);
    Route::get('visit-types/{visitTypeId}', [VisitTypeController::class, 'getVisitType']);
    Route::delete('visit-types/{visitTypeId}', [VisitTypeController::class, 'deleteVisitType']);

    //Patients Routes
    Route::patch('patients/{code}', [PatientController::class, 'modifyPatient']);
    Route::get('patients/{code}', [PatientController::class, 'getPatient']);
    Route::get('patients/{patientId}/visits', [PatientController::class, 'getPatientVisit']);
    Route::get('patients/{patientId}/creatable-visits', [PatientController::class, 'getCreatableVisits']);

    //Visits Routes
    Route::post('visits/{id}/validate-dicom', [VisitController::class, 'validateDicom']);
    Route::patch('visits/{id}/quality-control', [VisitController::class, 'modifyQualityControl']);
    Route::patch('visits/{id}/quality-control/reset', [VisitController::class, 'modifyQualityControlReset']);
    Route::post('visits/{id}/quality-control/unlock', [VisitController::class, 'unlockQc']);
    Route::patch('visits/{id}/corrective-action', [VisitController::class, 'modifyCorrectiveAction']);
    Route::patch('visits/{id}/visit-date', [VisitController::class, 'modifyVisitDate']);
    Route::delete('visits/{id}', [VisitController::class, 'deleteVisit']);
    Route::patch('visits/{id}/reactivate', [VisitController::class, 'reactivateVisit']);
    Route::post('visit-types/{visitTypeId}/visits', [VisitController::class, 'createVisit']);
    Route::get('visits/{id}', [VisitController::class, 'getVisit']);

    //Local Form Routes
    Route::get('visits/{id}/investigator-form', [ReviewController::class, 'getInvestigatorForm']);
    Route::delete('visits/{id}/investigator-form', [ReviewController::class, 'deleteInvestigatorForm']);
    Route::post('visits/{id}/investigator-form', [ReviewController::class, 'createInvestigatorForm']);
    Route::put('visits/{id}/investigator-form', [ReviewController::class, 'modifyInvestigatorForm']);
    Route::patch('visits/{id}/investigator-form/unlock', [ReviewController::class, 'unlockInvestigatorForm']);
    Route::get('visits/{id}/investigator-associated-data', [ReviewController::class, 'getAssociatedDataOfVisitForInvestigator']);

    //Review routes
    Route::post('visits/{visitId}/reviews', [ReviewController::class, 'createReviewForm']);
    Route::put('reviews/{id}', [ReviewController::class, 'modifyReviewForm']);
    Route::get('reviews/{id}', [ReviewController::class, 'getReviewForm']);
    Route::delete('reviews/{id}', [ReviewController::class, 'deleteReviewForm']);
    Route::patch('reviews/{id}/unlock', [ReviewController::class, 'unlockReviewForm']);
    Route::post('reviews/{id}/file/{key}', [ReviewController::class, 'createReviewFile']);
    Route::delete('reviews/{id}/file/{key}', [ReviewController::class, 'deleteReviewFile']);
    Route::get('visits/{visitId}/reviews', [ReviewController::class, 'getReviewsFromVisit']);
    Route::get('studies/{studyName}/visits/{visitId}/reviewer-associated-data', [ReviewController::class, 'getAssociatedDataOfVisitForReviewer']);

    //Dicom Routes
    Route::delete('dicom-series/{seriesInstanceUID}', [DicomController::class, 'deleteSeries']);
    Route::patch('dicom-series/{seriesInstanceUID}', [DicomController::class, 'reactivateSeries']);
    Route::patch('dicom-study/{studyInstanceUID}', [DicomController::class, 'reactivateStudy']);
    Route::get('visits/{id}/dicoms', [DicomController::class, 'getVisitDicoms']);

    //Ask Unlock route
    Route::post('visits/{id}/ask-unlock', [AskUnlockController::class, 'askUnlock']);

    //upload Routes
    Route::any('tus/{filename?}', [ReverseProxyController::class, 'tusUpload']);

    //DicomWeb Routes
    Route::get('orthanc/{path?}', [ReverseProxyController::class, 'dicomWebReverseProxy'])->where(['path' => '.*']);

    //Tracker Routes
    Route::get('tracker', [TrackerController::class, 'getTracker']);
    Route::get('studies/{studyName}/tracker', [TrackerController::class, 'getStudyTracker']);
    Route::get('studies/{studyName}/tracker/{trackerOfRole}', [TrackerController::class, 'getStudyTrackerRoleAction']);
    Route::get('studies/{studyName}/visits/{visitId}/tracker', [TrackerController::class, 'getStudyTrackerByVisit']);

    //Documentations routes
    Route::get('studies/{studyName}/documentations', [DocumentationController::class, 'getDocumentationsFromStudy']);
    Route::post('studies/{studyName}/documentations', [DocumentationController::class, 'createDocumentation']);
    Route::post('documentations/{id}/file', [DocumentationController::class, 'uploadDocumentation']);
    Route::delete('documentations/{id}', [DocumentationController::class, 'deleteDocumentation']);
    Route::patch('documentations/{id}', [DocumentationController::class, 'modifyDocumentation']);
    Route::patch('documentations/{id}/reactivate', [DocumentationController::class, 'reactivateDocumentation']);

    //Tools routes
    Route::post('tools/centers/patients-from-centers', [ToolsController::class, 'getPatientsInStudyFromCenters']);
    Route::post('tools/patients/visits-from-patients', [ToolsController::class, 'getPatientsVisitsInStudy']);
    Route::post('tools/find-user', [ToolsController::class, 'findUser']);

    // Binary routes
    Route::get('export-db', [ExportDBController::class, 'exportDB']);
    Route::get('documentations/{id}/file', [DocumentationController::class, 'getDocumentationFile']);
    Route::get('visits/{id}/dicoms/file', [DicomController::class, 'getVisitDicomsFile']);
    Route::get('studies/{studyName}/export', [StudyController::class, 'exportStudyData']);
    Route::post('studies/{studyName}/dicom-series/file', [DicomController::class, 'getSupervisorDicomsFile']);
    Route::get('reviews/{id}/file/{key}', [ReviewController::class, 'getReviewFile']);
});


/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
*/
//Request Route
Route::post('request', [RequestController::class, 'sendRequest']);

//Login Route
Route::post('login', [AuthController::class, 'login'])->name('login');
Route::post('tools/forgot-password', [UserController::class, 'forgotPassword'])->name('password.email');

//Forgot password routes
Route::get('tools/reset-password/{token}', function ($token) {
    return redirect('/reset-password?token='.$token);
})->name('password.reset');

Route::post('tools/reset-password', [UserController::class, 'updatePassword'] )->name('password.update');

//Route to validate email
Route::get('email/verify/{id}/{hash}', function (Request $request) {

    $user = User::findOrFail($request->route('id'));
    if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
        throw new AuthorizationException();
    }

    if ($user->markEmailAsVerified()) event(new Verified($user));

    return redirect('/email-verified');
})->middleware(['signed'])->name('verification.verify');

//Magic link route
Route::get('magic-link/{id}', [AuthController::class, 'getMagicLink'])->name('magic-link');