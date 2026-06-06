<?php

class LibraryTypesController {

    // LIST TYPES
    public static function list($pdo) {
        $stmt = $pdo->query("SELECT * FROM gm_library_types ORDER BY id ASC");
        $data = $stmt->fetchAll();

        Response::json([
            "success" => true,
            "types" => $data
        ]);
    }

    // CREATE TYPE
    public static function create($pdo) {

        $name = $_POST['type_name'] ?? '';

        if (!$name) {
            Response::json(["error" => "type_name required"], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO gm_library_types (type_name) VALUES (?)");
        $stmt->execute([$name]);

        Response::json([
            "success" => true,
            "message" => "Library type created",
            "id" => $pdo->lastInsertId()
        ]);
    }

    // UPDATE TYPE
    public static function update($pdo) {

        parse_str(file_get_contents("php://input"), $put);

        $id = $put['id'] ?? null;
        $name = $put['type_name'] ?? null;

        if (!$id || !$name) {
            Response::json(["error" => "id and type_name required"], 400);
        }

        $stmt = $pdo->prepare("UPDATE gm_library_types SET type_name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);

        Response::json([
            "success" => true,
            "message" => "Library type updated"
        ]);
    }
}
?>