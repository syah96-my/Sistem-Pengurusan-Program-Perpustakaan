<?php

class ProgramTypesController {

    public static function list($pdo) {
        $stmt = $pdo->query("SELECT * FROM gm_program_types ORDER BY id ASC");
        Response::json(["success" => true, "types" => $stmt->fetchAll()]);
    }

    public static function create($pdo) {
        $name = $_POST['type_name'] ?? '';
        if (!$name) Response::json(["error" => "type_name required"], 400);

        $stmt = $pdo->prepare("INSERT INTO gm_program_types (type_name) VALUES (?)");
        $stmt->execute([$name]);

        Response::json(["success" => true, "id" => $pdo->lastInsertId()]);
    }

    public static function update($pdo) {
        parse_str(file_get_contents("php://input"), $put);

        $id = $put['id'] ?? null;
        $name = $put['type_name'] ?? null;

        if (!$id || !$name) Response::json(["error" => "id & type_name required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_program_types SET type_name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);

        Response::json(["success" => true]);
    }

    public static function disable($pdo) {
        parse_str(file_get_contents("php://input"), $put);
        $id = $put['id'] ?? null;
        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_program_types SET enabled = 0 WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["success" => true]);
    }

    public static function enable($pdo) {
        parse_str(file_get_contents("php://input"), $put);
        $id = $put['id'] ?? null;
        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_program_types SET enabled = 1 WHERE id = ?");
        $stmt->execute([$id]);

        Response::json(["success" => true]);
    }
}
?>