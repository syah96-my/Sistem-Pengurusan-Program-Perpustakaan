<?php

class PlatformsController {

    public static function list($pdo) {
        $stmt = $pdo->query("SELECT * FROM gm_platforms ORDER BY platform_name ASC");
        Response::json(["success" => true, "platforms" => $stmt->fetchAll()]);
    }

    public static function create($pdo) {
        $name = $_POST['platform_name'] ?? '';
        if (!$name) Response::json(["error" => "platform_name required"], 400);

        $stmt = $pdo->prepare("INSERT INTO gm_platforms (platform_name) VALUES (?)");
        $stmt->execute([$name]);

        Response::json(["success" => true, "id" => $pdo->lastInsertId()]);
    }

    public static function update($pdo) {
        parse_str(file_get_contents("php://input"), $put);

        $id = $put['id'] ?? null;
        $name = $put['platform_name'] ?? null;

        if (!$id || !$name) Response::json(["error" => "id & platform_name required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_platforms SET platform_name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);

        Response::json(["success" => true]);
    }

    public static function disable($pdo) {
        parse_str(file_get_contents("php://input"), $put);
        $id = $put['id'] ?? null;
        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_platforms SET enabled = 0 WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["success" => true]);
    }

    public static function enable($pdo) {
        parse_str(file_get_contents("php://input"), $put);
        $id = $put['id'] ?? null;
        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_platforms SET enabled = 1 WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["success" => true]);
    }
}
?>