<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# Module: ImporterExporter (c) 2014 by Robert Campbell
#    (calguy1000@cmsmadesimple.org)
#  An addon module for CMS Made Simple to allow shortening URLS.
#
#-------------------------------------------------------------------------
# CMS - CMS Made Simple is (c) 2005 by Ted Kulp (wishy@cmsmadesimple.org)
# Visit the CMSMS Homepage at: http://www.cmsmadesimple.org
#
#-------------------------------------------------------------------------
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# However, as a special exception to the GPL, this software is distributed
# as an addon module to CMS Made Simple.  You may not use this software
# in any Non GPL version of CMS Made simple, or in any version of CMS
# Made simple that does not indicate clearly and obviously in its admin
# section that the site was built with CMS Made simple.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
# Or read it online: http://www.gnu.org/licenses/licenses.html#GPL
#
#-------------------------------------------------------------------------
#END_LICENSE
if( !isset($gCms) ) exit;
if( !$this->VisibleToAdminUser() ) return;

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

$smarty->assign('formstart',$this->CGCreateFormStart($id,'defaultadmin',$returnid));
$smarty->assign('formend',$this->CreateFormEnd());
$smarty->assign('wp',$wpconfig);
$smarty->assign('opts',$options);
$smarty->assign('this_path', $config['root_path']);
$smarty->assign('default_authors', $default_authors);
$smarty->assign('default_categories', $default_categories);
$smarty->assign('all_field_defs', $all_field_defs);

echo $this->ProcessTemplate('defaultadmin.tpl');
#
# EOF
#
?>