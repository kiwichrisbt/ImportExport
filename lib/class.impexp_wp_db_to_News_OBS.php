<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------


class impexp_wp_db_to_News extends ImportExportBase 
{

	public function __construct() 
	{	
		parent::__construct();
    }

    //
    //    NOTE: This used to function - but currently disabled - could be resurected ???
    //


    // NOTE - would need re-writing to work 
    public function old_action_code ()
    {
    

        // config defaults
        $wpconfig = array();
        $wpconfig['root_path'] = '';
        $wpconfig['root_url'] = '';
        $wpconfig['db_host'] = 'localhost';
        $wpconfig['db_user'] = '';
        $wpconfig['db_pass'] = '';
        $wpconfig['db_name'] = '';
        $wpconfig['db_prefix'] = 'wp_';
        $tmp = cms_userprefs::get('ImporterExporter_wpconfig');
        if( $tmp ) $wpconfig = unserialize($tmp);

        $options['clear_news_posts'] = 1;
        $options['clear_news_fielddefs'] = 0;
        $options['clear_news_categories'] = 0;
        $options['import_categories'] = 1;
        $options['import_posts'] = 1;
        $options['import_images'] = 1;
        $options['image_paths'] = '';
        $options['image_dest'] = '';
        // $options['import_users'] = 1;
        $options['default_author'] = 0;
        $options['default_category'] = 0;
        $options['import_thumbnails'] = 0;
        $options['user_pw'] = 'changeme';
        $options['enable_postmeta_mapping'] = FALSE;
        $options['postmeta_mapping'] = array();
        $tmp = cms_userprefs::get('ImporterExporter_options');
        if( $tmp ) $options = unserialize($tmp);

        try {
        if( isset($params['wp_check']) ) {
            $wpconfig['root_path'] = trim(cge_utils::get_param($params,'root_path'));
            ImporterExporter_utils::check_wp_dir($wpconfig['root_path']);
            echo $this->ShowMessage($this->Lang('msg_wpfound'));
        }

        if( isset($params['wp_test']) ) {
            $wpconfig['root_path'] = trim(cge_utils::get_param($params,'root_path'));
            $wpconfig['root_url'] = trim(cge_utils::get_param($params,'root_url'));
            $wpconfig['db_host'] = trim(cge_utils::get_param($params,'db_host'));
            $wpconfig['db_user'] = trim(cge_utils::get_param($params,'db_user'));
            $wpconfig['db_pass'] = trim(cge_utils::get_param($params,'db_pass'));
            $wpconfig['db_name'] = trim(cge_utils::get_param($params,'db_name'));
            $wpconfig['db_prefix'] = trim(cge_utils::get_param($params,'db_prefix'));
            $wpdb = ImporterExporter_utils::get_wp_db($wpconfig);
            ImporterExporter_utils::check_wp_db($wpdb);

            $query = 'SELECT DISTINCT meta_key FROM '.$wpconfig['db_prefix'].'postmeta';
            $metakeys = $wpdb->GetCol($query);
            $out = array('none'=>$this->Lang('none'));
            foreach( $metakeys as $one ) {
                $out[$one] = $one;
            }
            $smarty->assign('wp_metakeys',$out);
            $smarty->assign('db_tested',1);
        }

        if( isset($params['wp_import']) ) {
            $wpconfig['root_path'] = trim(cge_utils::get_param($params,'root_path'));
            $wpconfig['root_url'] = trim(cge_utils::get_param($params,'root_url'));
            $wpconfig['db_host'] = trim(cge_utils::get_param($params,'db_host'));
            $wpconfig['db_user'] = trim(cge_utils::get_param($params,'db_user'));
            $wpconfig['db_pass'] = trim(cge_utils::get_param($params,'db_pass'));
            $wpconfig['db_name'] = trim(cge_utils::get_param($params,'db_name'));
            $wpconfig['db_prefix'] = trim(cge_utils::get_param($params,'db_prefix'));
            $wpdb = ImporterExporter_utils::get_wp_db($wpconfig);
            ImporterExporter_utils::check_wp_db($wpdb);

            // get options
            $options = array();
            $options['clear_news_posts'] = (int)cge_utils::get_param($params,'clear_news_posts');
            $options['clear_news_fielddefs'] = (int)cge_utils::get_param($params,'clear_news_fielddefs');
            $options['clear_news_categories'] = (int)cge_utils::get_param($params,'clear_news_categories');
            $options['import_categories'] = (int)cge_utils::get_param($params,'import_categories');
            $options['import_posts'] = (int)cge_utils::get_param($params,'import_posts');
            $options['import_images'] = (int)cge_utils::get_param($params,'import_images');
            // $options['import_users'] = (int)cge_utils::get_param($params,'import_users');
            $options['user_pw'] = trim(cge_utils::get_param($params,'user_pw'));
            $options['image_paths'] = trim(cge_utils::get_param($params,'image_paths'));
            $options['image_dest'] = trim(cge_utils::get_param($params,'image_dest'));
            $options['enable_postmeta_mapping'] = (int)cge_utils::get_param($params,'enable_postmeta_mapping');
            $options['default_author'] = (int)cge_utils::get_param($params,'default_author');
            $options['default_category'] = (int)cge_utils::get_param($params,'default_category');
            $options['import_thumbnails'] = (int)cge_utils::get_param($params,'import_thumbnails');

            // clean up the postmeta mappings
            if( $options['enable_postmeta_mapping'] && isset($params['postmeta']) ) {
                if( count($params['postmeta']['wpkey']) != count($params['postmeta']['fldname']) ) throw new Exception($this->Lang('error_invalid_postmeta'));
                $data = array();
                if( 1 ) {
                    $tmp1 = $params['postmeta']['wpkey'];
                    $tmp2 = $params['postmeta']['fldname'];
                    for( $i = 0; $i < count($tmp1); $i++ ) {
                        $data[$tmp1[$i]] = $tmp2[$i];
                    }
                }
                $options['postmeta_mapping'] = $data;

                $clean_array = function($data,$name){
                    $mod = cms_utils::get_module('ImporterExporter');
                    $tmp = array();
                    foreach( $data as $one ) {
                        $one = trim($one);
                        if( !$one || $one == -1 ) throw new Exception($mod->Lang('error_invalidvalue',$name,$one));
                        if( in_array($one,$tmp) ) throw new Exception($mod->Lang('error_duplicate',$name));
                        $tmp[] = $one;
                    }
                    return $tmp;
                };

                $wpkeys  = $clean_array(array_keys($data),$this->Lang('col_wpkey'));
                $news_flds = $clean_array(array_values($data),$this->Lang('col_fldname'));
            }

            // save options
            cms_userprefs::set('ImporterExporter_wpconfig',serialize($wpconfig));
            cms_userprefs::set('ImporterExporter_options',serialize($options));
            $this->Redirect($id,'admin_import_main',$returnid);
        }
        }
        catch( Exception $e ) {
        echo $this->ShowErrors($e->Getmessage());
        }

        // get Backend Users
        $UserOps = UserOperations::get_instance();
        $backend_users = $UserOps->LoadUsers();
        $default_authors = [];
        foreach ($backend_users as $user) {
            $default_authors[$user->id] = $user->firstname.' '.$user->lastname.' ('.$user->email.')';
        }
        // get Categories
        $news_mod = cms_utils::get_module('News');
        $default_categories = array_flip( news_ops::get_category_list() );

        // get Custom Fields
        $all_field_defs = ['0'=>'--- do not import ---'];
        $fields = news_ops::get_fielddefs();
        foreach ($fields as $fielddef) {
            $all_field_defs[$fielddef['id']] = $fielddef['name'];
        }



    }



}