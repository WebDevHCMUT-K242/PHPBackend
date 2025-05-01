<?php

include_once __DIR__ . "/db.php";

class QaThread {
    public $id;

    public $user_id;
    public $title;
    public $message;

    public $timestamp;
    public $last_updated;
    public $is_locked;

    function __construct($id, $user_id, $title, $message, $timestamp, $last_updated, $is_locked) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->title = $title;
        $this->message = $message;
        $this->timestamp = $timestamp;
        $this->last_updated = $last_updated;
        $this->is_locked = $is_locked;
    }

    function set_id($id) { $this->id = $id; }
    function set_user_id($user_id) { $this->user_id = $user_id; }
    function set_title($title) { $this->title = $title; }
    function set_message($message) { $this->message = $message; }
    function set_timestamp($timestamp) { $this->timestamp = $timestamp; }
    function set_last_updated($last_updated) { $this->last_updated = $last_updated; }
    function set_is_locked($is_locked) { $this->is_locked = $is_locked; }

    // Database operations

    public static function maybeCreateQaThreadsTable() {
        Database::getConnection()->query("
            CREATE TABLE IF NOT EXISTS qa_threads (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                is_locked BOOLEAN NOT NULL DEFAULT FALSE,

                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public static function createThread($user_id, $title, $message) {
        $conn = Database::getConnection();
        // These 3 are all we need, timestamp/last_updated and is_locked are already done by default
        $stmt = $conn->prepare("
            INSERT INTO qa_threads (user_id, title, message)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $user_id, $title, $message);
        $stmt->execute();

        return $conn->insert_id;
    }

    public static function getThread($thread_id) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT id, user_id, title, message, timestamp, last_updated, is_locked
            FROM qa_threads
            WHERE id = ?
        ");
        $stmt->bind_param("i", $thread_id);
        $stmt->execute();

        $stmt->bind_result($id, $user_id, $title, $message, $timestamp, $last_updated, $is_locked);
        if ($stmt->fetch()) {
            return new QaThread($id, $user_id, $title, $message, $timestamp, $last_updated, $is_locked);
        }

        return null;
    }

    public static function getPageCount($per_page = 20) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("SELECT COUNT(*) AS total_threads FROM qa_threads");
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $total_threads = $row['total_threads'];

        $page_count = ceil($total_threads / $per_page);

        return $page_count;
    }

    public static function listThreads($page = 1, $per_page = 20) {
        $conn = Database::getConnection();

        $offset = ($page - 1) * $per_page;
        $stmt = $conn->prepare("
            SELECT id, user_id, title, message, timestamp, last_updated, is_locked
            FROM qa_threads
            ORDER BY timestamp DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();

        $result = $stmt->get_result();
        $threads = [];

        while ($row = $result->fetch_assoc()) {
            $threads[] = new QaThread(
                $row['id'],
                $row['user_id'],
                $row['title'],
                $row['message'],
                $row['timestamp'],
                $row['last_updated'],
                $row['is_locked']
            );
        }

        return $threads;
    }

    public static function updateThreadTimestamp($thread_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            UPDATE qa_threads SET last_updated = CURRENT_TIMESTAMP WHERE id = ?
        ");
        $stmt->bind_param("i", $thread_id);
        $stmt->execute();
    }

    public static function setThreadIsLocked($thread_id, $is_locked) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            UPDATE qa_threads
            SET is_locked = ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $is_locked, $thread_id);
        return $stmt->execute();
    }

    public static function deleteThread($thread_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM qa_threads WHERE id = ?");
        $stmt->bind_param("i", $thread_id);
        return $stmt->execute();
    }
}

class QaPost {
    public $id;
    public $thread_id;
    public $user_id;
    public $message;
    public $timestamp;

    function __construct($id, $thread_id, $user_id, $message, $timestamp) {
        $this->id = $id;
        $this->thread_id = $thread_id;
        $this->user_id = $user_id;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }

    public static function maybeCreateQaPostsTable() {
        Database::getConnection()->query("
            CREATE TABLE IF NOT EXISTS qa_posts (
                id INT NOT NULL,
                thread_id INT NOT NULL,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id, thread_id),
                FOREIGN KEY (thread_id) REFERENCES qa_threads(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public static function createPost($thread_id, $user_id, $message) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            INSERT INTO qa_posts (id, thread_id, user_id, message)
            VALUES (NULL, ?, ?, ?)
        ");
        $stmt->bind_param("iis", $thread_id, $user_id, $message);
        $stmt->execute();

        $post_id = $conn->insert_id;

        QaThread::updateThreadTimestamp($thread_id);

        return $post_id;
    }

    public static function getPostsForThread($thread_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT id, thread_id, user_id, message, timestamp
            FROM qa_posts
            WHERE thread_id = ?
            ORDER BY timestamp ASC
        ");
        $stmt->bind_param("i", $thread_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $posts = [];

        while ($row = $result->fetch_assoc()) {
            $posts[] = new QaPost(
                $row['id'],
                $row['thread_id'],
                $row['user_id'],
                $row['message'],
                $row['timestamp']
            );
        }

        return $posts;
    }

    public static function getPost($thread_id, $user_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT id, thread_id, user_id, message, timestamp
            FROM qa_posts
            WHERE thread_id = ? AND user_id = ?
            ORDER BY timestamp ASC
        ");
        $stmt->bind_param("ii", $thread_id, $user_id);
        $stmt->execute();

        $result = $stmt->get_result();
        if ($result->num_rows !== 1) {
            return null;
        }

        return new QaPost(
            $result['id'],
            $result['thread_id'],
            $result['user_id'],
            $result['message'],
            $result['timestamp']
        );
    }

    public static function deletePost($thread_id, $post_id) {
        $conn = Database::getConnection();

        $delete = $conn->prepare("DELETE FROM qa_posts WHERE id = ? AND thread_id = ?");
        $delete->bind_param("ii", $post_id, $thread_id);
        $delete->execute();

        QaThread::updateThreadTimestamp($thread_id);

        return true;
    }
}
