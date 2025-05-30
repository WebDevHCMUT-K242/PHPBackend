<?php

include_once __DIR__ . "/db.php";

class EditableAbout {
    public static function maybeCreateTables() {
        $conn = Database::getConnection();

        $conn->query("
            CREATE TABLE IF NOT EXISTS editable_about_props (
                property VARCHAR(255) NOT NULL PRIMARY KEY,
                text_value TEXT,
                integer_value INT
            ) ENGINE=InnoDB;
        ");
        $conn->query("
            CREATE TABLE IF NOT EXISTS editable_about_contents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(255) NOT NULL,
                text TEXT NOT NULL,
                display_order INT NOT NULL
            ) ENGINE=InnoDB;
        ");

        $res = $conn->query("SELECT COUNT(*) AS count FROM editable_about_props");
        $row = $res->fetch_assoc();
        if ((int)$row['count'] === 0) {
            $stmt = $conn->prepare("INSERT INTO editable_about_props (property, text_value) VALUES (?, ?)");
            $stmt->bind_param("ss", $property, $textValue);
            $property = "title";
            $textValue = "About us";
            $stmt->execute();
            $stmt->close();
        }

        $res = $conn->query("SELECT COUNT(*) AS count FROM editable_about_contents");
        $row = $res->fetch_assoc();
        if ((int)$row['count'] === 0) {
            $stmt = $conn->prepare("INSERT INTO editable_about_contents (type, text, display_order) VALUES (?, ?, ?)");

            $type = "h2";
            $text = "Our mission";
            $order = 0;
            $stmt->bind_param("ssi", $type, $text, $order);
            $stmt->execute();

            $type = "p";
            $text = "Our mission is to redefine hardware excellence through a curated ecosystem of premium PC components. We empower builders and enthusiasts alike with scalable, high-performance solutions that push the boundaries of thermal efficiency, modularity, and overclocking potential.";
            $order = 1;
            $stmt->execute();

            $type = "p";
            $text = "By leveraging strategic vendor partnerships and just-in-time logistics, we deliver consistent value and responsiveness in a rapidly evolving tech landscape. Our commitment to supply chain agility ensures your builds stay on spec and on time.";
            $order = 2;
            $stmt->execute();

            $type = "p";
            $text = "From silicon to chassis, we believe in enabling every user—from first-time system integrators to enterprise deployment specialists—with the tools to elevate digital experiences and maximize lifecycle ROI.";
            $order = 3;
            $stmt->execute();

            $stmt->close();
        }
    }

    private static function bumpLastUpdated() {
        $conn = Database::getConnection();
        $now = time();
        $stmt = $conn->prepare("
            INSERT INTO editable_about_props (property, integer_value)
            VALUES ('last_updated', ?)
            ON DUPLICATE KEY UPDATE integer_value = VALUES(integer_value)
        ");
        $stmt->bind_param('i', $now);
        $stmt->execute();
    }

    public static function setTitle($title) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            INSERT INTO editable_about_props (property, text_value)
            VALUES ('title', ?)
            ON DUPLICATE KEY UPDATE text_value = VALUES(text_value)
        ");
        $stmt->bind_param('s', $title);
        $stmt->execute();

        self::bumpLastUpdated();
    }

    public static function insertElement($type, $text, $index) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            UPDATE editable_about_contents
            SET display_order = display_order + 1
            WHERE display_order >= ?
        ");
        $stmt->bind_param('i', $index);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO editable_about_contents (type, text, display_order)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('ssi', $type, $text, $index);
        $stmt->execute();

        self::bumpLastUpdated();
    }

    public static function removeElement($id) {
        $conn = Database::getConnection();
        $stmt = $conn->prepare("
            SELECT display_order FROM editable_about_contents WHERE id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $order = $row['display_order'];

            $stmt = $conn->prepare("
                DELETE FROM editable_about_contents WHERE id = ?
            ");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $stmt = $conn->prepare("
                UPDATE editable_about_contents
                SET display_order = display_order - 1
                WHERE display_order > ?
            ");
            $stmt->bind_param('i', $order);
            $stmt->execute();

            self::bumpLastUpdated();
        }
    }

    public static function updateElement($id, $type, $text, $newIndex) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT display_order FROM editable_about_contents WHERE id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$row = $result->fetch_assoc()) return;

        $oldIndex = $row['display_order'];

        if ($newIndex !== $oldIndex) {
            if ($newIndex > $oldIndex) {
                $stmt = $conn->prepare("
                    UPDATE editable_about_contents
                    SET display_order = display_order - 1
                    WHERE display_order > ? AND display_order <= ?
                ");
                $stmt->bind_param('ii', $oldIndex, $newIndex);
            } else {
                $stmt = $conn->prepare("
                    UPDATE editable_about_contents
                    SET display_order = display_order + 1
                    WHERE display_order >= ? AND display_order < ?
                ");
                $stmt->bind_param('ii', $newIndex, $oldIndex);
            }
            $stmt->execute();
        }

        $stmt = $conn->prepare("
            UPDATE editable_about_contents
            SET type = ?, text = ?, display_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssii', $type, $text, $newIndex, $id);
        $stmt->execute();

        self::bumpLastUpdated();
    }

    public static function buildJSON() {
        $conn = Database::getConnection();

        $titleResult = $conn->query("
            SELECT text_value FROM editable_about_props
            WHERE property = 'title'
        ");
        $titleRow = $titleResult->fetch_assoc();
        $title = $titleRow ? $titleRow['text_value'] : '';

        $updatedResult = $conn->query("
            SELECT integer_value FROM editable_about_props
            WHERE property = 'last_updated'
        ");
        $updatedRow = $updatedResult->fetch_assoc();
        $lastUpdated = $updatedRow ? (int)$updatedRow['integer_value'] : 0;

        $contentResult = $conn->query("
            SELECT id, type, text FROM editable_about_contents
            ORDER BY display_order ASC
        ");
        $contents = [];
        while ($row = $contentResult->fetch_assoc()) {
            $contents[] = [
                'id' => (int)$row['id'],
                'type' => $row['type'],
                'text' => $row['text'],
            ];
        }

        return [
            'title' => $title,
            'last_updated' => $lastUpdated,
            'contents' => $contents
        ];
    }
}
