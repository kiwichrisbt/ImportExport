<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
namespace ImportExport;


class field_map_item 
{
    public $dest_field;         // string - field alias
    public $dest_description;   // description of the field if available
    public $dest_data_type;     // string - data type of the field
    public $dest_fd_type;       // string - field definition type of the field
    public $dest_required;      // boolean - if field is required 
    public $source_field;       // string - source field alias


           
    public function __construct($dest_field, $dest_description='', $dest_data_type='', $dest_fd_type=null, $dest_required=false, $source_field=null) 
    {
        $this->dest_field = $dest_field;
        $this->dest_description = $dest_description;
        $this->dest_data_type = $dest_data_type;
        $this->dest_fd_type = $dest_fd_type;
        $this->dest_required = $dest_required;
        $this->source_field = $source_field;
    }


}