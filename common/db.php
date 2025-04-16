<?php

class Database {
    private static $instance = null;

    public static function getConnection() {
        if (self::$instance === null) {
            $connection = new mysqli('localhost', 'root', '');
            if ($connection->connect_error) {
                die("Database connection failed: " . $connection->connect_error);
            }
            $connection->query("CREATE DATABASE IF NOT EXISTS db");
            $connection->select_db('db');
            self::$instance = $connection;
        }
        return self::$instance;
    }
}