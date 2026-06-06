<?php

class LibrariesController {

    /* ---------------------------------------------
       Helper: read JSON or POST
    --------------------------------------------- */
    private static function getInput()
    {
        $input = $_POST;

        if (empty($input)) {
            $raw = file_get_contents("php://input");
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $json;
            }
        }
        return $input;
    }

    // GET: list parent libraries
    public static function list($pdo)
    {
        $stmt = $pdo->query("SELECT * FROM gm_libraries WHERE parent_id IS NULL");
        $data = $stmt->fetchAll();

        Response::json([
            "success" => true,
            "libraries" => $data
        ]);
    }

    // POST: create new parent library
    public static function create($pdo)
    {
        $input = self::getInput();

        $name = $input['name'] ?? '';
        $type_id = $input['type_id'] ?? null;
        $address = $input['address'] ?? '';

        if (!$name || !$type_id) {
            Response::json(["error" => "name and type_id required"], 400);
        }

        $stmt = $pdo->prepare("
            INSERT INTO gm_libraries (name, type_id, parent_id, address)
            VALUES (?, ?, NULL, ?)
        ");

        $stmt->execute([$name, $type_id, $address]);

        Response::json([
            "success" => true,
            "message" => "Library created",
            "library_id" => $pdo->lastInsertId()
        ]);
    }

    // PUT: update parent library
    public static function update($pdo)
    {
        $input = self::getInput();

        $id = $input['id'] ?? null;
        $name = $input['name'] ?? null;
        $type_id = $input['type_id'] ?? null;
        $address = $input['address'] ?? null;

        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_libraries
            SET name = ?, type_id = ?, address = ?
            WHERE id = ? AND parent_id IS NULL
        ");

        $stmt->execute([$name, $type_id, $address, $id]);

        Response::json([
            "success" => true,
            "message" => "Library updated"
        ]);
    }

    // DELETE: delete parent library
    public static function delete($pdo)
    {
        $input = self::getInput();

        $id = $input['id'] ?? null;

        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }

        $stmt = $pdo->prepare("
            DELETE FROM gm_libraries 
            WHERE id = ? AND parent_id IS NULL
        ");

        $stmt->execute([$id]);

        Response::json([
            "success" => true,
            "message" => "Library deleted"
        ]);
    }

    // PUT: deactivate parent library
    public static function deactivate($pdo)
    {
        $input = self::getInput();

        $id = $input['id'] ?? null;

        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_libraries 
            SET status = 'inactive' 
            WHERE id = ? AND parent_id IS NULL
        ");

        $stmt->execute([$id]);

        Response::json([
            "success" => true,
            "message" => "Library deactivated"
        ]);
    }

    // PUT: activate parent library
    public static function activate($pdo)
    {
        $input = self::getInput();

        $id = $input['id'] ?? null;

        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_libraries 
            SET status = 'active' 
            WHERE id = ? AND parent_id IS NULL
        ");

        $stmt->execute([$id]);

        Response::json([
            "success" => true,
            "message" => "Library activated"
        ]);
    }
    
    // GET: list ALL libraries (parent + child) with filters
public static function get_all($pdo)
{
    $parent_id = $_GET['parent_id'] ?? null;
    $type_id   = $_GET['type_id'] ?? null;

    $where = " WHERE 1 ";
    $values = [];

    // Filter by parent library
    if (!empty($parent_id)) {
        $where .= " AND l.parent_id = ? ";
        $values[] = $parent_id;
    }

    // Filter by type_id
    if (!empty($type_id)) {
        $where .= " AND l.type_id = ? ";
        $values[] = $type_id;
    }

    // Final SQL
    $stmt = $pdo->prepare("
        SELECT 
            l.id,
            l.name,
            l.type_id,
            t.type_name,
            l.parent_id,
            p.name AS parent_name,
            l.address,
            l.status
        FROM gm_libraries l
        LEFT JOIN gm_library_types t ON t.id = l.type_id
        LEFT JOIN gm_libraries p ON p.id = l.parent_id
        $where
        ORDER BY l.name ASC
    ");

    $stmt->execute($values);

    Response::json([
        "success" => true,
        "libraries" => $stmt->fetchAll()
    ]);
}


// GET: list child libraries for a parent
public static function get_child_library($pdo)
{
    $parentId = $_GET['parent_id'] ?? null;

    if (!$parentId) {
        Response::json([
            "success" => true,
            "libraries" => []
        ]);
        return;
    }

    $stmt = $pdo->prepare("
        SELECT
            l.id,
            l.name,
            l.type_id,
            t.type_name,
            l.parent_id,
            p.name AS parent_name,
            l.address,
            l.status
        FROM gm_libraries l
        LEFT JOIN gm_library_types t ON t.id = l.type_id
        LEFT JOIN gm_libraries p ON p.id = l.parent_id
        WHERE l.parent_id = ?
           OR l.id = ?
        ORDER BY l.name ASC
    ");

    $stmt->execute([$parentId, $parentId]);

    Response::json([
        "success" => true,
        "libraries" => $stmt->fetchAll()
    ]);
}



}

?>
