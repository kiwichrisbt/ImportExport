<?php

//  
//  NOTE: this class is obsolete - it is not used in the current version of the module
//  it is retained for reference only
//

class ImporterExporter_tree extends cms_tree
{
    public function get_parents($tagname)
    {
        $parent = $this->getParent();
        if( !$parent || $parent->get_tag($tagname) == '' ) return array($this->get_tag($tagname));

        $tmp = $parent->get_parents($tagname);
        if( !is_array($tmp) ) $tmp = array();
        $tmp[] = $this->get_tag($tagname);
        return $tmp;
    }

    public function get_path($tagname, $delimiter = '/^^/')
    {
        $tmp = $this->get_parents($tagname);
        if( count($tmp) ) return implode($delimiter,$tmp);
    }

    public function &find_child_by_tag($tagname, $value)
    {
        $bad = null;
        if( !$this->has_children() ) return $bad;
        foreach( $this->get_children() as $child ) {
            if( $child->get_tag($tagname) == $value ) return $child;
        }
        return $bad;
    }

    public static function &find_by_path(ImporterExporter_tree $root, $tagname, $path, $delimiter = '/^^/')
    {
        $bad = null;
        $node = $root;
        $tmp = explode($delimiter,$path);
        for( $i = 0; $i < count($tmp); $i++ ) {
            $newnode = $node->find_child_by_tag($tagname,$tmp[$i]);
            if( !$newnode ) return $bad;
            $node = $newnode;
        }
        return $node;
    }
}

?>