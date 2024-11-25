<?php

// Note: indented $lang strings have not yet been confirmed as current

#A
    $lang['article_count'] = 'Articles imported';
    $lang['author_default'] = 'Default Author';

#B


#C
$lang['cancel'] = 'Cancel';
$lang['change_type'] = 'Change type';
$lang['clear_selection'] = 'Clear selection';
    $lang['check'] = 'Check';
    $lang['news_field_exists'] = 'A News custom field with the name %s already exists';
    $lang['clear_news_categories'] = 'Delete all News categories';
    $lang['clear_news_fielddefs'] = 'Delete all News custom fields';
    $lang['clear_news_posts'] = 'Clear all News posts';
    $lang['col_wpkey'] = 'WP Meta Key';
    $lang['col_fldname'] = 'News Field Name';
    $lang['created_news_field'] = 'Created News custom field: %s';

#D
    $lang['db_host'] = 'Database Hostname';
    $lang['db_name'] = 'Database Name';
    $lang['db_user'] = 'Database Username';
    $lang['db_pass'] = 'Database Password';
    $lang['db_prefix'] = 'Database Prefix';


#E
    $lang['enable_postmeta_mapping'] = 'Enable importing Wordpress metadata as News fields';
    $lang['error_duplicate'] = 'A duplicate %s was detected';
    $lang['error_invalidvalue'] = 'The value %s is invalid for a %s';
    $lang['error_invalid_postmeta'] = 'Invalid data entered for postmeta';
    $lang['error_invalidurl'] = 'Invalid URL <em>(maybe it is already used, or there are invalid characters)</em>';
$lang['error_invalidfiletype'] = 'Upload failed. Allowed file types: %s';
$lang['error_upload'] = 'There was an error uploading the file %s.';
$lang['error_no_import_items'] = 'No items found to import';
// error_multiple_categories
$lang['error_multiple_categories'] = '%s items had multiple categories set, but LISE category only enabled for single categories.';




#F
$lang['field_map_destination_fields'] = 'destination fields';
$lang['field_map_source_fields'] = 'source fields';
$lang['friendlyname'] = 'Import Export';


#G


#H


#I
$lang['import_progress'] = 'Import Progress';
    $lang['image_count'] = 'Image Count';
    $lang['image_dest'] = 'Image Destination';
    $lang['image_errors'] = 'Image Errors';
    $lang['info_clear_news_categories'] = 'If enabled, ALL News categories will be deleted.  Only valid if clear posts is also selected.';
    $lang['info_clear_news_fielddefs'] = 'If enabled, ALL News field definitions will be deleted.  Only valid if clear posts is also selected.';
    $lang['info_clear_news_posts'] = 'If enabled, ALL News posts will be deleted';
    $lang['info_image_dest'] = 'Specify the name of a folder (relative to the uploads directory) where eligable images should be copied to';
    $lang['info_import_images'] = 'This process involves parsing the Wordpress post content searching for image tags, and trying to then copy the images to the local server, and change the post content accordingly.  Translation will only be done for images that begin with one of the image paths listed below.';
    $lang['info_import_posts'] = 'Import posts from WordPress&trade; to News';
    $lang['info_author_default'] = 'Select default admin author. If WP post author not a CMSMS Backend User this default author will be used.';
    $lang['info_postmeta_mapping'] = <<<EOT
    <p>This functionality allows converting post data stored as metadata into News custom fields.  You must select a wordpress metadata field and enter an appropriate News field name.<p><br/>
    <p><strong>Note:</strong> It is not appropriate to copy ALL of the wordpress metadata.  You may need to search through the wp_postmeta table to determine the keys for the table that you want to import.</p><br/>
    <p>New News fields will be created for each fieldname entered, public text fields.</p>
    EOT;
    $lang['info_root_path'] = 'The complete path to the wordpress installation on this server. Similar to the root path of this website: %s';
    $lang['info_root_url'] = 'The complete public URL to the wordpress installation. ';
    $lang['info_user_pw'] = 'Specify the password for imported FEU Users';
    $lang['image_paths'] = 'Image Paths';
    $lang['import'] = 'Import';
    $lang['import_categories'] = 'Import WordPress&trade; Categories';
    $lang['import_posts'] = 'Import WordPress&trade; Posts';
    $lang['import_images'] = 'Import WordPress&trade; Images';
    $lang['import_users'] = 'Import Users';
    $lang['info_import_categories'] = 'If enabled, this otion will create News categories for each unique wordpress category name, and also add the imported article into matching categories.  The system may not handle nested categories with identical names to other nested categories.  i.e:  Hockey >> Womens vs.  Soccer >> Womens';
    $lang['info_image_paths'] = 'Specify <em>(one entry per line)</em> URL prefixes that represent images that should be copied from the wordpress site to the new News installation.  i.e: http://www.mywpsite.com/wp-content/uploads';

#J


#K


#L
$lang['label_select_source'] = '--- select source field ---';
$lang['label_wp_xml_to_LISE_lise_instance'] = 'Select LISE Instance to import to';
$lang['label_wp_xml_to_LISE_submit_continue'] = 'Continue';
$lang['label_wp_xml_to_LISE_submit_import'] = 'Import';
$lang['label_wp_xml_to_LISE_wp_xml_file'] = 'Select WordPress&trade; XML file';
$lang['label_wp_xml_to_LISE_lise_to_wp_field_map'] = 'Select LISE fields to map WordPress&trade; content to';
$lang['label_wp_xml_to_LISE_limit_source_selection'] = 'Allow source fields to be selected only once';
$lang['label_wp_xml_to_LISE_delete_all_items_before_import'] = 'Delete all LISE items before import - warning: this cannot be undone';
$lang['label_wp_xml_to_LISE_source_base_urls'] = 'Source base URL(s) - comma separated list of urls to be stripped from image paths and relative CMSMS urls';
$lang['label_wp_xml_to_LISE_auto_create_categories'] = 'Auto create categories from imported posts';
// label_wp_xml_to_LISE_default_owner 
$lang['label_wp_xml_to_LISE_default_owner'] = 'Select Default Owner - if WP post author not a CMSMS Backend User this default author will be used. Note: use "_" instead of spaces or "-" in the username.';
$lang['label_wp_xml_to_LISE_submit_completed'] = 'Import Completed';
// label_wp_xml_to_LISE_uploads_location
$lang['label_wp_xml_to_LISE_uploads_location'] = 'Uploads Location - relative to the uploads directory .e.g. blog-images';
$lang['label_wp_xml_to_LISE_submit_retry_attachments'] = 'Retry Attachments';
$lang['label_wp_xml_to_LISE_cancel_ajax'] = 'Cancel Import';
$lang['label_wp_xml_to_LISE_cancel_import'] = 'Cancel Import';
    $lang['legend_postmeta_mapping'] = 'Map Wordpress&trade; Meta Data';


#M
$lang['message_file_uploaded'] = 'File %s is successfully uploaded.';
$lang['message_lise_deleted_all_items'] = 'All %s items deleted from LISE instance: %s';
$lang['message_imported_items'] = 'Imported %s items into %s';
$lang['message_imported_attachments'] = 'Imported %s attachments';
$lang['message_new_categories_added'] = '%s New categories added: %s';
$lang['message_content_updated'] = 'Content updated for %s items';

$lang['module_description'] = 'This module, adds the ability to Import & Export content.';
    $lang['msg_wpfound'] = 'WordPress&trade; installation found';

#N
    $lang['none'] = 'None';

#O
    $lang['options'] = 'Options';

#P
$lang['postinstall'] = 'The ImportExport module has been installed';
$lang['postuninstall'] = 'The ImportExport module has been uninstalled';
$lang['preview_wp_xml'] = 'Selected WordPress&trade; XML file: "%s" with %d items, %d attachments and %d authors';
$lang['preview_lise_instance'] = 'Selected LISE Instance: "%s" with %d items';


#Q


#R
    $lang['root_path'] = 'Root Path';
    $lang['root_url'] = 'Root URL';

#S
$lang['selected_import_export'] = 'Selected Import Export Type';
$lang['step_1_select'] = 'Select'; 
$lang['step_2_config'] = 'Configuration'; 
$lang['step_3_import'] = 'Import'; 
$lang['step_4_completed'] = 'Completed'; 



#T
    $lang['test'] = 'Test';
    $lang['title_import'] = 'Import from WordPress&trade;';
$lang['type_select'] = '--- select Import Export type ---';
$lang['type_wp_xml_to_LISE'] = 'Wordpress xml to LISE';


#U
    $lang['user_count'] = 'User Count';
    $lang['user_pw'] = 'User Password';

#V


#W
    $lang['warn_longtime'] = '<strong>Warning</strong> This operation may take a considerable amount of time to complete.  Use caution, and ensure that your PHP is configured so that the set_time_limit method works from within php.';
    $lang['warn_wp_import'] = '<strong>Warning</strong> This operation will copy WordPress&trade; posts into the CMSMS News module.  It does not check for matching existing articles, therefore repeatedly running this action can create duplicate blog articles.  We recommend you perform this installation on a blank News installation, or on a development installation for testing purposes.';
    $lang['wpsettings'] = 'WordPress&trade; Installation';

#X


#Y


#Z


