<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
namespace ImportExport;

class impexp_wp_xml_to_LISE extends ImportExportBase 
{

    public $wp_xml = null;
    public $lise_instance = null;


	public function __construct() 
	{	
		parent::__construct();

        $this->type = 'wp_xml_to_LISE';
        $this->type_name = $this->mod->Lang("type_$this->type");
        $this->template_name = 'wp_xml_to_LISE.tpl';
        $this->steps = [
            '1' => 'step_1_select',
            '2' => 'step_2_config',
            '3' => 'step_3_complete',
        ]; 
        $this->default_field_mappings = [   // source field => destination field (WP > LISE)
            'title' => 'title',
            'link'  => 'url'
        ];
    }

    
    public function create_config_options()
    {
        if (!empty($this->config_options)) $this->config_options = [];
        // used in step 1
        new config_option($this, 'wp_xml_file', [
            'inputtype' => 'file_xml',
            'step' => 1,
            'required' => true,
        ]);
        $lise = new lise_interface('');
        new config_option($this, 'lise_instance', [
            'inputtype' => 'select',
            'step' => 1,
            'required' => true,
            'options' => $lise->get_lise_instance_list(),
        ]);
        new config_option($this, 'submit_continue', [
            'inputtype' => 'submit',
            'step' => 1,
            'label' => 'continue',
            'uiicon' => 'circle-check',
        ]);

        // used in step 2
        new config_option($this, 'lise_to_wp_field_map', [
            'inputtype' => 'field_map',
            'step' => 2,
        ]);
        new config_option($this, 'limit_source_selection', [
            'inputtype' => 'checkbox',
            'step' => 2,
            'value' => 1,
        ]);
        new config_option($this, 'delete_all_items_before_import', [
            'inputtype' => 'checkbox',
            'step' => 2,
            'value' => 0,
        ]);
        new config_option($this, 'auto_create_categories', [
            'inputtype' => 'checkbox',
            'step' => 2,
            'value' => 0,
        ]);
        new config_option($this, 'source_base_urls', [
            'inputtype' => 'text',
            'step' => 2,
            'value' => '',
        ]);
        new config_option($this, 'default_owner', [
            'inputtype' => 'select',
            'step' => 2,
            'required' => true,
            'options' => $this->mod->get_users(),
        ]);
        new config_option($this, 'submit_import', [
            'inputtype' => 'cancel+submit',
            'step' => 2,
            'label' => 'import',
            'uiicon' => 'circle-check',
        ]);

        // Step 3
        new config_option($this, 'submit_completed', [
            'inputtype' => 'submit',
            'step' => 3,
            'label' => 'completed',
            'uiicon' => 'circle-check',
        ]);

    }


    public function process($params=[])
    {
        // determine the current step in the process - increment after submit - default is 1
        $this->step = !empty($params['step']) ? $params['step']+1 : 1; 
        switch ($this->step) {
            case '1': // 1st page - setup the config options
                $this->create_config_options(); // get the config options
                $this->load_config();   // previously saved values
                break;

            case '2': // 2nd page - config options submitted, process the import
                $this->create_config_options(); // get the config options
                $this->load_config();   // previously saved values
                $this->get_config_values($params);  // get the values from the form
                $this->save_config();
                if ( !empty( $this->messageManager->getErrors() ) ) {
                    $this->step = 1;    // go back to the config page - with errors
                    break;
                }

                // preview wp_xml_file
                $filename = $this->config_options['wp_xml_file']->value;
                $this->wp_xml = new wp_xml_interface( $filename );
                $this->messageManager->addMessage( $this->mod->Lang('preview_wp_xml', $this->wp_xml->wp_title, $this->wp_xml->wp_item_count, $this->wp_xml->wp_author_count) );
                // set souce details
                $this->source_name = $this->wp_xml->wp_title;
                $this->source_fields = $this->wp_xml->fields;
                $this->config_options['source_base_urls']->value = (string)$this->wp_xml->wp_site_url;
    // $this->source_samples = $this->wp_xml->get_sample_data();

                // get the LISE instance
                $this->lise_instance = new lise_interface( $this->config_options['lise_instance']->value );
                $this->messageManager->addMessage( $this->mod->Lang('preview_lise_instance', $this->lise_instance->instance_name, $this->lise_instance->record_count) );
                // set destination details
                $this->destination_name = $this->lise_instance->instance_name;

                // get the field map, field_map_source_fields, field_map_source_samples, default_field_mappings
                $this->field_map = $this->lise_instance->generate_field_map();
                $this->get_field_map_values();


                break;

            case '3': // 3rd page - complete import
                $this->create_config_options(); // get the config options
                $this->load_config();   // previously saved values
                $this->lise_instance = new lise_interface( $this->config_options['lise_instance']->value );
                $this->field_map = $this->lise_instance->generate_field_map(); // get the field map
                $this->get_field_map_values();
                $this->get_config_values($params);  // get the values from the form
                $this->save_config();
                if ($this->config_options['auto_create_categories']->value) {
                    $this->lise_instance->get_category_list();
                }

                if ( !empty( $this->messageManager->getErrors() ) ) {
                    $this->step = 1;    // go back to the config page - with errors
                    break;
                }

// maybe validate the field map - e.g. for required fields

                // delete all items before import - config option
                if ($this->config_options['delete_all_items_before_import']->value) {
                    $this->lise_instance->delete_all_items();
                }

                // import the data
                $this->import_wp_xml_to_LISE();
                break;

            case '4': // 3rd page - complete import
                $this->mod->Redirect($id, 'defaultadmin', $returnid);
                break;
        }
    }



    public function import_wp_xml_to_LISE()
    {
// $this->messageManager->addMessage( $this->mod->Lang('import_started') );
        // get the wp xml data again
        $filename = $this->config_options['wp_xml_file']->value;
        $this->wp_xml = new wp_xml_interface( $filename );

        $items = $this->wp_xml->get_items(1000);   // get the first 10 items

        try {
            if (empty($items)) {
                $this->messageManager->addError( $this->mod->Lang('error_no_import_items') );
                return;
            }
            $source_base_urls = $this->config_options['source_base_urls']->value;
            $this->lise_instance->default_owner_id = $this->config_options['default_owner']->value;
            $this->lise_instance->import_items($items, $this->field_map, $source_base_urls);

        } catch (\exception $e) {
            $this->messageManager->addError('Import error: '.$e->getMessage() );

        }

    }


}