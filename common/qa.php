<?php

include_once __DIR__ . "/db.php";

class QaThread {
    public $id;

    public $user_id;
    public $title;
    public $message;

    public $timestamp;

    function __construct($id, $user_id, $title, $message, $timestamp) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->title = $title;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }

    function set_id($id) {
        $this->id = $id;
    }

    function set_user_id($user_id) {
        $this->user_id = $user_id;
    }

    function set_title($title) {
        $this->title = $title;
    }

    function set_message($message) {
        $this->message = $message;
    }

    function set_timestamp($timestamp) {
        $this->timestamp = $timestamp;
    }

    // Database operations

    public static function maybeCreateQaThreadsTable() {
        Database::getConnection()->query("
            CREATE TABLE IF NOT EXISTS qa_threads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
                FOREIGN KEY (user_id) REFERENCES users(id)
            ) ENGINE=InnoDB;
        ");
    }
}