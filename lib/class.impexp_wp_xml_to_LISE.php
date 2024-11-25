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
    public $posts_imported = 0;
    public $attachments_imported = 0;
    public $content_updated = 0;


	public function __construct() 
	{	
		parent::__construct();

        $this->type = 'wp_xml_to_LISE';
        $this->type_name = $this->mod->Lang("type_$this->type");
        $this->template_name = 'wp_xml_to_LISE.tpl';
        $this->steps = [
            '1' => 'step_1_select',
            '2' => 'step_2_config',
            '3' => 'step_3_import',
            '4' => 'step_4_completed',
        ]; 
        $this->default_field_mappings = [   // source field => destination field (WP > LISE)
            'title' => 'title',
            'link'  => 'url',
            'wp_status' => 'active',
            'pubDate' => 'create_time',
            'creator' => 'owner',
        ];
        $this->content_update_types = [ 'TextArea' ];
        $this->batch_size = 10;
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
        new config_option($this, 'uploads_location', [
            'inputtype' => 'text',
            'step' => 2,
            'value' => '',
        ]);
        new config_option($this, 'cancel_import', [
            'inputtype' => 'cancel',
            'step' => 2,
            'label' => 'import',
            'uiicon' => 'circle-check',
            'inline' => 'inline_start',
        ]);
        new config_option($this, 'submit_import', [
            'inputtype' => 'submit',
            'step' => 2,
            'label' => 'import',
            'uiicon' => 'circle-check',
            'inline' => 'inline_end',
        ]);


        // Step 3
        new config_option($this, 'cancel_ajax', [
            'inputtype' => 'cancel',
            'step' => 3,
            'label' => 'cancel_import',
            'uiicon' => 'circle-check',
            'inline' => 'inline_start',
        ]);
        new config_option($this, 'submit_retry_attachments', [
            'inputtype' => 'submit',
            'step' => 3,
            'label' => 'retry_attachments',
            'uiicon' => 'circle-check',
            'inline' => 'inline',
            'addclass' => 'hidden',
        ]);
        new config_option($this, 'submit_completed', [
            'inputtype' => 'submit',
            'step' => 3,
            'label' => 'completed',
            'uiicon' => 'circle-check',
            'inline' => 'inline_end',
            'addclass' => 'hidden',
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
                if ( empty($this->config_options['lise_instance']->value) ) {
                    $this->messageManager->addError('No LISE instance selected');
                }
                if ( !empty( $this->messageManager->getErrors() ) ) {
                    $this->step = 1;    // go back to the config page - with errors
                    break;
                }

                // preview wp_xml_file
                $filename = $this->config_options['wp_xml_file']->value;
                $this->wp_xml = new wp_xml_interface( $filename );
                $this->messageManager->addLangMessage( 'preview_wp_xml', $this->wp_xml->wp_title, 
                    $this->wp_xml->wp_post_count, $this->wp_xml->wp_attachment_count, 
                    $this->wp_xml->wp_author_count );
                // set souce details
                $this->source_name = $this->wp_xml->wp_title;
                $this->source_fields = $this->wp_xml->fields;
                $this->config_options['source_base_urls']->value = (string)$this->wp_xml->wp_site_url;
    // $this->source_samples = $this->wp_xml->get_sample_data();
                // warning message re attachments field required
                if ($this->wp_xml->wp_attachment_count>0 && !in_array('wp_attachments', $this->field_map_values)) {
                    $this->messageManager->addMessage( 'Warning: "wp_attachments" field not mapped - attachments will not be imported' );
                }

                // get the LISE instance
                $this->lise_instance = new lise_interface( $this->config_options['lise_instance']->value );
                $this->messageManager->addMessage( $this->mod->Lang('preview_lise_instance', $this->lise_instance->instance_name, $this->lise_instance->record_count) );
                // set destination details
                $this->destination_name = $this->lise_instance->instance_name;

                // get the field map, field_map_source_fields, field_map_source_samples, default_field_mappings
                $this->field_map = $this->lise_instance->generate_field_map();
                $this->get_field_map_values();


                if (!$this->fopen_enabled() ) {
                    $this->messageManager->addError( 'fopen is not enabled on this server. It will not be possible to import any remote images or attachments.' );
                }
                break;

            case '3': // 3rd page - import 
                $this->create_config_options(); // get the config options
                $this->load_config();   // previously saved values
                $this->lise_instance = new lise_interface( $this->config_options['lise_instance']->value );
                $this->field_map = $this->lise_instance->generate_field_map(); // get the field map
                $this->get_field_map_values();
                $this->get_config_values($params);  // get the values from the form
                $this->save_config();   // save the config values & field map
                $this->lise_instance->default_owner_id = $this->config_options['default_owner']->value;
                $this->set_uploads_location(subdir: $this->config_options['uploads_location']->value );
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

                // initiate the progress bar & ajax import
                $this->ajax_status = 'started';
                $this->ajax_position = 0;
                $this->set_progress(0, 100); 
                $extra_details = [
                    'posts_imported' => $this->posts_imported,      // 0
                    'attachments_imported' => $this->attachments_imported,    // 0
                    'content_updated' => $this->content_updated,    // 0
                ];
                $this->set_ajax_processing_details($extra_details);
                break;

            case '4': // 3rd page - import completed
                $this->mod->Redirect('', 'defaultadmin', '');
                break;
        }
    }


    /**
     *  for ajax triggering of processing, after template has been displayed
     *  @param object $saved_details - previously saved details
     */
    public function ajax_process($saved_details)
    {
        $this->reset_ajax_processing_details(); // now we are here - reset the access details
        // put the saved extra details into the object
        $this->posts_imported = $saved_details->posts_imported;
        $this->attachments_imported = $saved_details->attachments_imported;
        $this->content_updated = $saved_details->content_updated;

        $this->create_config_options(); // get the config options
        $this->load_config();   // load the config values & field map
        $this->lise_instance = new lise_interface( $this->config_options['lise_instance']->value );
        $this->field_map = $this->lise_instance->generate_field_map(); // setup the field map
        $this->get_field_map_values();

        $this->lise_instance->default_owner_id = $this->config_options['default_owner']->value;
        $this->set_uploads_location(subdir: $this->config_options['uploads_location']->value );

        if ($this->retry) $this->retry_ajax_process();

        $this->import_wp_xml_to_LISE();



        $extra_details = [
            'posts_imported' => $this->posts_imported,
            'attachments_imported' => $this->attachments_imported,
            'content_updated' => $this->content_updated,
        ];
        $this->set_ajax_processing_details($extra_details);
    }


    public function import_wp_xml_to_LISE()
    {
        // get the wp xml data again
        $filename = $this->config_options['wp_xml_file']->value;
        $this->wp_xml = new wp_xml_interface( $filename );
        $source_base_urls = $this->config_options['source_base_urls']->value;
        $item_count = 0;

        // process the next batch_size number of items
        try {
            switch ($this->ajax_status) {
                case 'started':
                    $this->ajax_status = 'post';
                    $this->ajax_position = 0;
                    // just continue to 'post' - no break

                case 'post':
                    if ( $this->ajax_position==0 ) {    // setup 'post import' feedback message
                        $message = 'Importing <span class="feedback post-count">0</span> of '.
                            $this->wp_xml->wp_post_count.' posts into '.$this->lise_instance->instance_name;
                        $this->messageManager->addMessage( $message );
                    }
                    $items = $this->wp_xml->get_items($this->ajax_status, $this->batch_size, $this->ajax_position);
                    $this->ajax_position = $this->wp_xml->current_item;
                    if ( !empty($items)) {
                        $item_count += $this->lise_instance->import_items($items, $this->field_map, $source_base_urls);
                        $this->posts_imported += $item_count;
                        $this->ajax_feedback['post-count'] = $this->posts_imported;
                    }
                    if ($this->wp_xml->at_xml_end) {    // switch to attachments and start at beginning
                        // display messages after status completed
                        $this->messageManager->addLangMessage( 'message_imported_items', $this->posts_imported, 
                            $this->lise_instance->instance_name );
                        $new_categories_added = $this->lise_instance->new_categories_added;
                        if (!empty($new_categories_added)) {
                            $this->messageManager->addLangMessage( 'message_new_categories_added', 
                                count($new_categories_added), implode(', ', $new_categories_added) );
                        }
                        // move to next status
                        $this->ajax_status = 'attachment';   
                        $this->ajax_position = 0;
                    }
                    break;

                case 'attachment':
                    if ($this->wp_xml->wp_attachment_count==0) {
                        $this->ajax_status = 'content_update';
                        $this->ajax_position = 0;
                        break;
                    }
                    if (!in_array('wp_attachments', $this->field_map_values)) {
                        $this->messageManager->addError('No "wp_attachments" field mapped - '.
                            $this->wp_xml->wp_attachment_count.' attachments will not be imported');
                        $this->ajax_status = 'content_update';
                        $this->ajax_position = 0;
                        break;
                    }

                    if ( $this->ajax_position==0 ) {    // setup 'attachment import' feedback message
                        $message = 'Importing <span class="feedback attachment-count">0</span> of '.
                            $this->wp_xml->wp_attachment_count.' attachments into '.$this->lise_instance->instance_name.': <span class="feedback file-exists-count">0</span> exist locally'.
                            ', <span class="feedback file-saved-count">0</span> remote files saved locally'.
                            ', <span class="feedback file-error-count">0</span> file errors.';

                        $this->messageManager->addMessage( $message );
                    }
                    // find the attachments field $this->field_map_values value = 'wp_attachments'
                    $attachments_field = array_search('wp_attachments', $this->field_map_values);
                    if ($attachments_field === false) break;  // no attachments field

                    $items = $this->wp_xml->get_items($this->ajax_status, $this->batch_size, $this->ajax_position);
                    $this->ajax_position = $this->wp_xml->current_item;
                    if ( !$this->check_uploads_dest_dir('') ) {
                        throw new \Exception('Uploads directory is not writable');
                    }

                    if ( !empty($items)) {
                        // import the attachments - if not already done, also adds 'local_relative_url' to items
                        $this->move_files_to_uploads_location($items);

                        $this->lise_instance->import_attachments($items, $this->field_map, $source_base_urls, $attachments_field);
                        $this->attachments_imported += $this->lise_instance->attachment_count;
                        $this->ajax_feedback['attachment-count'] = $this->attachments_imported;
                        $this->ajax_feedback['file-exists-count'] = $this->file_exists_count;
                        $this->ajax_feedback['file-saved-count'] = $this->file_saved_count;
                        $this->ajax_feedback['file-error-count'] = $this->file_error_count;                        
                    }

                    if ($this->wp_xml->at_xml_end) {  
                        // display messages after status completed
                        $this->messageManager->addLangMessage( 'message_imported_attachments',
                            $this->attachments_imported, $this->lise_instance->instance_name );
                        $this->ajax_status = 'content_update';
                        $this->ajax_position = 0;
                    }
                    break;

                case 'content_update':
                    if ( $this->ajax_position==0 ) {    // setup 'content update' feedback message
                        $message = 'Updating content <span class="feedback lise-count">0</span> of '.
                            $this->lise_instance->record_count.' items in '.$this->lise_instance->instance_name.' - images: <span class="feedback file-exists-count">0</span> exist locally'.
                            ', <span class="feedback file-saved-count">0</span> remote files saved locally'.
                            ', <span class="feedback file-error-count">0</span> file errors.';
                        $this->messageManager->addMessage( $message );
                        $this->file_exists_count = 0;   // reset file counts
                        $this->file_saved_count = 0;
                        $this->file_error_count = 0;   
                    }

                    $items = $this->lise_instance->get_items($this->ajax_position, $this->batch_size);
                    
                    $this->update_content($items, $source_base_urls);
                    //$this->ajax_position = $this->lise_instance->current_item;

                    // update the feedback message
                    $this->ajax_feedback['lise-count'] = $this->content_updated;
                    $this->ajax_feedback['file-exists-count'] = $this->file_exists_count;
                    $this->ajax_feedback['file-saved-count'] = $this->file_saved_count;
                    $this->ajax_feedback['file-error-count'] = $this->file_error_count;   

                    if ($this->lise_instance->at_end) {  
                        $this->messageManager->addLangMessage( 'message_content_updated',
                            $this->content_updated );
                        $this->ajax_status = 'completed';
                    }
                    break;

                case 'completed':
                    // see below
                    break;
            }

            if ($this->ajax_status == 'completed') {
                $this->messageManager->addMessage( 'Import Completed.' );
                if ($this->file_error_count > 0) {
                    $this->messageManager->addMessage( 'Recommended: Retry import of attachments - as the remote server load may be high. '.$this->file_error_count.' file errors.' );
                }
            }

            $this->set_progress( ($this->posts_imported + $this->attachments_imported + 
                $this->content_updated) , $this->progress_total() );

        } catch (\exception $e) {
            $this->messageManager->addError('Import error: '.$e->getMessage() );
        }

    }


    /**
     *  get the total number of tasks for the progress bar
     *  @return int
     */
    public function progress_total()
    {
        $import_total = $this->wp_xml->wp_post_count + $this->wp_xml->wp_attachment_count +
            $this->wp_xml->wp_post_count;   // posts, attachments, content update
        return $import_total;
    }


    public function retry_ajax_process()
    {
        if ($this->retry=='attachment') {
            $this->ajax_status = 'attachment';   
            $this->ajax_position = 0;
            $this->attachments_imported = 0;
            $this->file_exists_count = 0;
            $this->file_saved_count = 0;
            $this->file_error_count = 0;
        }
    }


    public function update_content($items, $source_base_urls)
    {
        $item_count = 0;
        // get the fields to update
        $update_fields = [];
        foreach ($this->field_map as $field) {
            if ( in_array($field->dest_fd_type, $this->content_update_types) ) {
                $update_fields[] = $field->dest_field;
            }
        }

        // update the content items in the batch
        $lise_instance = $this->lise_instance->lise_instance;
        // Create a new DOMDocument and set encoding
        $dom = new \DOMDocument('1.0', 'UTF-8');
        foreach ($items as $item) {
            $item_count++;
            $full_item = $lise_instance->LoadItemByIdentifier('item_id', $item['item_id']);
            foreach ($update_fields as $field) {
                $content_value = (string)$full_item->{$field};
                if (empty($content_value)) continue;

                // Load the HTML content & suppress errors due to malformed HTML with @
                @$dom->loadHTML('<?xml encoding="UTF-8">' . $content_value);  // add encoding to prevent errors
                // Iterate over the <img> tags and change their src attributes
                $images = $dom->getElementsByTagName('img'); // Get all <img> tags
                foreach ($images as $img) {
                    $oldSrc = $img->getAttribute('src');
                    $newSrc = $this->move_file_to_uploads_location($oldSrc);
                    $newSrc = ltrim($newSrc, DIRECTORY_SEPARATOR);
                    if (!empty($newSrc)) {
                        $img->setAttribute('src', $newSrc);
                    }
                }
                $content_value = $dom->saveHTML();  // convert back to string
                // replace [caption] shortcode with <figure> & <figcaption>
                $content_value = $this->replace_caption_shortcode($content_value);

                $full_item->{$field} = $content_value;
                $lise_instance->SaveItem( $full_item );
            }

        }

        $this->ajax_position += $item_count;
        $this->content_updated += $item_count;
        // $current_progress = $this->posts_imported + $this->attachments_imported + $this->content_updated;
        // $this->set_progress( $current_progress , $this->progress_total() );
        return $item_count;
    }


    /**
     *  replace [caption] shortcode with <figure> & <figcaption>
     *  @param string $content
     *  @return string
     */
    public function replace_caption_shortcode($content)
    {
        $pattern = '/\[caption id="([^"]*)" align="([^"]*)" width="([^"]*)"\](.*?)\[\/caption\]/s';
        $new_content = preg_replace_callback($pattern, function($matches) {
            // Extract the content inside the caption
            $content = $matches[4];
            // Separate the image and the caption text
            preg_match('/(<img[^>]+>)(.*)/s', $content, $contentMatches);
            $imgTag = $contentMatches[1];
            $captionText = trim($contentMatches[2]);
            $new_content = '<figure id="' . $matches[1] . '" class="align' . $matches[2] . '" style="width:' . $matches[3] . 'px;">' . $imgTag . '<figcaption>' . $captionText . '</figcaption></figure>';
            return $new_content;
        }, $content);

        return $new_content;
    }



}