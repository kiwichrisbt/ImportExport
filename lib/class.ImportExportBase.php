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
    const INPUT_TYPES = ['text', 'textarea', 'select', 'checkbox', 'file_xml', 'submit', 'cancel+submit'];
    const INPUT_TYPES_NOT_SAVED = ['submit', 'cancel+submit'];  // 'file_xml',
    const FIELDNAME_PREFIX = 'm1_';     // prefix for the form fieldname - as it's an admin form
    const CACHE_PREFIX = 'ImportExport_';    // prefix for the cache key

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
    public $field_map_values = [];          // array of field_map_item values

    public $messageManager = null;          // MessageManager instance (singleton)
    public $messages = [];                  // array of messages for the import/export
    public $errors = [];                    // array of errors for the import/export



    public function __construct() 
    {
        $this->mod = \cms_utils::get_module('ImportExport');
        $this->messageManager = MessageManager::getInstance();
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
                case 'cancel+submit':
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