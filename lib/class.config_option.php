<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
namespace ImportExport;


class config_option 
{
    public $fieldname = '';     // the name of the field
    public $inputtype = 'text'; // text, textarea, select, checkbox, file (xml), sumbit, cancel+submit
    public $step = 1;           // the step in the process - default 1
    public $label = '';         // automatically set from the language file
    public $value = '';         // the field value - either saved or default
    public $required = false;   // is this field required
    public $options = [];       // options array for select fields, or field_map source fields
    public $size = 80;          // size of the input field
    public $rows = 3;           // number of rows for textarea fields
    public $divclass = '';      // class for the div
    public $addclass = '';      // additional class for the input tag/button  
    public $inputproperties = '';// additional properties for the input field
    public $uiicon = '';        // icon for the submit button, exclude prefix 'ui-icon-'
    public $inline = null;      // optionally output div container start & end, to inline multiple inputs
                                // null (default - not inline), inline_start, inline_end, inline 
                                




    public function __construct($import_export, $fieldname, $settings=[])
    {
        if ( !is_object($import_export) || empty($fieldname) ) {
            throw new \Exception('config_option: import_export & fieldname is required');
        }
        $this->fieldname = $fieldname;
        if (is_array($settings)) {
            foreach ($settings as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
        // get the label from the language file
        $lang_label_key = $import_export::LANG_LABEL_PREFIX.$import_export->type."_$fieldname";
        $this->label = $import_export->mod->Lang($lang_label_key);

        // add the config_option to the import_export object
        $import_export->config_options[$fieldname] = $this;
    }


}