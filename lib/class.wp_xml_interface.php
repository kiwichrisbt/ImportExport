<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
namespace ImportExport;


class wp_xml_interface 
{
    const WP_ITEM_DATA = [  // default fields from WP xml - name => xml tag
        'title' => 'title',
        'link' => 'link',
        'pubDate' => 'pubDate',
        'creator' => 'dc:creator',
        'description' => 'description',
        'content' => 'content:encoded',
        'excerpt' => 'excerpt:encoded',
        'wp_post_id' => 'wp:post_id',
        'wp_post_date' => 'wp:post_date',
        'wp_post_name' => 'wp:post_name',
        'wp_status' => 'wp:status',
        'wp_post_parent' => 'wp:post_parent',
        'wp_post_type' => 'wp:post_type',
        'category' => 'category',
        'post_tag' => 'post_tag',
        'is_sticky' => 'wp:is_sticky',
        'wp_attachments' => 'wp:attachment_url',
    ];


    public $wp_xml = null;

    public $wp_title = '';
    public $wp_site_url = '';
    public $wp_item_count = 0;  // includes posts and attachments
    public $wp_post_count = 0;
    public $wp_attachment_count = 0;
    public $wp_author_count = 0;
    public $current_item = 0;
    public $at_xml_end = false;
    public $fields = [];  // array of fields source for the field_map
    public $messageManager = null;



    /**
     *   @param string $filename  The name of the WP xml file
     */
    public function __construct($filename='') 
    {	
        $this->messageManager = MessageManager::getInstance();
        if (empty($filename)) {
            $this->messageManager->addError( 'No filename provided' );
            return;
        }

        $file = file_get_contents( $filename );
        // $this->wp_xml = new \SimpleXMLElement( $file, LIBXML_NOCDATA );
        $this->wp_xml = simplexml_load_file($filename, 'SimpleXMLElement', LIBXML_NOCDATA);
        
        // get basic details
        $this->get_wp_details();
        $this->get_wp_fields();
    }

    
    /**
     *  get the fields from the WP xml - may need to extend this
     */
    public function get_wp_fields()
    {
        // get the default fields from WP_ITEM_DATA
        $this->fields = self::WP_ITEM_DATA;

    }


    /**
     *  get a specific item from the WP xml
        *  @param string $item_type   'post' (default) or 'attachment'
        *  @param integer $item_number  Zero-based index of the item to return
        *  @return array of item fields
     */
    public function get_item($item_type='post', $item_number)
    {
        $item = []; // for a nice sanitised array of fields
        if ($item_number >= $this->wp_item_count) {
            $this->messageManager->addError( 'Item number out of range' );
            return [];
        }
        $xml_item = $this->wp_xml->channel->item[$item_number];

        // check if this is the correct item type, if not return empty array
        $wp_post_type = $xml_item->xpath('wp:post_type')[0];
        if ($item_type!=$wp_post_type) return [];
        
        foreach (self::WP_ITEM_DATA as $name => $xml_tag) {
            // use xpath to retrieve all data, as that also gets CDATA e.g. 'content:encoded'
            $node = $xml_item->xpath($xml_tag);
            switch ($name) {
                case 'category':
                    $item['category'] = [];
                    $item['post_tag'] = [];
                    foreach ($node as $cat_node) {
                        $attributes = $cat_node->attributes();
                        $domain = isset($attributes['domain']) ? (string)$attributes['domain'] : null;
                        $nice_name = isset($attributes['nicename']) ? (string)$attributes['nicename'] : null;
                        $node_value = (string)$cat_node;
                        if (isset($domain) && isset($nice_name) ) {
                            if ( in_array($domain, ['category','post_tag']) ) {
                                $item[$domain][$nice_name] = $node_value;
                            } 
                            // ignore other domains e.g. post_format

                        } else {    // just in case
                            $item['category'][] = $node_value;
                        }
                    }
                    break;

                case 'post_tag':   // ignore - created by 'category'
                    break;

                case 'wp_status':
                    $item['wp_status'] = $node[0]=='publish' ? 1 : 0;  // convert to 1 or 0, 7 wp statuses
                    break;

                default:
                    $item[$name] = (string)($node[0] ?? '');   // convert all values to strings
            }

            
        }
        // special case - save the post_id as field source_id
        $item['source_id'] = $item['wp_post_id'];   // already a string

        return $item;
    }


    /**
     *  get a batch of X items from the WP xml, either posts or attachments, ignore other type
     *  @param integer $item_type   'post' (default) or 'attachment'
     *  @param integer $batch_size  Number of items to return
     *  @param integer $start       Optional. Start at this item, or continue from current position
     *  @return array of items
     */
    public function get_items( $item_type='post', $batch_size=50, $start=null)
    {
        $item_type = $item_type=='attachment' ? 'attachment' : 'post';
        $items = [];
        $this->current_item = $start ? $start : $this->current_item;    // zero-based index
        
        while (count($items) < $batch_size && !$this->at_xml_end) {
            $tmp_item = $this->get_item( $item_type, $this->current_item);
            if (!empty($tmp_item)) $items[] = $tmp_item;
            $this->current_item++;
            if ( $this->current_item >= $this->wp_item_count ) $this->at_xml_end = true;
        }

        return $items;
    }


    /**
     *  get the stats for the WP xml - both posts and attachments
     */
    public function get_wp_details()
    {
        if ( empty($this->wp_xml->channel) ) return;

        $this->wp_title = $this->wp_xml->channel->title;
        $this->wp_site_url = $this->wp_xml->channel->link;
        $this->wp_item_count = !empty($this->wp_xml->channel->item) ? 
            $this->wp_xml->channel->item->count() : 0;
        $authors = $this->wp_xml->channel->xpath('wp:author');
        $this->wp_author_count = count($authors);

        // count the posts and attachments
        $this->wp_post_count = 0;
        $this->wp_attachment_count = 0;
        foreach ($this->wp_xml->channel->item as $item) {
            $post_type = $item->xpath('wp:post_type');
            if ($post_type[0] == 'attachment') {
                $this->wp_attachment_count++;
            } else {
                $this->wp_post_count++;
            }
        }
    }



}