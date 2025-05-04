<?php

include_once __DIR__ . "/db.php";

class Article {
    public $id;
    public $user_id;
    public $title;
    public $content;
    public $timestamp;
    public $last_updated;

    public function __construct($id, $user_id, $title, $content,$timestamp, $last_updated) {
        $this->id = $id;
        $this->user_id = $user_id;
        $this->title = $title;
        $this->content = $content;
        $this->timestamp = $timestamp;
        $this->last_updated = $last_updated;
    }

    // Setters
    public function set_id($id) { $this->id = $id; }
    public function set_user_id($user_id) { $this->user_id = $user_id; }
    public function set_title($title) { $this->title = $title; }
    public function set_content($content) { $this->content = $content; }
    public function set_timestamp($timestamp) { $this->timestamp = $timestamp; }
    public function set_last_updated($last_updated) { $this->last_updated = $last_updated; }

    // Database operations

    public static function maybeCreateArticlesTable() {
        Database::getConnection()->query("
            CREATE TABLE IF NOT EXISTS articles (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                title VARCHAR(255) NOT NULL,
                content TEXT NOT NULL,
                timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_updated DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public static function createArticle($user_id, $title, $content) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO articles (user_id, title, content) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("isss", $user_id, $title, $content);
        $stmt->execute();
        return $conn->insert_id;
    }

    public static function getArticle($article_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT id, user_id, title, content, timestamp, last_updated FROM articles WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $stmt->bind_result($id, $user_id, $title, $content, $timestamp, $last_updated);
        if ($stmt->fetch()) {
            return new Article($id, $user_id, $title, $content, $timestamp, $last_updated);
        }
        return null;
    }

    public static function listArticles($page = 1, $per_page = 20) {
        $conn = Database::getConnection();
        $offset = ($page - 1) * $per_page;
        $stmt = $conn->prepare("SELECT id, user_id, title, content, timestamp, last_updated FROM articles ORDER BY timestamp DESC LIMIT ? OFFSET ?");
        $stmt->bind_param("ii", $per_page, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $articles = [];
        while ($row = $result->fetch_assoc()) {
            $articles[] = new Article(
                $row['id'],
                $row['user_id'],
                $row['title'],
                $row['content'],
                $row['timestamp'],
                $row['last_updated']
            );
        }
        return $articles;
    }

    public static function getPageCount($per_page = 20) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT COUNT(*) AS total_articles FROM articles");
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ceil($row['total_articles'] / $per_page);
    }

    public static function updateArticle($article_id, $title, $content) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("UPDATE articles SET title = ?, content = ?, last_updated = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->bind_param("sssi", $title, $content, $article_id);
        return $stmt->execute();
    }

    public static function deleteArticle($article_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM articles WHERE id = ?");
        $stmt->bind_param("i", $article_id);
        return $stmt->execute();
    }
}

class ArticleComment {
    public $id;
    public $article_id;
    public $user_id;
    public $message;
    public $timestamp;

    public function __construct($id, $article_id, $user_id, $message, $timestamp) {
        $this->id = $id;
        $this->article_id = $article_id;
        $this->user_id = $user_id;
        $this->message = $message;
        $this->timestamp = $timestamp;
    }

    public static function maybeCreateCommentsTable() {
        Database::getConnection()->query("
            CREATE TABLE IF NOT EXISTS article_comments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                article_id INT NOT NULL,
                user_id INT NOT NULL,
                message TEXT NOT NULL,
                timestamp DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (article_id) REFERENCES articles(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB;
        ");
    }

    public static function createComment($article_id, $user_id, $message) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("INSERT INTO article_comments (article_id, user_id, message) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $article_id, $user_id, $message);
        $stmt->execute();
        return $conn->insert_id;
    }

    public static function getCommentsForArticle($article_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("SELECT id, article_id, user_id, message, timestamp FROM article_comments WHERE article_id = ? ORDER BY timestamp");
        $stmt->bind_param("i", $article_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $comments = [];
        while ($row = $result->fetch_assoc()) {
            $comments[] = new ArticleComment(
                $row['id'],
                $row['article_id'],
                $row['user_id'],
                $row['message'],
                $row['timestamp']
            );
        }
        return $comments;
    }

    public static function deleteComment($comment_id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("DELETE FROM article_comments WHERE id = ?");
        $stmt->bind_param("i", $comment_id);
        return $stmt->execute();
    }
}

// Ensure tables exist
Database::maybeCreateTables();
Article::maybeCreateArticlesTable();
ArticleComment::maybeCreateCommentsTable();
