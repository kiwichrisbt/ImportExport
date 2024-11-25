<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
namespace ImportExport;


abstract class ImportExportBase 
{
    const LANG_LABEL_PREFIX = 'label_';
    const INPUT_TYPES = ['text', 'textarea', 'select', 'checkbox', 'file_xml', 'submit', 'cancel'];
    const INPUT_TYPES_NOT_SAVED = ['submit', 'cancel'];  // 'file_xml',
    const FIELDNAME_PREFIX = 'm1_';     // prefix for the form fieldname - as it's an admin form
    const CACHE_PREFIX = 'ImportExport_';    // prefix for the cache key
    const AJAX_PROCESSING_DETAILS = 'ImportExport_ajax_processing_details'; // preference key for ajax processing details - same as module class
    const REMOVE_LEADING_DIRS = ['wp-content', 'uploads'];
    const FILE_GET_CONTENTS_TIMEOUT = 5;  // seconds

    public $mod;
    public $type = '';      // type of import/export
    public $type_name = ''; // display name of the type of import/export
    public $template_name = null;  // set by child class
    public $step = 1;       // step in the import/export process: 1 is usually configuration
    public $steps = [       // should be updated by the child class, e.g.
        // 1 => 'step_1_select',
        // 2 => 'step_2_config',
        // 3 => 'step_3_complete',
    ];
    public $config_options = [];            // array of config_option's - indexed by fieldname
    public $source_name = '';               // name of the source system - title for display
    public $source_fields = [];             // array of source field aliases
    public $source_samples = [];            // alias => sample data from source field
    public $destination_name = '';          // name of the destination system - title for display
    public $field_map = [];                 // array of destination field => field_map_item's 
    public $default_field_mappings = [];    // source field => destination field
    public $content_update_types = [];      // array of content types, to be updated after import
    public $field_map_values = [];          // array of field_map_item values
    public $batch_size = 10;                // default - can be updated by the child class
    public $progress = null;                // progress bar percentage - also saved in preferences
    public $ajax_key = null;                // key for ajax processing - unset after use
    public $ajax_status = null;             // status: started, ... , complete
    public $ajax_position = null;           // position in the import/export process
    public $ajax_feedback = [];             // feedback for the ajax processing - 'class' => 'message'
    public $uploads_location = null;        // location for uploaded files - subdir of uploads
    public $file_exists_count = 0;          // count of files that already exist in the uploads directory
    public $file_error_count = 0;           // count of files that had errors saving
    public $file_saved_count = 0;           // count of files that were saved
    public $retry = null;                   // if set, retry the processing, e.g. attachments
    public $messageManager = null;          // MessageManager instance (singleton)
    public $messages = [];                  // array of messages for the import/export
    public $errors = [];                    // array of errors for the import/export



    public function __construct() 
    {
        $this->mod = \cms_utils::get_module('ImportExport');
        $this->messageManager = MessageManager::getInstance();
        $this->set_progress(null);     // should be null until import starts
        $this->set_uploads_location(''); // can be updated by the child class
    }


    /**
     *  This function should be implemented by the child class
     *      - with calls to add_config_option() to add configuration options
     */
    abstract public function create_config_options();


    /**
     *  This function should be implemented by the child class
     *      - with calls to ...
     */
    abstract public function process($params=[]);


    /**
     *  This function can optionally be implemented by the child class 
     *      - e.g. for ajax triggering of processing
     *  @param object $saved_details - the saved details retrieved from the previous ajax processing
     *                              - child class will handle the saved extra_details
     */
    public function ajax_process($saved_details) {}


    /**
     *  get the config options from the submitted form params and update the config_options array
        *  @param array $params - the submitted form parameters
     */
    protected function get_config_values($params)
    {
        $form_step = $this->step - 1;   // the form step is always 1 less than the current step
        foreach ($this->config_options as &$option) {
            if ( $option->step!=$form_step ) continue;  // only process options from the current step
            if (!is_object($option)) {
                $this->messageManager->addError("Invalid configuration option detected.");
                continue;
            }
            $fieldname = $option->fieldname;
            switch ($option->inputtype) {
                case 'cancel':
                case 'submit':
                    break;

                case 'checkbox':
                    if (isset($params[$fieldname])) {
                        $option->value = $params[$fieldname];
                    }
                    break;

                case 'file_xml':
                    $fullfieldname = self::FIELDNAME_PREFIX.$fieldname;
                    if (isset($_FILES[$fullfieldname]) && $_FILES[$fullfieldname]['error']==0) {
                        $filepathname = $this->handle_file_upload($fullfieldname, ['xml']);                       
                    } else {
                        $this->messageManager->addError( $this->mod->Lang('error_upload', $fieldname) );
                    }
                    if ( !empty($filepathname) ) $option->value = $filepathname;
                    break;

                case 'field_map':
                    if (empty($params['field_map_item'])) break;
                    foreach ($params['field_map_item'] as $dest_field => $source_field) {
                        if ( isset($this->field_map[$dest_field]) ) {
                            $this->field_map[$dest_field]->source_field = $source_field;
                        }
                    }
                    break;

                default:    // text, textarea, select
                    if (isset($params[$fieldname])) {
                        $option->value = trim($params[$fieldname]);
                    }
                    break;  
            }
        }
    }


    /**
     *  handle the file upload, including checking the file type, move into tmp/cache
        *  @param string $fieldname - the name of the field
        *  @param string $tmpname - the temporary name of the uploaded file
        *  @param array $filetypes - the allowed file types
        *  @return string $filepathname - full path & filename if uploaded successfully
     */
    protected function handle_file_upload($fieldname, $allowedfileExtensions=['xml'])
    {
        $fileName = $_FILES[$fieldname]['name'];
        $tmpname = $_FILES[$fieldname]['tmp_name'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));
        if (!in_array($fileExtension, $allowedfileExtensions)) {
            $this->messageManager->addError( $this->mod->Lang('error_invalidfiletype',implode(', ', $allowedfileExtensions)) );
            return '';
        }
        $upload_dir = CMS_ROOT_PATH.'/tmp/cache/';
        $upload_file = $upload_dir.self::CACHE_PREFIX.ltrim($fieldname,'m1_').'.'.$fileExtension;
        if (move_uploaded_file($tmpname, $upload_file)) {
            $this->messageManager->addMessage( $this->mod->Lang('message_file_uploaded', $fileName) );
        } else {
            $this->messageManager->addError( $this->mod->Lang('error_upload', $fileName) );
            return '';
        }
        return $upload_file;
    }


    /**
     *  save the config options + field_map to the module preferences
     */
    protected function save_config()
    {
        $config = [];
        foreach ($this->config_options as $option) {
            if (!is_object($option) || in_array($option->inputtype, self::INPUT_TYPES_NOT_SAVED)) continue;
            if ($option->inputtype=='field_map') {
                // save the whole field_map array (might change to just saving the field mapings)
                $tmp_field_map_values = [];
                foreach ($this->field_map as $field) {
                    $tmp_field_map_values[$field->dest_field] = $field->source_field;
                }
                $config[$option->fieldname] = $tmp_field_map_values;
            } else {
                $config[$option->fieldname] = $option->value;
            }
        }
        $this->mod->SetPreference($this->type.'_config', json_encode($config));
    }



    /**
     *  load the config options + field_map from the module preferences
     */ 
    protected function load_config()
    {
        $config_json = $this->mod->GetPreference($this->type.'_config');
        if (!empty($config_json)) {
            $saved_config = json_decode($config_json, true);
            foreach ($this->config_options as $fieldname => &$option) {
                if ( is_object($option) && isset($saved_config[$fieldname]) ) {
                    if ($option->inputtype=='field_map') {
                        $this->field_map_values = $saved_config[$fieldname];
                        $this->get_field_map_values();  // if field_map exists - saved values or defaults

                    } else {
                        $option->value = $saved_config[$fieldname];
                    }
                }
            }
        }
    }


    /**
     *  load field map with the saved values or default field mappings - if field_map exists
     */
    protected function get_field_map_values()
    {
        if ( empty($this->field_map) ) return;

        if ( !empty($this->field_map_values) ) {
            // load the field map with the saved values
            foreach ($this->field_map as $dest_field => $field) {
                if ( isset($this->field_map_values[$dest_field]) ) {
                    $this->field_map[$dest_field]->source_field = $this->field_map_values[$dest_field];
                }
            }

        } elseif ( !empty($this->default_field_mappings) ) {
            // apply the default field mappings
            foreach ($this->default_field_mappings as $source_field => $dest_field) {
                if ( isset($this->field_map[$dest_field]) && empty($this->field_map[$dest_field]->source_field) ) {
                    $this->field_map[$dest_field]->source_field = $source_field;
                }
            }
        }
    }



    /**
     *  get the selected sources from the field map
        *  @return array of selected source fields
     */ 
    public function get_selected_sources()
    {
        $selected_sources = [];
        foreach ($this->field_map as $fieldname => $field) {
            if ( !empty($field->source_field) ) {
                $selected_sources[] = $field->source_field;
            }
        }

        return $selected_sources;
    }


    /**
     *  set the progress bar percentage
     */
    public function set_progress($count=null, $total=null)
    {
        if ( is_null($count) || empty($total) ) {
            $progress = null;   // progress bar not output
        } else {
            $progress = (int)($count / $total * 100);
        }
        $this->progress = $progress;
    }



    /**
     *  retrieve the details for ajax processing in progress
     *  this code in module class - as import/export type is not known
     *  @param string $key - required - the previously saved key for ajax processing
     *  @return object|boolean array of saved details, or false if key incorrect
     */
    public function get_ajax_processing_details($provided_key)
    {
        // call the module class method
        return $this->mod->get_ajax_processing_details($provided_key);
    }


    /**
     *  save the details for the ajax processing, key & type added to the details
     *  @param array $extra_details - extra details to save 
     *  sets $this->ajax_key - the key for the ajax processing
     *      e.g. ['status' => 'started', 'position' => 0]...
     */
    public function set_ajax_processing_details($extra_details)
    {   
        $ajax_details = (object)$extra_details;
        // set the important details by default
        $ajax_details->type = $this->type;
        $this->ajax_key = md5(uniqid(rand(), true));
        $ajax_details->ajax_key = $this->ajax_key;
        $ajax_details->ajax_status = $this->ajax_status;
        $ajax_details->ajax_position = $this->ajax_position;
        $ajax_details->file_exists_count = $this->file_exists_count;
        $ajax_details->file_error_count = $this->file_error_count;
        $ajax_details->file_saved_count = $this->file_saved_count;

        $this->mod->SetPreference(self::AJAX_PROCESSING_DETAILS, json_encode($ajax_details));
    }


    /**
     *  reset the details for the ajax processing
     */
    public function reset_ajax_processing_details()
    {
        $this->mod->SetPreference(self::AJAX_PROCESSING_DETAILS, null);
    }


    public function check_uploads_dest_dir($subdir)
    {
        $uploads_path = \CmsApp::get_instance()->GetConfig()['uploads_path'];
        $dest_dir = cms_join_path($uploads_path, $subdir);
        $Ok = ( is_writable( $dest_dir ) );
        if (!$Ok) {
            $this->messageManager->addError( ('Cannot access destination directory: '+ $dest_dir) );
        }
        return $Ok;
    }


    /**
     *  move a file to the uploads location
     *  @param string $remote_file_url - the remote file url
     *  @return string $local_relative_url - the local relative url
     */
    public function move_file_to_uploads_location($remote_file_url)
    {
        // check if file is already in the uploads directory
        $local_relative_url = null;
        $file_details = $this->get_file_details($remote_file_url);
        if (file_exists($file_details->local_path)) {
            // no need to do anything...
            $local_relative_url = $file_details->local_relative_url;
            $this->file_exists_count++;

        } else {
            // get file from remote url, with timeout
            $ctx = stream_context_create(['http' => ['timeout' => self::FILE_GET_CONTENTS_TIMEOUT]]);
            $file = file_get_contents($remote_file_url, false, $ctx);           

            if ($file === false) {
                $this->file_error_count++;  // but also output error message
                $this->messageManager->addError( 'Error getting file: '.$remote_file_url);
            } else {
                // save file to uploads directory, check if directory exists, or create it
                if (!file_exists($file_details->local_dir)) {
                    mkdir($file_details->local_dir, 0777, true);
                }
                // save file
                if (file_put_contents($file_details->local_path, $file)===false) {
                    $this->file_error_count++;  // but also output error message
                    $this->messageManager->addError( 'Error saving file: '.$file_details->local_path);
                } else {
                    $local_relative_url = $file_details->local_relative_url;
                    $this->file_saved_count++;
                }
            }
        }  
        return $local_relative_url;
    }


    public function move_files_to_uploads_location(&$items)
    {
        foreach ($items as &$item) {
            $remote_file_url = $item['wp_attachments'];
            $local_relative_url = $this->move_file_to_uploads_location($remote_file_url);
            if (!empty($local_relative_url)) {
                $item['local_relative_url'] = $local_relative_url;
            }
        }
    }


    /**
     *  set the uploads location for the files - try to create $subdir or just use uploads dir
     */ 
    public function set_uploads_location($subdir)
    {
        $uploads_path = \CmsApp::get_instance()->GetConfig()['uploads_path'];
        $dest_dir = empty($subdir) ? $uploads_path : cms_join_path($uploads_path, $subdir);
        $dest_dir = rtrim($dest_dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;  // add trailing '/

        if ( is_writable( $dest_dir ) ) {   // dir exists
            $this->uploads_location = $dest_dir;
            
        } elseif ( mkdir($dest_dir, 0777, true) ) {
            $this->uploads_location = $dest_dir;    // created dir

        } else {
            $this->messageManager->addError( 'Cannot access destination directory: '.$dest_dir );
            $this->uploads_location = $uploads_path;    // use default uploads dir
        }
    }


    /**
     *  get the local file name from the remote file name
     *  @param string $remote_file_url - the remote file name
     *  @return object $local_file - the local file with all useful details as member variables
     */
    public function get_file_details($remote_file_url)
    {
        $file_details = new \stdClass;
        $file_details->remote_file_url = $remote_file_url;
        
        $remote_url_parts = parse_url($remote_file_url);
        $file_details->remote_scheme = $remote_url_parts['scheme'];
        $file_details->remote_host = $remote_url_parts['host'];  // full domain
        $file_details->remote_path = $remote_url_parts['path'];  // includes leading '/' & filename
        $file_details->filename = basename($file_details->remote_path);

        // get local path & url - remove leading directories
        $tmp_path = trim($file_details->remote_path, DIRECTORY_SEPARATOR); // remove '/'s
        $tmp_path_parts = explode('/', $tmp_path);  // split into parts
        foreach (self::REMOVE_LEADING_DIRS as $remove_dir) {    // remove leading directories
            if ($tmp_path_parts[0]==$remove_dir) {
                array_shift($tmp_path_parts);
            }
        }
        $tmp_path = implode(DIRECTORY_SEPARATOR, $tmp_path_parts); // path + filename
        $file_details->local_path = $this->uploads_location.$tmp_path;
        // local url - relative to root
        $root_path = \CmsApp::get_instance()->GetConfig()['root_path'];
        $file_details->local_relative_url = str_replace($root_path, '', $file_details->local_path);
        $file_details->local_dir = dirname($file_details->local_path);

        return $file_details;    
    }
    

    /**
     *  test if fopen is enabled
     *  @return boolean $fopen_enabled - TRUE if fopen is enabled
     */
    public function fopen_enabled()
    {
        $allow_url_fopen = ini_get('allow_url_fopen');
        return (boolean)$allow_url_fopen;
    }


    // /**
    //  * NOT YET TESTED - This function is not yet implemented
    //  */
    // public static function get_new_url( $news_rec, $wp_rec )
    // {
    //     $news_url = '';
    //     $error = FALSE;
    //     $mod = \cms_utils::get_module('ImporterExporter');
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