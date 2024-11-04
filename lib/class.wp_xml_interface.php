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
        'wp_post_date' => 'wp:post_date',
        'wp_post_name' => 'wp:post_name',
        'wp_status' => 'wp:status',
        'wp_post_type' => 'wp:post_type',
        'category' => 'category',
        'post_tag' => 'post_tag',
    ];


    public $wp_xml = null;

    public $wp_title = '';
    public $wp_site_url = '';
    public $wp_item_count = 0;
    public $wp_author_count = 0;
    public $current_item = 0;
    public $at_end = false;
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
        $this->wp_title = $this->wp_xml->channel->title;
        $this->wp_site_url = $this->wp_xml->channel->link;
        // $items = $this->wp_xml->channel->item;
        $this->wp_item_count = $this->wp_xml->channel->item->count();
        $authors = $this->wp_xml->channel->xpath('wp:author');
        $this->wp_author_count = count($authors);

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
        *  @param integer $item_number  Zero-based index of the item to return
        *  @return array of item fields
     */
    public function get_item($item_number)
    {
        $item = []; // for a nice sanitised array of fields
        if ($item_number >= $this->wp_item_count) {
            $this->messageManager->addError( 'Item number out of range' );
            return [];
        }
        $xml_item = $this->wp_xml->channel->item[$item_number];
        
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
                    $item[$name] = (string)$node[0];   // convert all values to strings
            }

            
        }

        return $item;
    }


    /**
     *  get a batch of items from the WP xml
     *  @param integer $batch_size  Number of items to return
     *  @param integer $start       Optional. Start at this item, or continue from current position
     *  @return array of items
     */
    public function get_items($batch_size=50, $start=null)
    {
        $items = [];
        $start = $start ? $start : $this->current_item; // zero-based index
        $end = min($start + $batch_size, $this->wp_item_count); // zero-based index
        for ($i = $start; $i < $end ; $i++) {
            $items[] = $this->get_item($i);
        }
        $this->current_item = $i;
        if ($i >= $this->wp_item_count) $this->at_end = true;
        
        return $items;
    }




}