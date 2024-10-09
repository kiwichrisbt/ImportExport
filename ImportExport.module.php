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
class ImportExport extends CMSModule
{
    const MODULE_VERSION = '1.0beta1';

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
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }



}


