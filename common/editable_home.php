<?php

include_once __DIR__ . "/db.php";

class EditableHome {
    public static function maybeCreateTables() {
        $conn = Database::getConnection();

        $conn->query("
            CREATE TABLE IF NOT EXISTS editable_home_contents (
                id INT AUTO_INCREMENT PRIMARY KEY,
                type VARCHAR(50) NOT NULL,  -- Loại element (vd: 'hero', 'feature-section', 'text')
                content TEXT NOT NULL,     -- Nội dung, thường là JSON cho element phức tạp
                display_order INT NOT NULL UNIQUE -- Thứ tự hiển thị, UNIQUE để đảm bảo không trùng
            ) ENGINE=InnoDB;
        ");


        $res = $conn->query("SELECT COUNT(*) AS count FROM editable_home_contents");
        $row = $res->fetch_assoc();
        if ((int)$row['count'] === 0) {
            self::addInitialContent($conn);
        }
    }

    private static function addInitialContent($conn) {
        $stmt = $conn->prepare("INSERT INTO editable_home_contents (type, content, display_order) VALUES (?, ?, ?)");

        $type = "hero";
        $content = json_encode([
            "title" => "Discover Our Exclusive Offers and Bestsellers",
            "subtitle" => "Explore our handpicked selection of top products designed to elevate your shopping experience. Don’t miss out on limited-time promotions that bring you the best value.",
            "buttons" => [
                ["text" => "Shop", "href" => "/shop", "style" => "btn-success"], 
                ["text" => "Learn More", "href" => "#", "style" => "btn-light"]
            ],
            "imageUrl" => "home-img.png" 
        ]);
        $order = 0;
        $stmt->bind_param("ssi", $type, $content, $order);
        $stmt->execute();

        $type = "feature-section";
        $content = json_encode([
            "bgColor" => "navy",
             "title" => "Experience unmatched quality and service",
             "description" => "Our commitment to fast shipping ensures that your orders arrive on time, every time. We source only high-quality products, so you can shop with confidence. With our dedicated customer service team, your satisfaction is our top priority.",
            "features" => [
                ["title" => "Fast Shipping to Your Doorstep", "text" => "Get your orders delivered swiftly and reliably."],
                ["title" => "Top-Quality Products You Can Trust", "text" => "We offer only the best for our customers."],
                ["title" => "Exceptional Customer Service, Always Here For You", "text" => "Our team is ready to assist you 24/7."]
            ]
        ]);
        $order = 1;
        $stmt->bind_param("ssi", $type, $content, $order);
        $stmt->execute();

        $type = "content-image-split";
        $content = json_encode([
            "bgColor" => "black",
            "title" => "Discover Our Best-Selling Products That Customers Can't Get Enough Of!",
            "text" => "Explore our curated selection of top-selling items that combine quality and value. Each product is designed to meet your needs and enhance your shopping experience.",
            "imageUrl" => "home-img.png"
        ]);
        $order = 2;
        $stmt->bind_param("ssi", $type, $content, $order);
        $stmt->execute();

        $stmt->close();
    }



    public static function insertElement($type, $content_json, $index) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            UPDATE editable_home_contents
            SET display_order = display_order + 1
            WHERE display_order >= ?
        ");
        $stmt->bind_param('i', $index);
        $stmt->execute();

        $stmt = $conn->prepare("
            INSERT INTO editable_home_contents (type, content, display_order)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param('ssi', $type, $content_json, $index);
        $stmt->execute();

    }
    public static function removeElement($id) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT display_order FROM editable_home_contents WHERE id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $order = $row['display_order'];

            $stmt = $conn->prepare("
                DELETE FROM editable_home_contents WHERE id = ?
            ");
            $stmt->bind_param('i', $id);
            $stmt->execute();

            $stmt = $conn->prepare("
                UPDATE editable_home_contents
                SET display_order = display_order - 1
                WHERE display_order > ?
            ");
            $stmt->bind_param('i', $order);
            $stmt->execute();

        }
    }

    public static function updateElement($id, $type, $content_json, $newIndex) {
        $conn = Database::getConnection();

        $stmt = $conn->prepare("
            SELECT display_order FROM editable_home_contents WHERE id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$row = $result->fetch_assoc()) return; 

        $oldIndex = (int)$row['display_order'];
        $newIndex = (int)$newIndex;

        if ($newIndex !== $oldIndex) {
            $tempOrder = -1;
            $stmt = $conn->prepare("UPDATE editable_home_contents SET display_order = ? WHERE id = ?");
            $stmt->bind_param('ii', $tempOrder, $id);
            $stmt->execute();

            if ($newIndex > $oldIndex) {
                $stmt = $conn->prepare("
                    UPDATE editable_home_contents
                    SET display_order = display_order - 1
                    WHERE display_order > ? AND display_order <= ?
                ");
                $stmt->bind_param('ii', $oldIndex, $newIndex);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("
                    UPDATE editable_home_contents
                    SET display_order = display_order + 1
                    WHERE display_order >= ? AND display_order < ?
                ");
                 $stmt->bind_param('ii', $newIndex, $oldIndex);
                 $stmt->execute();
            }
        }

        $stmt = $conn->prepare("
            UPDATE editable_home_contents
            SET type = ?, content = ?, display_order = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssii', $type, $content_json, $newIndex, $id);
        $stmt->execute();

    }

    public static function buildJSON() {
        $conn = Database::getConnection();


        $contentResult = $conn->query("
            SELECT id, type, content FROM editable_home_contents
            ORDER BY display_order ASC
        ");
        $elements = [];
        while ($row = $contentResult->fetch_assoc()) {
            $contentDecoded = json_decode($row['content'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $contentDecoded = $row['content'];
            }

            $elements[] = [
                'id' => (int)$row['id'],
                'type' => $row['type'],
                'content' => $contentDecoded, 
            ];
        }

        return [
            'elements' => $elements
        ];
    }
}