<?php
#---------------------------------------------------------------------------------------------------
# Module: ImportExport
# Author: Chris Taylor
# Copyright: (C) 2024 Chris Taylor, chris@binnovative.co.uk
# Licence: GNU General Public License version 3
#          see /ImportExport/lang/LICENCE.txt or <http://www.gnu.org/licenses/gpl-3.0.html>
#---------------------------------------------------------------------------------------------------
namespace ImportExport;


class MessageManager {
    private static $instance = null;
    private $messages = [];
    private $errors = [];
    private $mod = null;

    private function __construct() {
        $this->mod = \cms_utils::get_module('ImportExport');
    }


    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new MessageManager();
        }
        return self::$instance;
    }



    public function addMessage($message) {
        $this->messages[] = $message;
    }

    
    /**
     * add a message to the message list, with help lookup using $lang_key
     * @param mixed $lang_key
     */
    public function addLangMessage() {
        $args = func_get_args();
        $message = call_user_func_array([$this->mod, 'Lang'], $args);
        $this->messages[] = $message;
    }


    public function getMessages() {
        return $this->messages;
    }


    public function addError($error) {
        $this->errors[] = $error;
    }


    /**
     * add a message to the message list, with help lookup using $lang_key
     * @param mixed $lang_key
     */
    public function addLangError() {
        $args = func_get_args();
        $error = call_user_func_array([$this->mod, 'Lang'], $args);
        $this->errors[] = $error;
    }


    public function getErrors() {
        return $this->errors;
    }
}