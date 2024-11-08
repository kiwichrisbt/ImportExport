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

function progress_reset()
{
    echo '<script type="text/javascript">progress_reset()</script>';
    flush();
}

function progress_total($total)
{
    $total = (int)$total;
    echo '<script type="text/javascript">progress_total('.$total.')</script>';
    flush();
}

function progress($steps = 1)
{
    $steps = (int)$steps;
    if( $steps < 1 ) return;
    echo '<script type="text/javascript">progress('.$steps.')</script>';
    flush();
}

function error($msg)
{
    $msg = trim($msg);
    if( !$msg ) return;
    echo '<script type="text/javascript">error(\''.$msg.'\')</script>';
    flush();
}

function status($msg)
{
    $msg = trim($msg);
    if( !$msg ) return;
    echo '<script type="text/javascript">status(\''.$msg.'\')</script>';
    flush();
}

function statistic($key,$val)
{
    $key = trim($key);
    $val = trim($val);
    if( !$key ) return;
    if( !$val ) return;
    echo '<script type="text/javascript">'."stat('$key','$val');".'</script>';
    flush();
}

function nl2p($string)
{
    $paragraphs = '';

    foreach (explode("\n", $string) as $line) {
        if (trim($line)) $paragraphs .= '<p>' . $line . '</p>';
    }

    return $paragraphs;
}

try {
  $news_mod = cms_utils::get_module('News');
  if( !$news_mod ) throw new Exception('Could not get instance of News module');

  $handlers = ob_list_handlers();
  for ($cnt = 0; $cnt < sizeof($handlers); $cnt++) { echo ob_end_clean(); }
  echo $this->ProcessTemplate('admin_import.tpl'); flush();

  $wpconfig = unserialize(cms_userprefs::get('ImporterExporter_wpconfig'));
  $options = unserialize(cms_userprefs::get('ImporterExporter_options'));

  $user_count = 0;
  $user_errors = 0;
  $image_errors = 0;
  $image_count = 0;
  $article_count = 0;
  $article_errors = 0;
  $wp_cats = null;
  $wp_term_taxonomy = null;
  $news_cats = null;

  // todo: remember to delete these options when done. (particularly the config)
  $options['image_dest'] = '/'.trim($options['image_dest'],'/').'/';
  $tmp = explode("\n",$options['image_paths']);
  $image_paths = array();
  $wp_image_dirs = [];
  foreach( $tmp as $line ) {
    $line = trim($line);
    if( !$line ) continue;
    $image_paths[] = $line;
    $dir_part = explode( $wpconfig['root_url'],$options['image_paths'] );
    if (!empty($dir_part[1])) {
        $wp_image_dirs[] = $wpconfig['root_path'].$dir_part[1];
    }
  }

  // get feu
//   $feu = cms_utils::get_module('FrontEndUsers');
//   if( !is_object($feu) ) throw new Exception('Could not get the FrontEndUsers module instance');
//   $username_is_email = (int)$feu->CGGetPreference('username_is_email',0);

  // clear News stuff
  if( $options['clear_news_posts'] ) {
      $str = 'TRUNCATE TABLE '.cms_db_prefix().'%s';
      $tmp = array();
    //   $tmp[] = sprintf($str,'module_news_categories');   // see below - optional!
      $tmp[] = sprintf($str,'module_news_fieldvals');
      $tmp[] = sprintf($str,'module_news');
      foreach( $tmp as $sql ) {
          $dbr = $db->Execute($sql);
      }
      $db->DropSequence( cms_db_prefix()."module_news_seq" );
      $db->CreateSequence(cms_db_prefix()."module_news_seq");
      status('Cleared News Posts');

      if( $options['clear_news_fielddefs'] ) {
          $db->Execute('TRUNCATE TABLE '.cms_db_prefix().'module_news_fielddefs');
          status('Cleared News field definitions');
      }
      if( $options['clear_news_categories'] ) {
          $db->Execute('TRUNCATE TABLE '.cms_db_prefix().'module_news_categories');
          status('Cleared News categories');
      }
  }

    // connect to wordpress
    $wpdb = ImporterExporter_utils::get_wp_db($wpconfig);

    // get the users from wp, we need this for username matching.
    $query = 'SELECT * FROM '.ImporterExporter_utils::wp_db_prefix().'users';
    $wp_users = $wpdb->GetArray($query);
    if( !is_array($wp_users) || count($wp_users) == 0 ) {
        throw new Exception('Could not find any users in the WordPress database');
    }
    $wp_usernames = array();
    foreach( $wp_users as $wp_user ) {
        $username = $wp_user['user_email'];
        // if( !$username || !$username_is_email ) {
        if ( !$username ) {
            $username = $wp_user['user_login'];
        }
        $wp_usernames[$wp_user['ID']] = $username;    // email
    }


    // use WP Post authors if they exist as Backend Users, otherwise use $options['default_author']
    // get a map of the wp user id => backend User id's
    $UserOps = UserOperations::get_instance();
    $backend_users = $UserOps->LoadUsers();
    $backend_users_list = [];
    $wp_backend_user_map = []; 
    foreach ($backend_users as $user) {
        $backend_users_list[$user->email] = $user->id;
    }
    foreach ($wp_usernames as $wp_id => $wp_user_email) {
        if ( isset($backend_users_list[$wp_user_email]) ) {
            $wp_backend_user_map[$wp_id] = $backend_users_list[$wp_user_email]; // backend user id
        }
    }

// not working - so just temporiarily use a default category
//   if( $options['import_categories'] ) {
//       // get wordpress categories (name, id, parent_id)
//       $query = "SELECT T.name,T.term_id,O.term_taxonomy_id,O.count,O.parent FROM ".ImporterExporter_utils::wp_db_prefix()."terms T
//                 LEFT JOIN ".ImporterExporter_utils::wp_db_prefix()."term_taxonomy O ON T.term_id = O.term_id WHERE O.taxonomy = 'category' ORDER BY O.parent ASC, T.name ASC";
//       $wp_cats = $wpdb->GetArray($query);
//       if( $wp_cats ) {
//           $wp_term_taxonomy = cge_array::extract_field($wp_cats,'term_taxonomy_id');
//           $wp_cats = ImporterExporter_utils::wpcats_to_tree($wp_cats);
//       }

//       // get news [cgblog] categories
//       $news_cats = news_ops::get_category_list();
//       // $cgblog_cats = cgblog_ops::get_category_list(FALSE,null,TRUE);
//       $news_cats = ImporterExporter_utils::news_cats_to_tree($news_cats);

//       // this makes sure that our News category tree is compatible with the WP category tree.
//       $_convert_wp_cats = function($wp_tree,&$news_tree,$depth = 0) use (&$_convert_wp_cats) {
//           if( !is_array($wp_tree) ) $wp_tree = array($wp_tree);
//           foreach( $wp_tree as $wp_node ) {
//               if( $wp_node->get_tag('term_id') > 0 ) {
//                   $wp_name_path = $wp_node->get_path('name');
//                   $wp_id_path = $wp_node->get_path('term_id');
//                   if( $wp_name_path && $wp_id_path ) {
//                       if( !ImporterExporter_tree::find_by_path($news_tree,'name',$wp_name_path) ) {
//                           ImporterExporter_utils::create_path($news_tree,$wp_name_path,$wp_id_path);
//                       }
//                   }
//               }
//               if( $wp_node->has_children() ) {
//                   $_convert_wp_cats($wp_node->get_children(),$news_tree,$depth+1);
//               }
//           }
//       };
// // broke so skip this
// //      $_convert_wp_cats($wp_cats,$news_cats);

//       // Walk through the News [CGBlog] category tree.... anything that doesn't have an 'id'
//       // gets created in News [CGBlog]
//       $_create_news_cats = function(&$news_node,$parent_id = -1) use (&$_create_news_cats) {
//           $news_id = (int)$news_node->get_tag('id');
//           $news_name = trim($news_node->get_tag('name'));
//           if( $news_name != '' ) {
//               if( $news_id < 1 ) {
//                   // gotta create this category
//                   // be sure to set news_id
//                   $news_id = news_ops::add_category($news_name,$parent_id);
//                   $news_node->set_tag('id',$news_id);
//               }
//           }

//           if( $news_node->has_children() ) {
//               $children = $news_node->get_children();
//               foreach( $children as $child ) {
//                   $_create_news_cats($child,$news_id);
//               }
//           }
//       };
// // same - broke so skip this
//     //   $_create_news_cats($news_cats);

//       // At this point, the news_cats should have a complete mirror of the wp_tree
//       // but may have extra nodes (pre-existing news categories)
//       // nodes must have a 'wp_term_id' tag, and an 'id' tag
//       // nodes without a wp_term_id tag were pre-existing

//   } // import categories

  if( $options['import_posts'] ) {
      $query = 'SELECT COUNT(ID) FROM '.ImporterExporter_utils::wp_db_prefix().'posts WHERE post_type = ? AND post_status = ? ORDER BY post_date DESC';
      $nmatches = $wpdb->GetOne($query,array('post','publish'));
      if( $nmatches > 0 ) {

          $offset = 0;
          $batchsize = 50;
          $query = 'SELECT * FROM '.ImporterExporter_utils::wp_db_prefix().'posts WHERE post_type = ? AND post_status = ? ORDER BY post_date DESC';
          $dflt_news_rec = array('news_id'=>'','news_category_id'=>$options['default_category'], 'news_title'=>'','news_data'=>'','news_date'=>'','summary'=>'',
            'status'=>'published', 'start_time'=>null, 'end_time'=>null, 'create_date'=>null, 'modified_date'=>null,
            'author_id'=>$options['default_author'], 'news_extra'=>'', 'news_url'=>'');

          // the main query for inserting a News [cgblog] record
          $iquery = 'INSERT INTO '.cms_db_prefix().'module_news (news_id, news_category_id, news_title,     
                     news_data, news_date, summary, status, start_time, end_time, create_date, 
                     modified_date, author_id, news_extra, news_url) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';

          // base query for retrieving selected metadata for specified posts.
          $field_read_query = 'SELECT * FROM '.ImporterExporter_utils::wp_db_prefix().'postmeta WHERE post_id IN SUB1 AND meta_key in SUB2 ORDER BY post_id,meta_key';

          // query for inserting a custom field value
          $meta_insquery = 'INSERT INTO '.cms_db_prefix().'module_news_fieldvals (news_id,fielddef_id,value,create_date,modified_date) VALUES (?,?,?,NOW(),NOW())';

          // create fielddefinitions in News [CGBlog]?
          $meta_map = array();
          if( $options['enable_postmeta_mapping'] && isset($options['postmeta_mapping']) && count($options['postmeta_mapping']) ) {
              // create new field definitions
              $iorder = (int) $db->GetOne('SELECT MAX(item_order) FROM '.cms_db_prefix().'module_news_fielddefs');
              $iorder++;
              $if_chkquery = 'SELECT id FROM '.cms_db_prefix().'module_news_fielddefs WHERE name = ?';
              $if_insquery = "INSERT INTO ".cms_db_prefix().'module_news_fielddefs (name,type,create_date,modified_date,item_order,public,attrs) VALUES (?,?,NOW(),NOW(),?,1,?)';

              progress_reset();
              progress_total($nmatches);

              foreach( $options['postmeta_mapping'] as $wpkey => $fldname ) {
                  // check if a field by this name exists
                  $tmp = $db->GetOne($if_chkquery,array($fldname));
                  if ( $tmp < 1) {
                      // insert the field
                      $attrs = array();
                      $attrs['size'] = 40;
                      $attrs['max_length'] = 255;
                      $attrs['file_exts'] = 'pdf,txt';
                      $attrs['image_exts'] = 'png,gif,bmp,jpg,jpeg';
                      $attrs['textarea_wysiwyg'] = 0;
                      $db->Execute($if_insquery,array($fldname,'textbox',$iorder++,serialize($attrs)));
                      status($this->Lang('created_news_field',$fldname));
                  }
                  else {
                      error($this->Lang('news_field_exists',$fldname));
                  }
                  progress();
              }

                // get all field definitions
                $news_fields = [];
                $news_fielddefs = news_ops::get_fielddefs(TRUE);
                foreach ($news_fielddefs as $fielddef) {
                    $news_fields[ $fielddef['name'] ] = $fielddef;
                }

              // create a map of wp_meta keys to news [cgblog] field ids
              foreach( $options['postmeta_mapping'] as $wpkey => $fldname ) {
                  if( isset($news_fields[$fldname]) )  $meta_map[$wpkey] = $news_fields[$fldname]['id'];
              }
          } //  end creating field definitions

          progress_reset();
          progress_total($nmatches);

          while( $offset < $nmatches ) {
              set_time_limit(120);
              $wp_dbr = $wpdb->SelectLimit($query,$batchsize,$offset,array('post','publish'));

              // get post id list
              $post_ids = array();
              while( !$wp_dbr->EOF() ) {
                  $post_ids[] = $wp_dbr->fields['ID'];
                  $wp_dbr->MoveNext();
              }
              $wp_dbr->MoveFirst();

                // read postmeta
                if (empty($meta_map)) $meta_map['none'] = '';   // CT Hack
                $fr_query = str_replace('SUB1','('.implode(',',$post_ids).')',$field_read_query);
                $fr_query = str_replace('SUB2','('.cge_array::implode_quoted(array_keys($meta_map)).')',$fr_query);
                $tmp_metadata = $wpdb->GetArray($fr_query);
                $post_metadata = array();
                if( is_array($tmp_metadata) && count($tmp_metadata) ) {
                    foreach( $tmp_metadata as $row ) {
                        if( !isset($post_metadata[$row['post_id']]) ) $post_metadata[$row['post_id']] = array();
                        $post_metadata[$row['post_id']][$row['meta_key']] = $row['meta_value'];
                    }
                }

                // get all WP Post thumbnails if required
                $wp_thumbnails = [];
                if ( !empty( $options['import_thumbnails'] ) ) {
                    $thumb_read_query = 'SELECT TID.post_id, THUMB.meta_value FROM '.ImporterExporter_utils::wp_db_prefix().'postmeta THUMB
                        JOIN '.ImporterExporter_utils::wp_db_prefix().'postmeta TID
                            ON THUMB.post_id = TID.meta_value
                        WHERE TID.post_id IN (SUB1) AND TID.meta_key="_thumbnail_id" AND THUMB.meta_key="_wp_attached_file"';
                    $thumb_query = str_replace('SUB1',implode(',',$post_ids),$thumb_read_query);
                    $wp_thumbnails = $wpdb->GetAssoc($thumb_query); // GetAssoc
                }


              // do the real work
              while( !$wp_dbr->EOF() ) {
                  $rec = $wp_dbr->fields;

                  if( $options['import_images'] && count($image_paths) ) {
                      // get images
                      $images = array();
                      if(preg_match_all('/src=(\'|")(.*?)(\'|")/',$rec['post_content'],$matches) ) {
                          if( isset($matches[2]) && count($matches[2]) ) {
                              foreach( $matches[2] as $match ) {
                                  $fnd = null;
                                  foreach( $image_paths as $one ) {
                                      if( startswith($match,$one) ) {
                                          $fnd = $one;
                                          break;
                                      }
                                  }
                                  if( $fnd ) {
                                      // found image path for src
                                      $short = substr($match,strlen($fnd));
                                      $rel = ltrim($short,'/');
                                      $fn = basename($rel);
                                      $dn = dirname($rel);
                                      $dest = $config['uploads_path'].$options['image_dest'].$rel;
                                      $dest_url = $config['uploads_url'].$options['image_dest'].$rel;
                                      $images[$match] = array('file'=>$dest,'url'=>$dest_url);
                                  }
                              }
                          }
                      }

                      if( count($images) ) {
                          foreach( $images as $src_url => $data ) {
                              if( !is_readable($data['file']) ) {
                                  $filedata = @file_get_contents($src_url);
                                  if( $filedata ) {
                                      $dir = dirname($data['file']);
                                      $res = @mkdir($dir,0777,TRuE);
                                      status("copied $src_url to {$data['file']}");
                                      file_put_contents($data['file'],$filedata);
                                      $image_count++;
                                  }
                                  else {
                                      $image_errors++;
                                      error("$src_url does not exist");
                                  }
                              } // is_readable.
                              else {
                                  $rec['post_content'] = str_replace($src_url,$data['url'],$rec['post_content']);
                              }
                          }
                      }
                  } // if import images

                    // create a record.
                    $news_rec = $dflt_news_rec;
                    $news_rec['news_title'] = $rec['post_title'];
                    $news_rec['news_date'] = $rec['post_date'];
                    $news_rec['news_data'] = nl2p($rec['post_content']);
                    $news_rec['summary'] = $rec['post_excerpt'];
                    // $news_rec['start_time'] = $rec['post_date'];
                    $news_rec['modified_date'] = $rec['post_modified'];
                    $news_rec['create_date'] = $rec['post_modified'];
                    // create a new sequence
                    $news_rec['news_id'] = $db->GenID(cms_db_prefix().'module_news_seq');
                    // see if wp author exists as Backend User - or leave as default 
                    if ( isset($wp_backend_user_map[ $rec['post_author'] ]) ) {
                        $news_rec['author_id'] = $wp_backend_user_map[ $rec['post_author'] ];
                    }
                    $news_rec['news_url'] = ImporterExporter_utils::get_new_url($news_rec, $rec);

                    // insert the thing
                    $dbr = $db->Execute($iquery,$news_rec);
                    if ( !$dbr ) {
                        $article_errors++;

                    } else {
                        $article_count++;
                        if ($news_rec['news_url']!= '') {
                            news_admin_ops::register_static_route($news_rec['news_url'], $news_rec['news_id']);
                        }
                    }

// just using default category for import - a hack for now
                //   // get the insert id.
                //   if( $options['import_categories'] ) {
                //       $news_article_id = $db->Insert_ID();

                //       // get categories for this article.
                //       $cquery = 'SELECT DISTINCT term_taxonomy_id FROM '.ImporterExporter_utils::wp_db_prefix().'term_relationships WHERE term_taxonomy_id IN ('.implode(',',$wp_term_taxonomy).')
                //                  AND object_id = ?';
                //       $article_taxonomy_ids = $wpdb->GetCol($cquery,array($rec['ID']));

                //       // insert categorie(s)
                //       if( is_array($article_taxonomy_ids) && count($article_taxonomy_ids) ) {
                //           // the term_id is our key into the cgblog_cats tree, but wordpress in its infinite wisdom
                //           // has a layer of indirection there.   so we need to get the term_id given the taxonomy_id
                //           // and get the cgblog category id, given the wordpress term_id
                //           $ciquery = 'INSERT INTO '.cms_db_prefix().'module_news_categories (news_id,category_id) VALUES (?,?)';
                //           foreach( $article_taxonomy_ids as $article_taxonomy_id ) {
                //               $wp_cat_node = $wp_cats->find_by_tag('term_taxonomy_id',$article_taxonomy_id);
                //               if( is_object($wp_cat_node) ) {
                //                   $term_id = $wp_cat_node->get_tag('term_id');
                //                   debug_display('got term id '.$term_id.' for '.$article_taxonomy_id); flush();
                //                   if( $term_id > 0 ) {
                //                       $news_cat_node = $news_cats->find_by_tag('wp_term_id',$term_id);
                //                       if( is_object($news_cat_node) ) {
                //                           $news_cat_id = $news_cat_node->get_tag('id');
                //                           debug_display('got category id '.$news_cat_id.' for wp term '.$term_id); flush();
                //                           if( $news_cat_id > 0 ) $db->Execute($ciquery,array($news_article_id,$news_cat_id));
                //                       }
                //                   }
                //               }
                //           }
                //       }
                //       else {
                //           error('No categories for article '.$rec['ID']);
                //       }
                //   } // import categories

                  // insert metadata
                  if( $options['enable_postmeta_mapping'] && count($meta_map) ) {
                      if( isset($post_metadata[$rec['ID']]) ) {
                          foreach( $post_metadata[$rec['ID']] as $wpkey => $val ) {
                              if( isset($meta_map[$wpkey]) ) {
                                  $dbr = $db->Execute($meta_insquery,array($news_article_id,$meta_map[$wpkey],$val));
                                  if( !$dbr ) error($db->sql.' -- '.$db->ErrorMsg());
                              }
                          }
                      }
                      else {
                          error('No suitable meta tags for record '.$rec['ID']);
                      } // import medadata
                  }

                // set import_thumbnails field with WP Post thumbnails - if required
                if ( !empty($options['import_thumbnails']) && isset($wp_thumbnails[$rec['ID']]) ) {
                    $wp_thumbnail = $wp_thumbnails[$rec['ID']];
                    $dbr = $db->Execute($meta_insquery, [$news_rec['news_id'], $options['import_thumbnails'], $wp_thumbnail]);
                    if ( !$dbr ) error($db->sql.' -- '.$db->ErrorMsg());

                    // import image file    
                    $found_src = FALSE;
                    foreach( $wp_image_dirs as $wp_path ) {
                        if ( is_readable($wp_path.DIRECTORY_SEPARATOR.$wp_thumbnail) ) {
                            $found_src = $wp_path.DIRECTORY_SEPARATOR.$wp_thumbnail;
                            break;
                        }
                    }
                    if ( $found_src ) {  // found image path for src
                        // $dest = $config['uploads_path'].$options['image_dest'].$wp_thumbnail;
                        $dest = $config['uploads_path'].DIRECTORY_SEPARATOR.'news'.DIRECTORY_SEPARATOR.
                            'id'.$news_rec['news_id'].DIRECTORY_SEPARATOR.$wp_thumbnail;
                        $filedata = @file_get_contents($found_src);
                        if ( $filedata ) {
                            $dir = dirname($dest);
                            $res = @mkdir($dir, 0777, TRUE);
                            status("copied $found_src to {$dest}");
                            file_put_contents($dest, $filedata);
                        } else {
                            $image_errors++;
                            error("$src_url does not exist");
                        }                        
                    }
                }

                  // on to the next one.
                  progress();
                  $wp_dbr->MoveNext();
              } // one batch

              $offset += $batchsize;
          } // done batches

          statistic($this->Lang('user_count'),$user_count);
          statistic($this->Lang('user_errors'),$user_errors);
          statistic($this->Lang('article_count'),$article_count);
          statistic($this->Lang('article_errors'),$article_errors);
          statistic($this->Lang('image_count'),$image_count);
          statistic($this->Lang('image_errors'),$image_errors);
      }
  }
}
catch( Exception $e ) {
  $this->SetError($e->GetMessage());
  $this->Redirect($id,'defaultadmin',$returnid);
}

exit;
#
# EOF
#
?>