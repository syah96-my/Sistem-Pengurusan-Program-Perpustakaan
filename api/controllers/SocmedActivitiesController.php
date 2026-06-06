<?php

class SocmedActivitiesController {

    /* ============================================================
       INPUT HELPER (POST + JSON)
    ============================================================ */
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

    /* ============================================================
       CREATE
    ============================================================ */
    public static function create($pdo)
    {
        $input = self::getInput();

        $required = [
            "library_id",
            "program_type_id",
            "platform_id",
            "activity_title",
            "activity_date",
            "user_id"
        ];

        foreach ($required as $r) {
            if (empty($input[$r])) {
                Response::json(["error" => "$r is required"], 400);
                return;
            }
        }

        $library = LibraryHierarchy::getLibrary($pdo, $input["library_id"]);
        if (!$library) {
            Response::json(["error" => "Invalid library_id"], 400);
            return;
        }

        $stmt = $pdo->prepare("
            INSERT INTO gm_socmed_activities (
                parent_library_id,
                library_type_id,
                library_id,
                program_type_id,
                platform_id,
                activity_title,
                activity_description,
                post_url,
                reach_estimate,
                engagement_estimate,
                activity_date,
                created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $library["parent_id"] ?: null,
            $library["type_id"],
            $input["library_id"],
            $input["program_type_id"],
            $input["platform_id"],
            $input["activity_title"],
            $input["activity_description"] ?? null,
            $input["post_url"] ?? null,
            $input["reach_estimate"] ?? null,
            $input["engagement_estimate"] ?? null,
            $input["activity_date"],
            $input["user_id"]
        ]);

        Response::json([
            "success" => true,
            "activity_id" => $pdo->lastInsertId()
        ]);
    }

    /* ============================================================
       UPDATE
    ============================================================ */
    public static function update($pdo)
    {
        $input = self::getInput();

        if (empty($input["activity_id"])) {
            Response::json(["error" => "activity_id required"], 400);
            return;
        }

        $library = LibraryHierarchy::getLibrary($pdo, $input["library_id"]);
        if (!$library) {
            Response::json(["error" => "Invalid library_id"], 400);
            return;
        }

        $stmt = $pdo->prepare("
            UPDATE gm_socmed_activities SET
                parent_library_id    = ?,
                library_type_id      = ?,
                library_id           = ?,
                program_type_id      = ?,
                platform_id          = ?,
                activity_title       = ?,
                activity_description = ?,
                post_url             = ?,
                reach_estimate       = ?,
                engagement_estimate  = ?,
                activity_date        = ?,
                updated_at           = NOW()
            WHERE activity_id = ?
              AND is_deleted = 0
        ");

        $stmt->execute([
            $library["parent_id"] ?: null,
            $library["type_id"],
            $input["library_id"],
            $input["program_type_id"],
            $input["platform_id"],
            $input["activity_title"],
            $input["activity_description"] ?? null,
            $input["post_url"] ?? null,
            $input["reach_estimate"] ?? null,
            $input["engagement_estimate"] ?? null,
            $input["activity_date"],
            $input["activity_id"]
        ]);

        Response::json(["success" => true]);
    }

    /* ============================================================
       DELETE (SOFT)
    ============================================================ */
    public static function delete($pdo)
    {
        $input = self::getInput();

        if (empty($input["activity_id"])) {
            Response::json(["error" => "activity_id required"], 400);
            return;
        }

        $pdo->prepare("
            UPDATE gm_socmed_activities
            SET is_deleted = 1,
                updated_at = NOW()
            WHERE activity_id = ?
        ")->execute([
            $input["activity_id"]
        ]);

        Response::json(["success" => true]);
    }

    /* ============================================================
       VIEW (SINGLE)
    ============================================================ */
    public static function view($pdo)
    {
        if (empty($_GET["activity_id"])) {
            Response::json(["error" => "activity_id required"], 400);
            return;
        }

        $stmt = $pdo->prepare("
            SELECT *
            FROM gm_socmed_activities
            WHERE activity_id = ?
              AND is_deleted = 0
        ");
        $stmt->execute([$_GET["activity_id"]]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            Response::json(["error" => "Not found"], 404);
            return;
        }

        Response::json(["success" => true, "data" => $row]);
    }

    /* ============================================================
       LIST + FILTERS
    ============================================================ */
    public static function listAll($pdo)
    {
        $where  = " WHERE a.is_deleted = 0 ";
        $params = [];

        /* ===============================
        FILTERS
        =============================== */

        if (!empty($_GET['library_id'])) {
            $where .= " AND a.library_id = ? ";
            $params[] = $_GET['library_id'];
        }

        if (!empty($_GET['platform_id'])) {
            $where .= " AND a.platform_id = ? ";
            $params[] = $_GET['platform_id'];
        }

        if (!empty($_GET['program_type_id'])) {
            $where .= " AND a.program_type_id = ? ";
            $params[] = $_GET['program_type_id'];
        }

        if (!empty($_GET['year'])) {
            $where .= " AND YEAR(a.activity_date) = ? ";
            $params[] = $_GET['year'];
        }

        /* ===============================
        MAIN QUERY
        =============================== */
        $sql = "
            SELECT
                a.activity_id,
                a.activity_title,
                a.activity_date,
                a.post_url,
                a.reach_estimate,
                a.engagement_estimate,
                p.platform_name
            FROM gm_socmed_activities a
            LEFT JOIN gm_platforms p ON p.id = a.platform_id
            $where
            ORDER BY a.activity_date DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        Response::json([
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }


}
?>
