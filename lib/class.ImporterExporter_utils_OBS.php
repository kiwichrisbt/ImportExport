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

//  
//  NOTE: this class is obsolete - it is not used in the current version of the module
//  it is retained for reference only
//



final class ImporterExporter_utils
{
  private static $wp_dbprefix;

  private function __construct() {}

  public static function check_wp_dir($dir)
  {
    if( !is_dir($dir) ) throw new Exception($dir.' is not a directory');
    if( !is_readable($dir.'/wp-config.php') ) throw new Exception($dir.' is not a wordpress directory');
    return true;
  }

  public static function &get_wp_db($wpconfig)
  {
      $conn = new \CMSMS\Database\ConnectionSpec;
      $conn->host = $wpconfig['db_host'];
      $conn->username = $wpconfig['db_user'];
      $conn->password = $wpconfig['db_pass'];
      $conn->dbname = $wpconfig['db_name'];
      $conn->prefix = $wpconfig['db_prefix'];
      $obj = \CMSMS\Database\Connection::Initialize( $conn );
      $obj->Execute('SET NAMES utf8'); // test

      self::$wp_dbprefix = $wpconfig['db_prefix'];
      return $obj;
  }

  private static function set_wp_dbprefix($str)
  {
    self::$wp_dbprefix = $str;
  }

  public static function wp_db_prefix()
  {
    return self::$wp_dbprefix;
  }

  public static function check_wp_db(&$db)
  {
    $query = 'SELECT COUNT(ID) FROM '.self::wp_db_prefix().'posts WHERE post_status = ?';
    $tmp = $db->GetOne($query,array('publish'));
    if( !$tmp ) throw new Exception('Could not find any published posts in the WordPerfect database');

    $query = 'SELECT COUNT(ID) FROM '.self::wp_db_prefix().'users';
    $tmp = $db->GetOne($query);
    if( !$tmp ) throw new Exception('Could not find any users in the WordPerfect database');
  }

  public static function wpcats_to_tree($in)
  {
      $root = new ImporterExporter_tree(array('term_id'=>0));
      foreach( $in as $row ) {
          $node = new ImporterExporter_tree($row);
          if( $row['parent'] > 0 ) {
              $parent = $root->find_by_tag('term_id',$row['parent']);
              $parent->add_node($node);
          }
          else {
              $root->add_node($node);
          }
      }
      return $root;
  }

  public static function news_cats_to_tree($in)
  {
      $root = new ImporterExporter_tree();
      if( is_array($in) && count($in) ) {
          foreach( $in as $row ) {
              $node = new ImporterExporter_tree($row);
              if( $row['parent_id'] > 0 ) {
                  $parent = $root->find_by_tag('id',$row['parent_id']);
                  $parent->add_node($node);
              }
              else {
                  $root->add_node($node);
              }
          }
      }
      return $root;
  }

  public static function create_path( ImporterExporter_tree &$root, $name_path, $id_path, $delimiter = '/^^/')
  {
      $name_tmp = explode($delimiter,$name_path);
      $id_tmp = explode($delimiter,$id_path);
      if( count($name_tmp) != count($id_tmp) ) throw new CmsException('Inconsistent depths betwen name and id paths');

      $start = $root;
      for( $i = 0; $i < count($name_tmp); $i++ ) {
          $name = $name_tmp[$i];
          $id = $id_tmp[$i];
          $node = $start->find_child_by_tag('name',$name);
          if( $node ) {
              $start = $node;
              continue;
          }

          // gotta create it.
          $tmp = array('name'=>$name,'wp_term_id'=>$id);
          $node = new ImporterExporter_tree($tmp);
          $start->add_node($node);
          $start = $node;
      }
  }


    // MOVED into ImportExportBase - not yet tested

    // public static function get_new_url( $news_rec, $wp_rec )
    // {
    //     $news_url = '';
    //     $error = FALSE;
    //     $mod = cms_utils::get_module('ImporterExporter');
    //     if ( isset($wp_rec['post_name']) ) {
    //         $news_url = $wp_rec['post_name'];
    //     } else {
    //         $news_url = munge_string_to_url($wp_rec['post_title'], false, true);
    //     }

    //     // check for starting or ending slashes
    //     if (startswith($news_url, '/') || endswith($news_url, '/'))
    //         $error = $mod->Lang('error_invalidurl');
    //     if ($error===FALSE) {
    //         // check for invalid chars.
    //         $translated = munge_string_to_url($news_url, false, true);
    //         if (strtolower($translated) != strtolower($news_url))
    //             $error = $mod->Lang('error_invalidurl');
    //     }

    //     if ($error===FALSE) {
    //         // make sure this url isn't taken.
    //         cms_route_manager::load_routes();
    //         $route = cms_route_manager::find_match($news_url, TRUE);

    //         if ($route) {
    //             $dflts = $route->get_defaults();
    //             if ($route['key1'] != $mod->GetName() || !isset($dflts['articleid']) || $dflts['articleid'] != $news_rec['news_id']) {
    //                 // we're adding an article, not editing... any matching route is bad.
    //                 $error = $mod->Lang('error_invalidurl');
    //             }
    //         }
    //     }

    //     if ($error===FALSE) {
    //         return $news_url;
    //     }

    //     return '';
    // }


}