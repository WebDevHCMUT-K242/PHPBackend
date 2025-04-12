<?php

class UserData {
    public $id;
    public $username;
    public $display_name;
    public $hashed_password;

    function __construct($id = null, $username = null, $display_name = null, $hashed_password = null) {
        if ($id) {
            $this->set_id($id);
        }
        if ($username) {
            $this->set_username($username);
        }
        if ($display_name) {
            $this->set_display_name($display_name);
        }
        if ($hashed_password) {
            $this->set_hashed_password($hashed_password);
        }
    }

    function set_id($id) {
        $this->id = $id;
    }

    function set_username($name) {
        $this->username = $name;
    }

    function set_display_name($name) {
        $this->display_name = $name;
    }

    function set_hashed_password($hashed_password) {
        $this->hashed_password = $hashed_password;
    }
}