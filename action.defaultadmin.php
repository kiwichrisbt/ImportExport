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
$uid = get_userid();
if ( !UserOperations::get_instance()->IsSuperuser($uid) ) return;

$selected_type = $this->GetPreference('selected_type', '');

// if set, create the import/export object
if ( in_array( $selected_type, ImportExport::IMPORT_EXPORT_TYPES ) ) {
    $selected_type_class = ImportExport::CLASS_PREFIX.$selected_type;
    $import_export = new $selected_type_class();
}

// check for change in selected type of import_export - if so, save it and reload page
// if ( isset($params['submit']) && $params['submit']=='change_type' ) {
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



// OLD STUFF below here - Delete when finished - just for reference for now...

// // config defaults
// $wpconfig = array();
// $wpconfig['root_path'] = '';
// $wpconfig['root_url'] = '';
// $wpconfig['db_host'] = 'localhost';
// $wpconfig['db_user'] = '';
// $wpconfig['db_pass'] = '';
// $wpconfig['db_name'] = '';
// $wpconfig['db_prefix'] = 'wp_';
// $tmp = cms_userprefs::get('ImporterExporter_wpconfig');
// if( $tmp ) $wpconfig = unserialize($tmp);

// $options['clear_news_posts'] = 1;
// $options['clear_news_fielddefs'] = 0;
// $options['clear_news_categories'] = 0;
// $options['import_categories'] = 1;
// $options['import_posts'] = 1;
// $options['import_images'] = 1;
// $options['image_paths'] = '';
// $options['image_dest'] = '';
// // $options['import_users'] = 1;
// $options['default_author'] = 0;
// $options['default_category'] = 0;
// $options['import_thumbnails'] = 0;
// $options['user_pw'] = 'changeme';
// $options['enable_postmeta_mapping'] = FALSE;
// $options['postmeta_mapping'] = array();
// $tmp = cms_userprefs::get('ImporterExporter_options');
// if( $tmp ) $options = unserialize($tmp);

// try {
//   if( isset($params['wp_check']) ) {
//     $wpconfig['root_path'] = trim(cge_utils::get_param($params,'root_path'));
//     ImporterExporter_utils::check_wp_dir($wpconfig['root_path']);
//     echo $this->ShowMessage($this->Lang('msg_wpfound'));
//   }

//   if( isset($params['wp_test']) ) {
//     $wpconfig['root_path'] = trim(cge_utils::get_param($params,'root_path'));
//     $wpconfig['root_url'] = trim(cge_utils::get_param($params,'root_url'));
//     $wpconfig['db_host'] = trim(cge_utils::get_param($params,'db_host'));
//     $wpconfig['db_user'] = trim(cge_utils::get_param($params,'db_user'));
//     $wpconfig['db_pass'] = trim(cge_utils::get_param($params,'db_pass'));
//     $wpconfig['db_name'] = trim(cge_utils::get_param($params,'db_name'));
//     $wpconfig['db_prefix'] = trim(cge_utils::get_param($params,'db_prefix'));
//     $wpdb = ImporterExporter_utils::get_wp_db($wpconfig);
//     ImporterExporter_utils::check_wp_db($wpdb);

//     $query = 'SELECT DISTINCT meta_key FROM '.$wpconfig['db_prefix'].'postmeta';
//     $metakeys = $wpdb->GetCol($query);
//     $out = array('none'=>$this->Lang('none'));
//     foreach( $metakeys as $one ) {
//         $out[$one] = $one;
//     }
//     $smarty->assign('wp_metakeys',$out);
//     $smarty->assign('db_tested',1);
//   }

//   if( isset($params['wp_import']) ) {
//     $wpconfig['root_path'] = trim(cge_utils::get_param($params,'root_path'));
//     $wpconfig['root_url'] = trim(cge_utils::get_param($params,'root_url'));
//     $wpconfig['db_host'] = trim(cge_utils::get_param($params,'db_host'));
//     $wpconfig['db_user'] = trim(cge_utils::get_param($params,'db_user'));
//     $wpconfig['db_pass'] = trim(cge_utils::get_param($params,'db_pass'));
//     $wpconfig['db_name'] = trim(cge_utils::get_param($params,'db_name'));
//     $wpconfig['db_prefix'] = trim(cge_utils::get_param($params,'db_prefix'));
//     $wpdb = ImporterExporter_utils::get_wp_db($wpconfig);
//     ImporterExporter_utils::check_wp_db($wpdb);

//     // get options
//     $options = array();
//     $options['clear_news_posts'] = (int)cge_utils::get_param($params,'clear_news_posts');
//     $options['clear_news_fielddefs'] = (int)cge_utils::get_param($params,'clear_news_fielddefs');
//     $options['clear_news_categories'] = (int)cge_utils::get_param($params,'clear_news_categories');
//     $options['import_categories'] = (int)cge_utils::get_param($params,'import_categories');
//     $options['import_posts'] = (int)cge_utils::get_param($params,'import_posts');
//     $options['import_images'] = (int)cge_utils::get_param($params,'import_images');
//     // $options['import_users'] = (int)cge_utils::get_param($params,'import_users');
//     $options['user_pw'] = trim(cge_utils::get_param($params,'user_pw'));
//     $options['image_paths'] = trim(cge_utils::get_param($params,'image_paths'));
//     $options['image_dest'] = trim(cge_utils::get_param($params,'image_dest'));
//     $options['enable_postmeta_mapping'] = (int)cge_utils::get_param($params,'enable_postmeta_mapping');
//     $options['default_author'] = (int)cge_utils::get_param($params,'default_author');
//     $options['default_category'] = (int)cge_utils::get_param($params,'default_category');
//     $options['import_thumbnails'] = (int)cge_utils::get_param($params,'import_thumbnails');

//     // clean up the postmeta mappings
//     if( $options['enable_postmeta_mapping'] && isset($params['postmeta']) ) {
//         if( count($params['postmeta']['wpkey']) != count($params['postmeta']['fldname']) ) throw new Exception($this->Lang('error_invalid_postmeta'));
//         $data = array();
//         if( 1 ) {
//             $tmp1 = $params['postmeta']['wpkey'];
//             $tmp2 = $params['postmeta']['fldname'];
//             for( $i = 0; $i < count($tmp1); $i++ ) {
//                 $data[$tmp1[$i]] = $tmp2[$i];
//             }
//         }
//         $options['postmeta_mapping'] = $data;

//         $clean_array = function($data,$name){
//             $mod = cms_utils::get_module('ImporterExporter');
//             $tmp = array();
//             foreach( $data as $one ) {
//                 $one = trim($one);
//                 if( !$one || $one == -1 ) throw new Exception($mod->Lang('error_invalidvalue',$name,$one));
//                 if( in_array($one,$tmp) ) throw new Exception($mod->Lang('error_duplicate',$name));
//                 $tmp[] = $one;
//             }
//             return $tmp;
//         };

//         $wpkeys  = $clean_array(array_keys($data),$this->Lang('col_wpkey'));
//         $news_flds = $clean_array(array_values($data),$this->Lang('col_fldname'));
//     }

//     // save options
//     cms_userprefs::set('ImporterExporter_wpconfig',serialize($wpconfig));
//     cms_userprefs::set('ImporterExporter_options',serialize($options));
//     $this->Redirect($id,'admin_import_main',$returnid);
//   }
// }
// catch( Exception $e ) {
//   echo $this->ShowErrors($e->Getmessage());
// }

// // get Backend Users
// $UserOps = UserOperations::get_instance();
// $backend_users = $UserOps->LoadUsers();
// $default_authors = [];
// foreach ($backend_users as $user) {
//     $default_authors[$user->id] = $user->firstname.' '.$user->lastname.' ('.$user->email.')';
// }
// // get Categories
// $news_mod = cms_utils::get_module('News');
// $default_categories = array_flip( news_ops::get_category_list() );

// // get Custom Fields
// $all_field_defs = ['0'=>'--- do not import ---'];
// $fields = news_ops::get_fielddefs();
// foreach ($fields as $fielddef) {
//     $all_field_defs[$fielddef['id']] = $fielddef['name'];
// }

// rewrite the following to use latest format for smarty
// $smarty->assign('wp',$wpconfig);



// $smarty->assign('formstart',$this->CGCreateFormStart($id,'defaultadmin',$returnid));
// $smarty->assign('formend',$this->CreateFormEnd());
// $smarty->assign('wp',$wpconfig);
// $smarty->assign('opts',$options);
// $smarty->assign('this_path', $config['root_path']);
// $smarty->assign('default_authors', $default_authors);
// $smarty->assign('default_categories', $default_categories);
// $smarty->assign('all_field_defs', $all_field_defs);

// echo $this->ProcessTemplate('defaultadmin.tpl');



