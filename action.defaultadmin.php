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
if ( !$this->VisibleToAdminUser() ) return;

$selected_type = $this->GetPreference('selected_type', '');

// if set, create the import/export object
if ( in_array( $selected_type, ImportExport::IMPORT_EXPORT_TYPES ) ) {
    $selected_type_class = ImportExport::CLASS_PREFIX.$selected_type;
    $import_export = new $selected_type_class();
}

if ( isset($params['submit']) ) {

    switch( $params['submit'] ) {
        case 'change_type':
            // if selected type of import/export changed - save it and reload page
            $selected_type = $params['import_export_type'];
            $this->SetPreference('selected_type', $selected_type);
            $this->Redirect($id, 'defaultadmin', $returnid);
            break;

        case 'continue':
            // give everything to the import_export type and it will do the rest 
            $import_export->process($params);
            break;

        case 'cancel':
            // just go back to the start
            $this->Redirect($id, 'defaultadmin', $returnid);
            break;

        // case 'back':
        //     // ....
        //     break;

        default:
            // do nothing - a value should be set for the submit
    }

} elseif ( isset($import_export)) {
    // if no submit value, but import_export object exists, then just process it - step 1
    $import_export->process();

}


// set up the template
$template_name = $import_export->template_name ?? 'defaultadmin.tpl';
$tpl = $smarty->CreateTemplate( $this->GetTemplateResource($template_name), null, null, $smarty );
$tpl->assign('mod', $this);
$input_output_type_options = ['' => $this->Lang('type_select')];
foreach( ImportExport::IMPORT_EXPORT_TYPES as $type ) {
    $input_output_type_options[$type] = $this->Lang("type_$type");
}
$tpl->assign('input_output_type_options', $input_output_type_options);
$tpl->assign('selected_type', $selected_type);

if ( isset($import_export) ) {
    // pass the entire import_export object to the template & step
    $tpl->assign('import_export',$import_export);
    $tpl->assign('extraparms', ['step'=>$import_export->step]);
    $tpl->assign( 'selected_sources', $import_export->get_selected_sources() );
}

$tpl->display();

if ( isset($import_export) ) {
    $import_export->ajax_key = null; // clear any previous key - data already saved & given to smarty 
}


