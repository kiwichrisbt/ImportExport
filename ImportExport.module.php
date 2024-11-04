<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
# CMS - CMS Made Simple (c) 2004-2024 by CMS Made Simple Foundation (foundation@cmsmadesimple.org)
# Project's homepage is: http://www.cmsmadesimple.org
# Module's homepage is: http://dev.cmsmadesimple.org/projects/importexport
#---------------------------------------------------------------------------------------------------
# This program is free software; you can redistribute it and/or modify it under the terms of the
# GNU General Public License as published by the Free Software Foundation; either version 3
# of the License, or (at your option) any later version.
#
# This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
# without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
# See the GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License along with this program.
# If not, see <http://www.gnu.org/licenses/>.
#---------------------------------------------------------------------------------------------------
# 
#    Note: there are no install/uninstall/upgrade methods in this module as it is intended to be
#    installed only as required then uninstalled
#---------------------------------------------------------------------------------------------------

class ImportExport extends CMSModule
{
    const MODULE_VERSION = '1.0beta1';

    const IMPORT_EXPORT_TYPES = [
        'wp_xml_to_LISE',
        // 'wp_db_to_News',         // not implemented - needs updating - if actually needed???
    ];
    const CLASS_PREFIX = 'ImportExport\impexp_';
    const LANG_PROMPT_SUFFIX = '_prompt';

    public function GetName() { return 'ImportExport'; }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return self::MODULE_VERSION; }
    public function MinimumCMSVersion() { return '2.2'; }
    public function GetHelp() { return @file_get_contents(__DIR__.'/readme.md'); }
    public function GetAuthor() { return 'KiwiChris'; }
    public function GetAuthorEmail() { return 'chris@binnovative.co.uk'; }
    public function GetChangeLog() { return cmsms()->GetSmarty()->fetch('module_file_tpl:ImportExport;changelog.tpl'); }
    public function IsPluginModule() { return FALSE; }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function HasAdmin() { return TRUE; }
    public function VisibleToAdminUser() {
        $uid = get_userid();
        return UserOperations::get_instance()->IsSuperuser($uid);
    }
    public function GetHeaderHTML() { return $this->get_header_css_js(); }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }



    /**
     * output the css and js file links to be included in the head for the admin interface
     * @return string
     */
    public function get_header_css_js() {
        if (cms_utils::get_app_data('ImportExport_js_css_loaded')) return '';
        $path = $this->GetModuleURLPath();
        $admin_css_js = '
            <link rel="stylesheet" type="text/css" href="'.$path.'/lib/css/importexport_admin.css?v'.self::MODULE_VERSION.'">
            <script language="javascript" src="'.$path.'/lib/js/importexport_admin.js?v'.self::MODULE_VERSION.'"></script>';
        cms_utils::set_app_data('ImportExport_js_css_loaded', 1);

        return $admin_css_js;
    }



    /**
     *  get array of users and ids
     *  @return array of id => username
     */
    public function get_users()
    {
        $user_list = [];
        $userops = \UserOperations::get_instance();
        $allusers = $userops->LoadUsers();
        foreach ($allusers as $user) {
            $user_list[$user->id] = $user->username;
        }
        
        return $user_list;
    }

}


