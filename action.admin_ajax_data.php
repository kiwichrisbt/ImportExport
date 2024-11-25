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

    case 'ajax_process':
        $supplied_key = isset($_REQUEST['key']) ? $_REQUEST['key'] : '';
        $saved_details = $this->get_ajax_processing_details($supplied_key);
        if ( empty($saved_details) ) {
            $this->OutputAjaxError('Invalid request to ajax_process'); // & die
            return; // not needed - just to make it obvious
        }

        // start the ajax processing
        $type_class = ImportExport::CLASS_PREFIX.$saved_details->type;  // saved as array, read as object!
        $import_export = new $type_class();
        // could probably move the following to ... somewhere else
        $import_export->ajax_status = $saved_details->ajax_status;
        $import_export->ajax_position = $saved_details->ajax_position;
        $import_export->file_exists_count = $saved_details->file_exists_count;
        $import_export->file_error_count = $saved_details->file_error_count;
        $import_export->file_saved_count = $saved_details->file_saved_count;

        if ( !empty($_REQUEST['retry']) ) $import_export->retry = $_REQUEST['retry'];

        $import_export->ajax_process($saved_details);

        // pass results back to the client browser
        if ( !empty($import_export->progress) ) {
            $data = [
                'key' => $import_export->ajax_key,
                'progress' => $import_export->progress,
                'status' => $import_export->ajax_status,
                'position' => $import_export->ajax_position,
                'messages' => $import_export->messageManager->getMessages(), // test this instead of the below
                'errors' => $import_export->messageManager->getErrors(),
                'feedback' => $import_export->ajax_feedback,    // update existing messages e.g. count info
            ];
        }
        break;

}


// return data
http_response_code(200);    // signal OK
die( json_encode($data) );     // array
