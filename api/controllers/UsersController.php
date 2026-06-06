<?php

class UsersController {

    /* -------------------------------------------------------------
        Helper: JSON + POST reader
    ------------------------------------------------------------- */
    private static function input()
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

    private static function defaultPassword()
    {
        return getenv('GM_DEFAULT_USER_PASSWORD') ?: 'password';
    }


    /* =============================================================
        LIST — DataTables
    ============================================================= */
    public static function datatables($pdo)
    {
        $draw   = $_GET['draw'] ?? 1;
        $start  = $_GET['start'] ?? 0;
        $length = $_GET['length'] ?? 10;
        $search = $_GET['search']['value'] ?? "";

        $type_id   = $_GET['type_id']   ?? null;
        $parent_id = $_GET['parent_id'] ?? null;
        $status    = $_GET['status']    ?? null;

        $where = " WHERE 1 ";
        $values = [];

        // FILTER: library type
        if ($type_id) {
            $where .= " AND l.type_id = ? ";
            $values[] = $type_id;
        }

        // FILTER: parent library
        $isHQ = ($_SESSION['user_id'] ?? 0) == 1;
        
        // FILTER: parent library
        if ($parent_id && !($isHQ && $type_id <= 2)) {
            $where .= " AND l.parent_id = ? ";
            $values[] = $parent_id;
        }


        // FILTER: status
        if ($status !== null && $status !== "") {
            $where .= " AND u.status = ? ";
            $values[] = $status;
        }

        // SEARCH
        if ($search !== "") {
            $where .= " AND (u.username LIKE ? OR l.name LIKE ? OR r.role_name LIKE ?)";
            $kw = "%$search%";
            $values[] = $kw;
            $values[] = $kw;
            $values[] = $kw;
        }

        // Count total
        $recordsTotal = $pdo->query("SELECT COUNT(*) FROM gm_users")->fetchColumn();

        // Count filtered
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM gm_users u
            LEFT JOIN gm_libraries l ON l.id = u.library_id
            LEFT JOIN gm_roles r ON r.id = u.role_id
            $where
        ");
        $stmt->execute($values);
        $recordsFiltered = $stmt->fetchColumn();

        // Ordering
        $columns = ["u.id", "u.username", "l.name", "r.role_name", "u.status", "u.created_at"];
        $orderSql = "";
        if (!empty($_GET["order"][0]["column"])) {
            $col = intval($_GET["order"][0]["column"]);
            $dir = ($_GET["order"][0]["dir"] === "desc") ? "DESC" : "ASC";
            if (isset($columns[$col])) {
                $orderSql = " ORDER BY {$columns[$col]} $dir ";
            }
        }

        // Fetch data (NO PASSWORD)
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.username,
                u.library_id,
                u.role_id,
                u.status,
                u.created_at,
                l.name AS library_name,
                l.type_id AS library_type_id,
                r.role_name
            FROM gm_users u
            LEFT JOIN gm_libraries l ON l.id = u.library_id
            LEFT JOIN gm_roles r ON r.id = u.role_id
            $where
            $orderSql
            LIMIT $start, $length
        ");
        $stmt->execute($values);
        $data = $stmt->fetchAll();

        Response::json([
            "draw"            => intval($draw),
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data"            => $data
        ]);
    }




    /* =============================================================
        CREATE USER
    ============================================================= */
    public static function create($pdo)
    {
        $in = self::input();

        $username   = $in['username']   ?? null;
        $library_id = $in['library_id'] ?? null;
        $role_id    = $in['role_id']    ?? null;
        $status     = $in['status']     ?? "active";

        if (!$username || !$library_id || !$role_id) {
            Response::json(["error" => "username, library_id, role_id required"], 400);
        }

        // unique username
        $check = $pdo->prepare("SELECT id FROM gm_users WHERE username=?");
        $check->execute([$username]);
        if ($check->fetch()) {
            Response::json(["error" => "Username already exists"], 400);
        }

        $passwordHash = password_hash(self::defaultPassword(), PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO gm_users (username,password,library_id,role_id,status)
            VALUES (?,?,?,?,?)
        ");
        $stmt->execute([
            $username,
            $passwordHash,
            $library_id,
            $role_id,
            $status
        ]);

        Response::json([
            "success" => true,
            "message" => "User created",
            "user_id" => $pdo->lastInsertId()
        ]);
    }




    /* =============================================================
        UPDATE USER — FULLY FIXED
    ============================================================= */
    public static function update($pdo)
    {
        $in = self::input();
    
        $id         = $in['id']         ?? null;
        $username   = $in['username']   ?? null;
        $library_id = $in['library_id'] ?? null;
        $role_id    = $in['role_id']    ?? null;
        $status     = $in['status']     ?? "active";  // default active
    
        if (!$id) {
            Response::json(["error" => "id required"], 400);
        }
        
        $id = $in['id'] ?? null;
        
        // Protect system user.
        if ((int)$id === 1) {
            Response::json([
                "error" => "System user cannot change role or library"
            ], 403);
        }
    
        if (!$username || !$library_id || !$role_id) {
            Response::json(["error" => "username, library_id, role_id required"], 400);
        }
    
        // Check for duplicate username (only other users)
        $check = $pdo->prepare("SELECT id FROM gm_users WHERE username=? AND id<>?");
        $check->execute([$username, $id]);
        if ($check->fetch()) {
            Response::json(["error" => "Username already taken"], 400);
        }
    
        // Update user
        $stmt = $pdo->prepare("
            UPDATE gm_users
            SET username=?, library_id=?, role_id=?, status=?
            WHERE id=?
        ");
        $stmt->execute([$username, $library_id, $role_id, $status, $id]);
    
        Response::json([
            "success" => true,
            "message" => "User updated"
        ]);
    }





    /* =============================================================
        ACTIVATE
    ============================================================= */
    public static function activate($pdo)
    {
        $in = self::input();
        $id = $in['id'] ?? null;

        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_users SET status='active' WHERE id=?");
        $stmt->execute([$id]);

        Response::json(["success" => true, "message" => "User activated"]);
    }


    /* =============================================================
        DEACTIVATE
    ============================================================= */
    public static function deactivate($pdo)
    {
        $in = self::input();
        $id = $in['id'] ?? null;
        // Protect system user.
        if ((int)$id === 1) {
            Response::json([
                "error" => "System user cannot be deactivated"
            ], 403);
        }


        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("UPDATE gm_users SET status='inactive' WHERE id=?");
        $stmt->execute([$id]);

        Response::json(["success" => true, "message" => "User deactivated"]);
    }




    /* =============================================================
        RESET PASSWORD
    ============================================================= */
    public static function reset_password($pdo)
    {
        $in = self::input();
        $id = $in['id'] ?? null;

        if (!$id) Response::json(["error" => "id required"], 400);

        $defaultPassword = self::defaultPassword();
        $hash = password_hash($defaultPassword, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE gm_users SET password=? WHERE id=?");
        $stmt->execute([$hash, $id]);

        Response::json([
            "success" => true,
            "message" => "Password reset to default password"
        ]);
    }




    /* =============================================================
        DELETE USER
    ============================================================= */
    public static function delete($pdo)
    {
        $in = self::input();
        $id = $in['id'] ?? null;
        
        // Protect system user.
        if ((int)$id === 1) {
            Response::json([
                "error" => "System user cannot be deleted"
            ], 403);
        }

        if (!$id) Response::json(["error" => "id required"], 400);

        $stmt = $pdo->prepare("DELETE FROM gm_users WHERE id=?");
        $stmt->execute([$id]);

        Response::json([
            "success" => true,
            "message" => "User deleted"
        ]);
    }




    /* =============================================================
        BULK IMPORT USERS
    ============================================================= */
    public static function bulk_import($pdo)
    {
        $in = self::input();

        if (empty($in['users']) || !is_array($in['users'])) {
            Response::json(["error" => "users[] required"], 400);
        }

        $inserted = 0;
        $skipped  = 0;
        $errors   = [];

        $defaultPass = password_hash(self::defaultPassword(), PASSWORD_DEFAULT);

        foreach ($in['users'] as $i => $u) {

            $username   = $u['username']   ?? null;
            $library_id = $u['library_id'] ?? null;
            $role_id    = $u['role_id']    ?? null;
            $status     = $u['status']     ?? "active";

            if (!$username || !$library_id || !$role_id) {
                $errors[] = ["row" => $i+1, "error" => "Missing username/library/role"];
                $skipped++;
                continue;
            }

            // duplicate username
            $check = $pdo->prepare("SELECT id FROM gm_users WHERE username=?");
            $check->execute([$username]);
            if ($check->fetch()) {
                $errors[] = ["row" => $i+1, "error" => "Duplicate username"];
                $skipped++;
                continue;
            }

            // insert
            $stmt = $pdo->prepare("
                INSERT INTO gm_users (username,password,library_id,role_id,status)
                VALUES (?,?,?,?,?)
            ");
            $stmt->execute([$username, $defaultPass, $library_id, $role_id, $status]);

            $inserted++;
        }

        Response::json([
            "success" => true,
            "inserted" => $inserted,
            "skipped" => $skipped,
            "errors" => $errors
        ]);
    }
        /* =============================================================
        Single User
    ============================================================= */
    public static function get($pdo)
{
    $id = $_GET['id'] ?? null;

    if (!$id) {
        Response::json(["error" => "id required"], 400);
    }

    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.library_id,
            u.role_id,
            u.status,
            u.created_at,
            l.name AS library_name,
            l.type_id AS library_type_id,
            r.role_name
        FROM gm_users u
        LEFT JOIN gm_libraries l ON l.id = u.library_id
        LEFT JOIN gm_roles r ON r.id = u.role_id
        WHERE u.id = ?
    ");
    $stmt->execute([$id]);
    $data = $stmt->fetch();

    if (!$data) {
        Response::json(["error" => "User not found"], 404);
    }

    Response::json([
        "success" => true,
        "user" => $data
    ]);
}

}

?>
