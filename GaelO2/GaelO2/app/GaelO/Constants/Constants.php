<?php

namespace App\GaelO\Constants;
class Constants {

    const USER_STATUS_UNCONFIRMED = "Unconfirmed";
    const USER_STATUS_ACTIVATED = "Activated";
    const USER_STATUS_BLOCKED = "Blocked";
    const USER_STATUS_DEACTIVATED = "Deactivated";

    const ROLE_INVESTIGATOR = "Investigator";
    const ROLE_MONITOR = "Monitor";
    const ROLE_CONTROLER = "Controller";
    const ROLE_SUPERVISOR  = "Supervisor";
    const ROLE_REVIEWER = "Reviewer";

    const TRACKER_EDIT_PREFERENCE = "Preference edited";
    const TRACKER_SEND_MESSAGE = "Send Message";
    const TRACKER_CREATE_VISIT = "Create Visit";
    const TRACKER_UPLOAD_SERIES = "Upload Series";
    const TRACKER_UNLOCK_FORM = "Unlock Form";
    const TRACKER_CREATE_STUDY = "Create Study";
    const TRACKER_DEACTIVATE_STUDY = "Deactivate Study";
    const TRACKER_REACTIVATE_STUDY = "Reactivate Study";
    const TRACKER_RESET_QC = "Reset QC";
    const TRACKER_REACTIVATE_VISIT = "Reactivate Visit";
    const TRACKER_CHANGE_DICOM_DELETION = "DICOM Deletion Change";
    const TRACKER_DELETE_VISIT = "Delete Visit";
    const TRACKER_DELETE_FORM = "Delete Form";
    const TRACKER_ACCOUNT_BLOCKED = "Account Blocked";
    const TRACKER_SAVE_FORM = "Save Form";
    const TRACKER_EDIT_PATIENT = "Edit Patient";
    const TRACKER_IMPORT_PATIENT = "Import Patients";
    const TRACKER_ADD_DOCUMENTATION = "Add Documentation";
    const TRACKER_UPDATE_DOCUMENTATION = "Update Documentation";
    const TRACKER_PATIENT_WITHDRAW = "Patient Withdraw";
    const TRACKER_CORRECTIVE_ACTION = "Corrective Action";
    const TRACKER_QUALITY_CONTROL = "Quality Control";
    const TRACKER_CREATE_USER = "Create User";
    const TRACKER_EDIT_USER = "Edit User";
    const TRACKER_EDIT_CENTER = "Edit Center";
    const TRACKER_RESET_PASSWORD = "Ask New Password";
    const TRACKER_CHANGE_PASSWORD = "Password Changed";
    const TRACKER_ROLE_USER = "User";
    const TRACKER_ROLE_ADMINISTRATOR = "Administrator";

    const INVESTIGATOR_FORM_NOT_DONE = "Not Done";
    const INVESTIGATOR_FORM_DONE = "Done";
    const INVESTIGATOR_FORM_NOT_NEEDED = "Not Needed";
    const INVESTIGATOR_FORM_DRAFT = "Draft";

    const QUALITY_CONTROL_NOT_DONE = "Not Done";
    const QUALITY_CONTROL_NOT_NEEDED = "Not Needed";
    const QUALITY_CONTROL_WAIT_DEFINITIVE_CONCLUSION = "Wait Definitive Conclusion";
    const QUALITY_CONTROL_CORRECTIVE_ACTION_ASKED = "Corrective Action Asked";
    const QUALITY_CONSTROL_REFUSED = "Refused";
    const QUALITY_CONTROL_ACCEPTED = "Accepted";

    const ORTHANC_ANON_PROFILE_DEFAULT = "Default";
    const ORTHANC_ANON_PROFILE_FULL = "Full";
    const ORTHANC_PATIENTS_LEVEL="patients";
    const ORTHANC_STUDIES_LEVEL="studies";
    const ORTHANC_SERIES_LEVEL="series";
}
