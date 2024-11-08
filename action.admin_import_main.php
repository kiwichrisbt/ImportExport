<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
/** 
*   @var \ImportExport $this 
*/

if ( !defined('CMS_VERSION') ) exit;
if ( !$this->VisibleToAdminUser() ) return;

$smarty->assign('admin_import_url',$this->create_url($id,'admin_import',$returnid));
echo $this->ProcessTemplate('admin_import_main.tpl');

