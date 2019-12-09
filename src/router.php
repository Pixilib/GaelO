<?php
/**
 Copyright (C) 2018 KANOUN Salim
 This program is free software; you can redistribute it and/or modify
 it under the terms of the Affero GNU General Public v.3 License as published by
 the Free Software Foundation;
 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 Affero GNU General Public Public for more details.
 You should have received a copy of the Affero GNU General Public Public along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA
 */

/**
 * Router
 */

if ( !isset($_GET['page']) ) {
    require 'controllers/index_controller.php';
    
}else if ($_GET['page'] == 'controller_form') {
    require 'controllers/investigator/controller_form_controller.php';
    
}else if ($_GET['page'] == 'corrective_action') {
    require 'controllers/investigator/corrective_action_controller.php';
    
}else if ($_GET['page'] == 'root_investigator') {
    require 'controllers/investigator/root_investigator_controller.php';
    
}else if ($_GET['page'] == 'documentation') {
    require 'controllers/investigator/documentation_controller.php';
    
}else if ($_GET['page'] == 'patient_interface') {
    require 'controllers/investigator/patient_interface_controller.php';
    
}else if ($_GET['page'] == 'ask_unlock') {
    require 'controllers/investigator/ask_unlock_controller.php';
    
}else if ($_GET['page'] == 'visit_interface') {
    require 'controllers/investigator/visit_interface_controller.php';
    
}else if ($_GET['page'] == 'new_visit') {
    require 'controllers/investigator/new_visit_controller.php';
    
}else if ($_GET['page'] == 'specific_form'){
    require 'controllers/investigator/specific_form_controller.php';
    
}else if ($_GET['page'] == 'administrator') {
    require 'controllers/administrator/root_administrator_controller.php';
    
}else if ($_GET['page'] == 'study_activation') {
    require 'controllers/administrator/study_activation_controller.php';
    
}else if ($_GET['page'] == 'modify_centers') {
    require 'controllers/administrator/modify_centers_controller.php';
    
}else if ($_GET['page'] == 'preferences'){
    require 'controllers/administrator/preferences_controller.php';
    
}else if ($_GET['page'] == 'user_table'){
    require 'controllers/administrator/user_table_controller.php';
    
}else if ($_GET['page'] == 'new_user'){
    require 'controllers/administrator/new_user_controller.php';
    
}else if ($_GET['page'] == 'modify_user'){
    require 'controllers/administrator/modify_user_controller.php';
    
}else if ($_GET['page'] == 'my_account'){
    require 'controllers/my_account_controller.php';
    
}else if ($_GET['page'] == 'export_database'){
    require 'controllers/administrator/export_database_controller.php';
    
}else if ($_GET['page'] == 'tracker_user'){
    require 'controllers/administrator/tracker_user_controller.php';
    
}else if ($_GET['page'] == 'tracker_admin'){
    require 'controllers/administrator/tracker_admin_controller.php';
    
}else if ($_GET['page'] == 'create_study'){
    require 'controllers/administrator/create_study_controller.php';
    
}else if ($_GET['page'] == 'visit_builder'){
    require 'controllers/administrator/visit_builder_controller.php';
    
}else if ($_GET['page'] == 'preferences'){
    require 'controllers/administrator/preferences_controller.php';
    
}else if ($_GET['page'] == 'change_patient_status'){
    require 'controllers/supervisor/change_patient_status_controller.php';
    
}else if ($_GET['page'] == 'reminder_emails'){
    require 'controllers/supervisor/reminder_emails_controller.php';
    
}else if ($_GET['page'] == 'documentation_supervisor'){
    require 'controllers/supervisor/documentation_controller.php';
    
}else if ($_GET['page'] == 'documentation_upload'){
    require 'controllers/supervisor/documentation_upload_controller.php';
    
}else if ($_GET['page'] == 'users_details'){
    require 'controllers/supervisor/users_details_controller.php';
    
}else if ($_GET['page'] == 'import_patients'){
    require 'controllers/supervisor/import_patients_controller.php';
    
}else if ($_GET['page'] == 'patient_infos'){
    require 'controllers/supervisor/patient_infos_controller.php';
    
}else if ($_GET['page'] == 'visit_infos'){
    require 'controllers/supervisor/visit_infos_controller.php';
    
}else if ($_GET['page'] == 'root_supervisor'){
    require 'controllers/supervisor/supervisor_root_controller.php';
    
}else if ($_GET['page'] == 'upload_manager'){
    require 'controllers/supervisor/upload_manager_controller.php';
    
}else if ($_GET['page'] == 'review_manager'){
    require 'controllers/supervisor/review_manager_controller.php';
    
}else if ($_GET['page'] == 'download_manager'){
    require 'controllers/supervisor/download_manager_controller.php';
    
}else if ($_GET['page'] == 'tracker'){
    require 'controllers/supervisor/tracker_controller.php';
    
}else if ($_GET['page'] == 'statistics'){
    require 'controllers/supervisor/statistics_controller.php';
    
}else if ($_GET['page'] == 'change_password'){
    require 'controllers/change_password_controller.php';
    
}else if ($_GET['page'] == 'forgot_password'){
    require 'controllers/forgot_password_controller.php';
    
}else if ($_GET['page'] == 'request'){
    require 'controllers/request_controller.php';
    
}else if ($_GET['page'] == 'messenger'){
    require 'controllers/messenger_controller.php';
    
}else if ($_GET['page'] == 'main'){
    require 'controllers/main_controller.php';
    
}else if (strpos($_GET['page'], 'orthanc') === 0){
    //Start with Orthanc to reach orthanc
    require 'scripts/dicom_web.php';
    
}else{
    require 'includes/404.php';
}