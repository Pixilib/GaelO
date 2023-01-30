<?php

namespace App\GaelO\Constants;

class Constants
{

    const USER_EMAIL_NOT_VERIFIED = 'Email Not Verified';
    const USER_DELETED = 'User Deleted';
    const USER_BLOCKED = 'User Blocked';
    const USER_NOT_ONBOARDED = 'User Missing Onboarding';

    const ROLE_INVESTIGATOR = "Investigator";
    const ROLE_MONITOR = "Monitor";
    const ROLE_CONTROLLER = "Controller";
    const ROLE_SUPERVISOR  = "Supervisor";
    const ROLE_REVIEWER = "Reviewer";
    const ROLE_ADMINISTRATOR = "Administrator";

    const TRACKER_EDIT_PREFERENCE = "Preference edited";
    const TRACKER_SEND_MESSAGE = "Send Message";
    const TRACKER_CREATE_VISIT_GROUP  = "Create Visit Group";
    const TRACKER_CREATE_VISIT = "Create Visit";
    const TRACKER_UPLOAD_SERIES = "Upload Series";
    const TRACKER_UPDATE_VISIT_DATE = "Update Visit Date";
    const TRACKER_UPLOAD_VALIDATION_FAILED = "Upload Failed";
    const TRACKER_ASK_UNLOCK = "Ask Unlock Form";
    const TRACKER_UNLOCK_INVESTIGATOR_FORM = "Unlock Investigator Form";
    const TRACKER_UNLOCK_REVIEWER_FORM = "Unlock Reviewer Form";
    const TRACKER_CREATE_STUDY = "Create Study";
    const TRACKER_DEACTIVATE_STUDY = "Deactivate Study";
    const TRACKER_REACTIVATE_STUDY = "Reactivate Study";
    const TRACKER_RESET_QC = "Reset QC";
    const TRACKER_REACTIVATE_VISIT = "Reactivate Visit";
    const TRACKER_DELETE_DICOM_SERIES = "Delete DICOM Series";
    const TRACKER_REACTIVATE_DICOM_SERIES = "Reactivate DICOM Series";
    const TRACKER_REACTIVATE_DICOM_STUDY = "Reactivate DICOM Study";
    const TRACKER_DELETE_VISIT = "Delete Visit";
    const TRACKER_DELETE_INVESTIGATOR_FORM = "Delete Investigator Form";
    const TRACKER_DELETE_REVIEWER_FORM = "Delete Reviewer Form";
    const TRACKER_ACCOUNT_BLOCKED = "Account Blocked";
    const TRACKER_SAVE_INVESTIGATOR_FORM = "Save Investigator Form";
    const TRACKER_MODIFY_INVESTIGATOR_FORM = "Modify Investigator Form";
    const TRACKER_SAVE_REVIEWER_FORM = "Save Reviewer Form";
    const TRACKER_MODIFY_REVIEWER_FORM = "Modify Reviewer Form";
    const TRACKER_EDIT_PATIENT = "Edit Patient";
    const TRACKER_IMPORT_PATIENT = "Import Patients";
    const TRACKER_ADD_DOCUMENTATION = "Add Documentation";
    const TRACKER_UPLOAD_DOCUMENTATION = "Upload Documentation";
    const TRACKER_UPDATE_DOCUMENTATION = "Update Documentation";
    const TRACKER_DELETE_DOCUMENTATION = "Delete Documentation";
    const TRACKER_REACTIVATE_DOCUMENTATION = "Reactivate Documentation";
    const TRACKER_PATIENT_WITHDRAW = "Patient Withdrawal";
    const TRACKER_CORRECTIVE_ACTION = "Corrective Action";
    const TRACKER_QUALITY_CONTROL = "Quality Control";
    const TRACKER_CREATE_USER = "Create User";
    const TRACKER_EDIT_USER = "Edit User";
    const TRACKER_CREATE_CENTER = "Create Center";
    const TRACKER_EDIT_CENTER = "Edit Center";
    const TRACKER_RESET_PASSWORD = "Ask New Password";
    const TRACKER_CHANGE_PASSWORD = "Password Changed";
    const TRACKER_ROLE_USER = "User";
    const TRACKER_ROLE_ADMINISTRATOR = "Administrator";
    const TRACKER_VALIDATED_DOCUMENTATION = "Validated Documentation";

    const ORTHANC_PATIENTS_LEVEL = "patients";
    const ORTHANC_STUDIES_LEVEL = "studies";
    const ORTHANC_SERIES_LEVEL = "series";
}
