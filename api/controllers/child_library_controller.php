<?php

class ChildLibraryController {

    // LIST CHILD LIBRARIES
    public static function list($pdo) {

        $parent_id = $_GET['parent_id'] ?? null;

        if (!$parent_id) {
            Response::json(["error" => "parent_id required"], 400);
        }

        $stmt = $pdo->prepare("
            SELECT * FROM gm_libraries
            WHERE parent_id = ? 
            ORDER BY name ASC
        ");
        $stmt->execute([$parent_id]);
        $data = $stmt->fetchAll();

        Response::json([
            "success" => true,
            "children" => $data
        ]);
    }


    // CREATE CHILD LIBRARY
    public static function create($pdo) {
    
        // Read JSON if POST is empty
        $input = $_POST;
        if (empty($input)) {
            $raw = file_get_contents("php://input");
            $json = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $input = $json;
            }
        }
    
        $name      = $input['name'] ?? '';
        $type_id   = $input['type_id'] ?? null;
        $address   = $input['address'] ?? '';
        $parent_id = $input['parent_id'] ?? null;
    
        if (!$name || !$type_id || !$parent_id) {
            Response::json(["error" => "name, type_id, parent_id required"], 400);
        }

        $canCreate = LibraryHierarchy::assertCanCreateChild($pdo, $parent_id);
        if ($canCreate !== true) {
            Response::json(["error" => $canCreate], 400);
        }
    
        $stmt = $pdo->prepare("
            INSERT INTO gm_libraries (name, type_id, parent_id, address, status)
            VALUES (?, ?, ?, ?, 'active')
        ");
    
        $stmt->execute([$name, $type_id, $parent_id, $address]);
    
        Response::json([
            "success" => true,
            "message" => "Child library created",
            "library_id" => $pdo->lastInsertId()
        ]);
    }



    // UPDATE CHILD LIBRARY
    public static function update($pdo) {

        parse_str(file_get_contents("php://input"), $put);

        $id = $put['id'] ?? null;
        $name = $put['name'] ?? null;
        $type_id = $put['type_id'] ?? null;
        $address = $put['address'] ?? null;

        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_libraries
            SET name = ?, type_id = ?, address = ?
            WHERE id = ? AND parent_id IS NOT NULL
        ");

        $stmt->execute([$name, $type_id, $address, $id]);

        Response::json([
            "success" => true,
            "message" => "Child library updated"
        ]);
    }


    // DEACTIVATE CHILD LIBRARY (SOFT DELETE)
    public static function deactivate($pdo) {

        parse_str(file_get_contents("php://input"), $put);

        $id = $put['id'] ?? null;

        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_libraries
            SET status = 'inactive'
            WHERE id = ? AND parent_id IS NOT NULL
        ");

        $stmt->execute([$id]);

        Response::json([
            "success" => true,
            "message" => "Child library deactivated"
        ]);
    }


    // ACTIVATE CHILD LIBRARY
    public static function activate($pdo) {

        parse_str(file_get_contents("php://input"), $put);

        $id = $put['id'] ?? null;

        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_libraries
            SET status = 'active'
            WHERE id = ? AND parent_id IS NOT NULL
        ");

        $stmt->execute([$id]);

        Response::json([
            "success" => true,
            "message" => "Child library activated"
        ]);
    }
    
    
    // ===============================================
// DATATABLES SERVER-SIDE LIST
// ===============================================
   public static function datatables($pdo)
{
    $draw   = $_GET["draw"] ?? 1;
    $start  = $_GET["start"] ?? 0;
    $length = $_GET["length"] ?? 10;
    $search = $_GET["search"]["value"] ?? "";

    $parent_id = $_GET["parent_id"] ?? null;
    if (!$parent_id) {
        Response::json(["error" => "parent_id required"], 400);
    }

    /* ============================
       BASE WHERE CLAUSE
    ============================ */
    $where = " WHERE l.parent_id = ? ";
    $values = [$parent_id];

    /* ============================
       OPTIONAL type_id FILTER
    ============================ */
    if (!empty($_GET["type_id"])) {
        $where .= " AND l.type_id = ? ";
        $values[] = $_GET["type_id"];
    }

    /* ============================
       OPTIONAL status FILTER
    ============================ */
    if (!empty($_GET["status"])) {
        $where .= " AND l.status = ? ";
        $values[] = $_GET["status"];
    }

    /* ============================
       SEARCH FILTER
    ============================ */
    if (!empty($search)) {
        $kw = "%$search%";
        $where .= "
            AND (
                l.name LIKE ?
                OR l.address LIKE ?
                OR t.type_name LIKE ?
            )
        ";
        $values[] = $kw;
        $values[] = $kw;
        $values[] = $kw;
    }

    /* ============================
       TOTAL COUNT (ONLY PARENT)
    ============================ */
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM gm_libraries WHERE parent_id = ?");
    $stmt->execute([$parent_id]);
    $recordsTotal = $stmt->fetchColumn();

    /* ============================
       FILTERED COUNT
    ============================ */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM gm_libraries l
        LEFT JOIN gm_library_types t ON t.id = l.type_id
        $where
    ");
    $stmt->execute($values);
    $recordsFiltered = $stmt->fetchColumn();

    /* ============================
       ORDERING
    ============================ */
    $columns = ["l.id", "l.name", "t.type_name", "l.address", "l.status"];
    $orderSql = "";

    if (!empty($_GET["order"][0]["column"])) {
        $i = intval($_GET["order"][0]["column"]);
        $dir = ($_GET["order"][0]["dir"] === "desc") ? "DESC" : "ASC";
        if (isset($columns[$i])) {
            $orderSql = " ORDER BY {$columns[$i]} $dir ";
        }
    }

    /* ============================
       PAGE RESULTS
    ============================ */
    $stmt = $pdo->prepare("
        SELECT 
            l.*,
            t.type_name
        FROM gm_libraries l
        LEFT JOIN gm_library_types t ON t.id = l.type_id
        $where
        $orderSql
        LIMIT $start, $length
    ");
    $stmt->execute($values);
    $data = $stmt->fetchAll();

    /* ============================
       OUTPUT
    ============================ */
    Response::json([
        "draw" => intval($draw),
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        "data" => $data
    ]);
}

    
    
    // ======================================================================
// BULK IMPORT CHILD LIBRARIES
// ======================================================================
    public static function bulk_import($pdo)
    {
        $input = json_decode(file_get_contents("php://input"), true);
    
        if (!$input || !isset($input['parent_id']) || !isset($input['libraries'])) {
            Response::json(["error" => "parent_id and libraries[] required"], 400);
        }
    
        $parent_id = $input['parent_id'];
        $rows = $input['libraries'];
    
        if (!is_array($rows)) {
            Response::json(["error" => "libraries must be an array"], 400);
        }

        $canCreate = LibraryHierarchy::assertCanCreateChild($pdo, $parent_id);
        if ($canCreate !== true) {
            Response::json(["error" => $canCreate], 400);
        }
    
        $inserted = 0;
        $skipped = 0;
        $errors = [];
    
        $pdo->beginTransaction();
    
        try {
            // Prepare statements
            $checkDup = $pdo->prepare("
                SELECT id FROM gm_libraries 
                WHERE parent_id = ? AND name = ?
            ");
    
            $insert = $pdo->prepare("
                INSERT INTO gm_libraries (name, type_id, parent_id, address, status)
                VALUES (?, ?, ?, ?, 'active')
            ");
    
            foreach ($rows as $index => $row) {
    
                $name = trim($row['name'] ?? '');
                $type_id = $row['type_id'] ?? null;
                $address = $row['address'] ?? '';
    
                // Validate
                if (!$name || !$type_id) {
                    $errors[] = "Row $index: missing name/type_id";
                    $skipped++;
                    continue;
                }
    
                // Skip duplicate name under same parent
                $checkDup->execute([$parent_id, $name]);
                if ($checkDup->fetch()) {
                    $skipped++;
                    continue;
                }
    
                // Insert
                try {
                    $insert->execute([$name, $type_id, $parent_id, $address]);
                    $inserted++;
                } catch (Exception $e) {
                    $errors[] = "Row $index: " . $e->getMessage();
                    $skipped++;
                }
            }
    
            $pdo->commit();
    
        } catch (Exception $e) {
            $pdo->rollBack();
            Response::json([
                "error" => "Bulk import failed",
                "details" => $e->getMessage()
            ], 500);
        }
    
        Response::json([
            "success" => true,
            "message" => "Bulk import completed",
            "inserted" => $inserted,
            "skipped" => $skipped,
            "errors" => $errors
        ]);
    }


}
?>
