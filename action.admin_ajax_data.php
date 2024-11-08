<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
/** 
*   @var \ImportExport $this 
*/


if ( !defined('CMS_VERSION') ) exit;

if ( !$this->VisibleToAdminUser() ) {
    $this->OutputAjaxError('Error - not Admin User');
}    
if ( empty($params['do']) ) {
    $this->OutputAjaxError('Error - incorrect parameters (admin_ajax_data)'); // & die
} 


$data = [];
switch ($params['do']) {

    case 'get_progress':
        $progress = $this->GetPreference(preference_name: $this::PROGRESS_PREFERENCE);
// // test hack
// $progress = $progress + 10;
// $this->SetPreference($this::PROGRESS_PREFERENCE, $progress);
// // end test hack
        $data = ['progress' => $progress];
        break;


    case 'start_ajax_processing':
        $details = json_decode( $this->GetPreference($this::AJAX_PROCESSING_DETAILS, null) );
        // $details = $this->GetPreference($this::AJAX_PROCESSING_DETAILS, null);
        if ( empty($details) || !in_array($details->type, $this::IMPORT_EXPORT_TYPES) ||
            $params['key']!==$details->key ) {
            $this->OutputAjaxError('Invalid request to start_ajax_processing'); // & die
            return; // not needed - just to make it obvious
        } 

        // start the ajax processing
        $type_class = ImportExport::CLASS_PREFIX.$details->type;
        $import_export = new $type_class();
        $import_export->ajax_process();
        $data = ['success' => true];
        $messages = $import_export->messageManager->getMessages();
        $errors = $import_export->messageManager->getErrors();
        if ( !empty($messages) ) $data['messages'] = $messages;
        if ( !empty($errors) ) $data['errors'] = $errors;
        break;

}


// return data
http_response_code(200);    // signal OK
die( json_encode($data) );     // array
