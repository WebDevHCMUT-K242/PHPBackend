<?php

class UserData {
    public $id;
    public $is_admin;
    public $username;
    public $display_name;
    public $hashed_password;

    function __construct($id = null, $is_admin = false, $username = null, $display_name = null, $hashed_password = null) {
        if ($id) {
            $this->set_id($id);
        }
        if ($is_admin) {
            $this->set_is_admin($is_admin);
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

    function set_is_admin($is_admin) {
        $this->is_admin = $is_admin;
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