<?php

class ProgramNotesController {

    /* ------------------------------------------------------------
       Helper: Read JSON or POST
    ------------------------------------------------------------ */
    private static function getInput() {
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
       CREATE NOTE
       ============================================================ */
    public static function create($pdo) {

        $input = self::getInput();

        $required = ['program_id', 'user_id', 'role', 'note_text'];

        foreach ($required as $r) {
            if (empty($input[$r])) {
                Response::json(["error" => "$r is required"], 400);
            }
        }

        // Check program exists & not is_deleted
        $stmt = $pdo->prepare("SELECT is_deleted FROM gm_programs WHERE program_id = ?");
        $stmt->execute([$input['program_id']]);
        $p = $stmt->fetch();

        if (!$p)
            Response::json(["error" => "Program not found"], 404);

        if ($p['is_deleted'] == 1)
            Response::json(["error" => "Cannot add notes to deleted program"], 400);

        // Insert note with Malaysia timezone
        $stmt = $pdo->prepare("
            INSERT INTO gm_notes (program_id, user_id, note_role, note_text, created_at, updated_at)
            VALUES (?, ?, ?, ?, 
                CONVERT_TZ(NOW(), '+00:00', '+08:00'),
                CONVERT_TZ(NOW(), '+00:00', '+08:00')
            )
        ");

        $stmt->execute([
            $input['program_id'],
            $input['user_id'],
            $input['role'],
            $input['note_text']
        ]);

        Response::json(["success" => true, "message" => "Note added"]);
    }


    /* ============================================================
       LIST NOTES
       ============================================================ */
    public static function list($pdo) {

        $program_id = $_GET['program_id'] ?? null;

        if (!$program_id)
            Response::json(["error" => "program_id required"], 400);

        $stmt = $pdo->prepare("
            SELECT 
                n.*, 
                u.username,
                DATE_FORMAT(CONVERT_TZ(n.created_at, '+00:00', '+08:00'), '%Y-%m-%d %H:%i:%s') AS created_at_my,
                DATE_FORMAT(CONVERT_TZ(n.updated_at, '+00:00', '+08:00'), '%Y-%m-%d %H:%i:%s') AS updated_at_my
            FROM gm_notes n
            LEFT JOIN gm_users u ON u.id = n.user_id
            WHERE n.program_id = ?
            ORDER BY n.created_at ASC
        ");

        $stmt->execute([$program_id]);

        Response::json([
            "success" => true,
            "notes" => $stmt->fetchAll()
        ]);
    }

}

?>
