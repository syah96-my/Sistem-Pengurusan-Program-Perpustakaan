<?php

class ScalesController {

    public static function list($pdo) {
        $stmt = $pdo->query("SELECT * FROM gm_scales ORDER BY scale_name ASC");
        Response::json(["success" => true, "scales" => $stmt->fetchAll()]);
    }

    public static function create($pdo) {
        $name = $_POST['scale_name'] ?? '';
        if (!$name) Response::json(["error" => "scale_name required"], 400);

        $stmt = $pdo->prepare("INSERT INTO gm_scales (scale_name) VALUES (?)");
        $stmt->execute([$name]);

        Response::json(["success" => true, "id" => $pdo->lastInsertId()]);
    }

    public static function update($pdo) {
        parse_str(file_get_contents("php://input"), $put);

        $id = $put['id'] ?? null;
        $name = $put['scale_name'] ?? null;

        if (!$id || !$name) Response::json(["error" => "id & scale_name required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_scales SET scale_name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);

        Response::json(["success" => true]);
    }

    public static function disable($pdo) {
        parse_str(file_get_contents("php://input"), $put);
        $id = $put['id'] ?? null;
        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_scales SET enabled = 0 WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["success" => true]);
    }

    public static function enable($pdo) {
        parse_str(file_get_contents("php://input"), $put);
        $id = $put['id'] ?? null;
        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_scales SET enabled = 1 WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["success" => true]);
    }
}
?>