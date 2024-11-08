<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
namespace ImportExport;

use CMSMSExt\reports\formatted_report_defn;
use \LISE;
use LISE\alias_generator;
use \LISE\api;


class lise_interface 
{

    const DATA_TYPES = [    // used to standardise data that is output - maybe move to ImportExportBase
        'string',           
        'boolean',
        'integer',
        'datetime',
        'comma_separated',
        'string_no_spaces',
        'array',
        'categories',           // special case for categories - array of category ids, or "-1" for none
        'categories_multi',     // categories - can have multiple categories
        'url',
        'owner',                // special case for owner - id (integer)
    ];
    const DEFAULT_ITEM_FIELDS = [   // field_alias => data_type
        'alias' => 'string_no_spaces',
        'title' => 'string',
        'url' => 'url',
        'active' => 'integer',
        'create_time' => 'datetime',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'owner' => 'owner',
    ];
    const LISE_FD_TYPES = [     // for custom fields - fd_type => DATA_TYPE (see above)
        'default' => 'string',   // use this as 
        'TextArea' => 'string',
        'Tags' => 'comma_separated',
        'Categories' => 'categories',
        'Categories_multi' => 'categories_multi',
    ];
	const LISE_MULTI_TYPES = [
        'CheckboxGroup',
        'MultiSelect',
        'JQueryMultiSelect'
    ];
		
    public $lise_mod = null;
    public $lise_instance = null;
    public $instance_name = '';   // the name of the LISE instance
    public $record_count = null;  // number of records in the LISE instance
    public $messageManager = null;
    public $source_base_urls = [];       // base urls for the LISE instance
    public $valid_date_min = null;  
    public $valid_date_max = null;
    public $category_list = [];  // list of categories => ids for the LISE instance
    public $new_categories_added = [];  // list of new categories added
    public $default_owner_id = null;  // default owner id for LISE items
    public $multi_categories_truncated = 0;  // count of items with multiple categories truncated
    public $import_count = 0;  // count of items imported



    /**
     *   @param string $instance_name optional - the name of the LISE instance  
     */
    public function __construct($instance_name=null) 
    {	
        $this->lise_mod = \cms_utils::get_module('LISE');
        $this->messageManager = MessageManager::getInstance();
        $this->valid_date_min = strtotime('2000-01-01');
        $this->valid_date_max = strtotime('+1 year');

        if (!empty($instance_name)) {   // optional - if instance not yet selected
            $this->instance_name = $instance_name;
            $this->lise_instance = $this->lise_mod->GetModuleInstance($instance_name);

            $params = [];
            $lise_items = $this->lise_instance->GetItemQuery($params);
            $result     = $lise_items->Execute(true);
            $this->record_count = $result->RecordCount();
            $this->get_category_list();
        }

    }



    /**
     *  get a list of LISE instances suitable for a select field
        *  @return array
     */
    public function get_lise_instance_list()
    {
        $imp_exp_mod = \cms_utils::get_module('ImportExport');
        $instances = [];
        $instance_names = ['' => $imp_exp_mod->Lang('label_wp_xml_to_LISE_lise_instance')];
        if ($this->lise_mod) {
            $instances = $this->lise_mod->ListModules();
        }
        foreach ($instances as $instance) {
            $instance_names[$instance->module_name] = $instance->module_name;
        }
        return $instance_names;
    }


    /**
     *  generates a field map from the LISE instance fields, excluding the ignore fields
        *  @return array of field_map_item's, indexed by destination field
     */
    public function generate_field_map()
    {
        $field_map = [];    // array of destination field => field_map_item's
        // add the default fields
        foreach (self::DEFAULT_ITEM_FIELDS as $field_alias => $data_type) {
            $field_map[$field_alias] = new field_map_item($field_alias, '', $data_type);
        }
        // add the custom fields
        $fielddefs = $this->lise_instance->GetFieldDefs();
        foreach($fielddefs as $fielddef) {
            $dest_data_type = isset(self::LISE_FD_TYPES[$fielddef->type]) ? self::LISE_FD_TYPES[$fielddef->type] : 'string';
            if ($dest_data_type=='categories') {
                $type = $fielddef->GetOptionValue('subtype', 'Dropdown');
                if ( in_array($type, self::LISE_MULTI_TYPES) ) {
                    $dest_data_type = 'categories_multi';
                }
            }
            $field_map[$fielddef->alias] = new field_map_item($fielddef->alias, '', $dest_data_type, $fielddef->required);
        }

        return $field_map;
    }


    /**
     *  import the given items into the LISE instance
     */
    public function import_items($items, $field_map, $source_base_urls)
    {
        $import_count = 0;
        $auto_active = empty($field_map['active']->source_field); // if no 'active' field, default to active
        $this->source_base_urls = empty($source_base_urls) ? [] : explode(',', $source_base_urls);
        foreach ($items as $item) {
            $lise_item = $this->lise_instance->InitiateItem();

            foreach ($field_map as $mapped_field) {    
                if ($mapped_field->dest_field!='create_time') {  // we have to set it manually later
                    $lise_item->{$mapped_field->dest_field} = $this->filter_data($mapped_field->dest_data_type, $item[$mapped_field->source_field]);
                }
            }
            if ($auto_active) $lise_item->active = '1';

            try {
                $this->lise_instance->SaveItem($lise_item);
                // check if create_time is mapped & set it manually
                if (!empty($field_map['create_time']->source_field) && !empty($item[$field_map['create_time']->source_field])) {
                    $create_time = $item[$field_map['create_time']->source_field];
                    $this->set_create_time($lise_item, $create_time);
                }
                $import_count++;  // if no error generated

            } catch (\Exception $e) {
                $this->messageManager->addError( $e->getMessage() );
            }
        }

        // $this->messageManager->addLangMessage( 'message_imported_items', $import_count, $this->instance_name );
        $this->import_count += $import_count;
        $this->record_count += $import_count;
        // if (!empty($this->new_categories_added)) {
        //     $this->messageManager->addLangMessage( 'message_new_categories_added', count($this->new_categories_added) ,implode(', ', $this->new_categories_added) );
        // }
        if ($this->multi_categories_truncated) {
            $this->messageManager->addLangError( 'error_multiple_categories', (string)$this->multi_categories_truncated);
        }

    }



    /**
     *  delete all items from the LISE instance
     */
    public function delete_all_items()
    {
        $db = cmsms()->GetDb();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'module_'.strtolower($this->instance_name).'_item';
        $result = $db->Execute($query);
        $record_count = $result->RecordCount();
        while($result && $row = $result->FetchRow()) 
        {
            $this->lise_instance->DeleteItemById($row['item_id']);
        }  
        $imp_exp_mod = \cms_utils::get_module('ImportExport');
        $this->messageManager->addLangMessage( 'message_lise_deleted_all_items',
            $record_count, $this->instance_name );
        $this->record_count = 0;
    }


    /**
     *  filter the data to the correct type
     *  @param string $data_type - one of the DATA_TYPES
     *  @param mixed $data - the data to filter
     *  @return mixed - the filtered data
     */
    public function filter_data($data_type, $data)
    {
        if (empty($data)) $data = '';    // default to string
        
        $filtered_data = '';    // default to string
        switch ($data_type) {
            case 'boolean':
                $filtered_data = (bool)$data;
                break;

            case 'integer':
                $filtered_data = (int)$data;
                break;

            case 'datetime':
                $filtered_data = null;
                $tmp_data = is_numeric($data) ? $data : strtotime($data);
                if ($tmp_data>=$this->valid_date_min && $tmp_data<=$this->valid_date_max) {
                    $filtered_data = date('Y-m-d H:i:s', $tmp_data);  // LISE needs formatted date
                }
                break;

            case 'comma_separated':
                $filtered_data = is_array($data) ? implode(',', $data) :  (string)$data;
                break;

            case 'string_no_spaces':
                $filtered_data = str_replace(' ', '', (string)$data);
                break;

            case 'array':
                $filtered_data = is_array($data) ? $data : explode(',', $data);
                break;

            case 'categories':
            case 'categories_multi':
                if (empty($data)) {
                    $filtered_data = -1;
                    break;
                } 

                $cat_array = is_array($data) ? $data : implode(',', $data);
                $cat_ids = [];
                foreach ($cat_array as $cat_alias => $cat_name) {
                    if (!isset($this->category_list[$cat_alias])) {
                        $this->add_category($cat_alias, $cat_name);
                    }
                    $cat_ids[] = $this->category_list[$cat_alias];
                }
                
                if ($data_type=='categories') {
                    $filtered_data = $cat_ids[0];   // just get first $cat_id    
                    if (count($cat_ids)>1) $this->multi_categories_truncated++;
                } else {
                    $filtered_data = implode(',', $cat_ids);    // all comma separated
                }           
                break;

            case 'owner':
                // replace ' ' or '-' with '_' - to be valid CMSMS username
                $data = preg_replace('/[\s-]/', '_', $data);
                $imp_exp_mod = \cms_utils::get_module('ImportExport');
                $user_list_by_username = array_flip( $imp_exp_mod->get_users() );
                if (array_key_exists($data, $user_list_by_username)) {
                    $filtered_data = $user_list_by_username[$data];
                } else {
                    $filtered_data = $this->default_owner_id; 
                }
                break;

            case 'url':
                $filtered_data = (string)$data; 
                // strip root_url beginning of 'url' field - if present
                foreach ($this->source_base_urls as $base_url) {
                    if (strpos($data, $base_url) === 0) {
                        $filtered_data = trim( str_replace($base_url, '', $filtered_data), '/');
                        break;
                    }
                }
                break;

            case 'string':
            default:
                $filtered_data = is_array($data) ? implode(',', $data) : (string)$data;
        }

        return $filtered_data;
    }



    /**
     *  get the list of categories for the LISE instance
     */
    public function get_category_list()
    {
        $this->category_list = [];
        $params = [];
        $lise_cats = $this->lise_instance->GetCategoryQuery($params);
        $result = $lise_cats->Execute(true);
        while ($result && $row = $result->FetchRow()) {
            $this->category_list[$row['category_alias']] = $row['category_id'];
        }
    }


    /**
     *  add a category to the LISE instance
     */
    public function add_category($category_alias, $category_name=null)
    {
        $category = $this->lise_instance->InitiateCategory();
		$category->alias		= $category_alias;
        $category->name        	= !empty($category_name) ? $category_name : $category_alias;
		// $category->description	= $description; // not used
		$category->parent_id 	= '-1';		
		$category->active       = '1';
		
		$this->lise_instance->SaveCategory($category);
        $this->new_categories_added[] = $category_alias;
    }


    /**
    *  set lise items create_time - LISE sets it to NOW() on create, and NOT on update
    *  so we have to set it directly
    */
    public function set_create_time($lise_item, $create_time)
    {
        $db = cmsms()->GetDb();
        // reformat the date
        $create_time = date('Y-m-d H:i:s', strtotime($create_time));
        $query = 'UPDATE '.CMS_DB_PREFIX.'module_'.strtolower($this->instance_name).'_item '.
				  'SET create_time = ? WHERE item_id = ?';
        $result = $db->Execute($query, [$create_time, $lise_item->item_id]);
    }


}