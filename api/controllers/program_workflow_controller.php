<?php

class ProgramWorkflowController {

    /* ============================================================
       UNIVERSAL INPUT HANDLER (JSON + POST)
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
       VERIFY (Single)
    ============================================================ */
    public static function verify($pdo)
    {
        $input = self::getInput();

        foreach (['program_id', 'user_id'] as $r)
            if (empty($input[$r])) Response::json(["error" => "$r is required"], 400);

        $stmt = $pdo->prepare("SELECT verification_status AS status FROM gm_programs WHERE program_id=? AND is_deleted=0");
        $stmt->execute([$input['program_id']]);
        $p = $stmt->fetch();

        if (!$p) Response::json(["error" => "Program not found"], 404);
        if ($p['status'] === 'verified')
            Response::json(["error" => "Already verified"], 400);
        if (!LibraryHierarchy::canVerifyProgram($pdo, $input['user_id'], $input['program_id'])) {
            Response::json(["error" => "Only the immediate parent library can verify this program"], 403);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_programs 
            SET verification_status='verified', verified_by=?, verified_at=NOW(), 
                rejected_by=NULL, rejected_at=NULL
            WHERE program_id=?
        ");
        $stmt->execute([$input['user_id'], $input['program_id']]);

        // Log note
        $stmt = $pdo->prepare("
            INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
            VALUES (?, ?, 'verifier', 'Program verified')
        ");
        $stmt->execute([$input['program_id'], $input['user_id']]);

        Response::json(["success" => true, "message" => "Program verified"]);
    }



    /* ============================================================
       REJECT (Single)
    ============================================================ */
    public static function reject($pdo)
    {
        $input = self::getInput();

        foreach (['program_id', 'user_id', 'reason'] as $r)
            if (empty($input[$r])) Response::json(["error" => "$r is required"], 400);

        $stmt = $pdo->prepare("SELECT verification_status AS status FROM gm_programs WHERE program_id=? AND is_deleted=0");
        $stmt->execute([$input['program_id']]);
        $p = $stmt->fetch();

        if (!$p) Response::json(["error" => "Program not found"], 404);
        if (!LibraryHierarchy::canVerifyProgram($pdo, $input['user_id'], $input['program_id'])) {
            Response::json(["error" => "Only the immediate parent library can reject this program"], 403);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_programs 
            SET verification_status='rejected', rejected_by=?, rejected_at=NOW(),
                verified_by=NULL, verified_at=NULL
            WHERE program_id=?
        ");
        $stmt->execute([$input['user_id'], $input['program_id']]);

        // Log rejection reason
        $stmt = $pdo->prepare("
            INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
            VALUES (?, ?, 'reject', ?)
        ");
        $stmt->execute([$input['program_id'], $input['user_id'], $input['reason']]);

        Response::json(["success" => true, "message" => "Program rejected"]);
    }

    /* ============================================================
    DELETE (Single — Workflow)
    ============================================================ */
    public static function delete($pdo)
    {
        $input = self::getInput();

        foreach (['program_id', 'user_id', 'reason'] as $r) {
            if (empty($input[$r])) {
                Response::json(["error" => "$r is required"], 400);
            }
        }

        // Check program exists and is not already deleted
        $stmt = $pdo->prepare("
            SELECT is_deleted 
            FROM gm_programs 
            WHERE program_id = ?
        ");
        $stmt->execute([$input['program_id']]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$p) {
            Response::json(["error" => "Program not found"], 404);
        }

        if ((int)$p['is_deleted'] === 1) {
            Response::json(["error" => "Program already deleted"], 400);
        }

        // Soft delete
        $stmt = $pdo->prepare("
            UPDATE gm_programs
            SET is_deleted = 1,
                deleted_by = ?,
                deleted_at = CONVERT_TZ(NOW(), '+00:00', '+08:00'),
                verified_by = NULL,
                verified_at = NULL,
                rejected_by = NULL,
                rejected_at = NULL
            WHERE program_id = ?
        ");
        $stmt->execute([
            $input['user_id'],
            $input['program_id']
        ]);

        // Log delete reason (audit trail)
        $stmt = $pdo->prepare("
            INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
            VALUES (?, ?, 'remove', ?)
        ");
        $stmt->execute([
            $input['program_id'],
            $input['user_id'],
            '' . $input['reason']
        ]);

        Response::json([
            "success" => true,
            "message" => "Program deleted"
        ]);
    }


    /* ============================================================
       RESET (Single)
    ============================================================ */
    public static function reset($pdo)
    {
        $input = self::getInput();

        foreach (['program_id', 'user_id'] as $r)
            if (empty($input[$r])) Response::json(["error" => "$r is required"], 400);

        $stmt = $pdo->prepare("
            SELECT r.role_name AS role
            FROM gm_users u
            JOIN gm_roles r ON r.id = u.role_id
            WHERE u.id=?
        ");
        $stmt->execute([$input['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !in_array($user['role'], ['admin', 'super_admin']))
            Response::json(["error" => "Only admin can reset workflow"], 403);

        $stmt = $pdo->prepare("
            UPDATE gm_programs
            SET verification_status='pending', verified_by=NULL, verified_at=NULL,
                rejected_by=NULL, rejected_at=NULL
            WHERE program_id=?
        ");
        $stmt->execute([$input['program_id']]);

        // Log reset
        $stmt = $pdo->prepare("
            INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
            VALUES (?, ?, 'admin', 'Workflow reset to pending')
        ");
        $stmt->execute([$input['program_id'], $input['user_id']]);

        Response::json(["success" => true, "message" => "Workflow reset"]);
    }




    /* ============================================================
       BULK VERIFY
    ============================================================ */
    public static function bulk_verify($pdo)
    {
        $input = self::getInput();

        if (empty($input['programs']) || !is_array($input['programs']))
            Response::json(["error" => "programs must be array"], 400);

        if (empty($input['user_id']))
            Response::json(["error" => "user_id required"], 400);

        $results = [];

        foreach ($input['programs'] as $row) {

            if (empty($row["program_id"])) {
                $results[] = ["success" => false, "error" => "program_id missing"];
                continue;
            }

            $pid = $row["program_id"];
            if (!LibraryHierarchy::canVerifyProgram($pdo, $input['user_id'], $pid)) {
                $results[] = [
                    "success" => false,
                    "program_id" => $pid,
                    "error" => "Only the immediate parent library can verify this program"
                ];
                continue;
            }

            $stmt = $pdo->prepare("
                UPDATE gm_programs 
                SET verification_status='verified', verified_by=?, verified_at=NOW(),
                    rejected_by=NULL, rejected_at=NULL
                WHERE program_id=? AND is_deleted=0
            ");
            $stmt->execute([$input['user_id'], $pid]);

            // Note
            $stmtN = $pdo->prepare("
                INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
                VALUES (?, ?, 'verifier', 'Program verified (bulk)')
            ");
            $stmtN->execute([$pid, $input['user_id']]);

            $results[] = ["success" => true, "program_id" => $pid];
        }

        Response::json([
            "success" => true,
            "message" => "Bulk verification complete",
            "results" => $results
        ]);
    }




    /* ============================================================
       BULK REJECT
    ============================================================ */
    public static function bulk_reject($pdo)
    {
        $input = self::getInput();

        if (empty($input['programs']) || !is_array($input['programs']))
            Response::json(["error" => "programs must be array"], 400);

        foreach (['user_id', 'reason'] as $r)
            if (empty($input[$r])) Response::json(["error" => "$r is required"], 400);

        $results = [];

        foreach ($input['programs'] as $row) {

            if (empty($row["program_id"])) {
                $results[] = ["success" => false, "error" => "program_id missing"];
                continue;
            }

            $pid = $row["program_id"];
            if (!LibraryHierarchy::canVerifyProgram($pdo, $input['user_id'], $pid)) {
                $results[] = [
                    "success" => false,
                    "program_id" => $pid,
                    "error" => "Only the immediate parent library can reject this program"
                ];
                continue;
            }

            $stmt = $pdo->prepare("
                UPDATE gm_programs 
                SET verification_status='rejected', rejected_by=?, rejected_at=NOW(),
                    verified_by=NULL, verified_at=NULL
                WHERE program_id=? AND is_deleted=0
            ");
            $stmt->execute([$input['user_id'], $pid]);

            // Note
            $stmtN = $pdo->prepare("
                INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
                VALUES (?, ?, 'reject', ?)
            ");
            $stmtN->execute([$pid, $input['user_id'], $input['reason']]);

            $results[] = ["success" => true, "program_id" => $pid];
        }

        Response::json([
            "success" => true,
            "message" => "Bulk rejection complete",
            "results" => $results
        ]);
    }



    /* ============================================================
   BULK REMOVE (Soft Delete)
============================================================ */
public static function bulk_remove($pdo)
{
    $input = self::getInput();

    if (empty($input['programs']) || !is_array($input['programs'])) {
        Response::json(["error" => "programs must be array"], 400);
    }

    foreach (['user_id', 'reason'] as $r) {
        if (empty($input[$r])) {
            Response::json(["error" => "$r is required"], 400);
        }
    }

    $results = [];

    foreach ($input['programs'] as $row) {

        if (empty($row["program_id"])) {
            $results[] = ["success" => false, "error" => "program_id missing"];
            continue;
        }

        $pid = $row["program_id"];

        // Soft delete
        $stmt = $pdo->prepare("
            UPDATE gm_programs 
            SET is_deleted = 1,
                deleted_by = ?,
                deleted_at = CONVERT_TZ(NOW(), '+00:00', '+08:00'),
                verified_by = NULL,
                verified_at = NULL,
                rejected_by = NULL,
                rejected_at = NULL
            WHERE program_id = ? AND is_deleted = 0
        ");
        $stmt->execute([$input['user_id'], $pid]);

        // Audit note
        $stmtN = $pdo->prepare("
            INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
            VALUES (?, ?, 'remove', ?)
        ");
        $stmtN->execute([
            $pid,
            $input['user_id'],
            '' . $input['reason']
        ]);

        $results[] = ["success" => true, "program_id" => $pid];
    }

    Response::json([
        "success" => true,
        "message" => "Bulk remove complete",
        "results" => $results
    ]);
}



    /* ============================================================
       BULK RESET (Admin only)
    ============================================================ */
    public static function bulk_reset($pdo)
    {
        $input = self::getInput();

        if (empty($input['programs']) || !is_array($input['programs']))
            Response::json(["error" => "programs must be array"], 400);

        if (empty($input['user_id']))
            Response::json(["error" => "user_id required"], 400);

        // Permission check
        $stmt = $pdo->prepare("
            SELECT r.role_name AS role
            FROM gm_users u
            JOIN gm_roles r ON r.id = u.role_id
            WHERE u.id=?
        ");
        $stmt->execute([$input['user_id']]);
        $user = $stmt->fetch();

        if (!$user || !in_array($user['role'], ['admin','super_admin']))
            Response::json(["error" => "Only admin can reset workflow"], 403);

        $results = [];

        foreach ($input['programs'] as $row) {

            if (empty($row["program_id"])) {
                $results[] = ["success" => false, "error" => "program_id missing"];
                continue;
            }

            $pid = $row["program_id"];

            $stmt = $pdo->prepare("
                UPDATE gm_programs 
                SET verification_status='pending', verified_by=NULL, verified_at=NULL,
                    rejected_by=NULL, rejected_at=NULL
                WHERE program_id=? AND is_deleted=0
            ");
            $stmt->execute([$pid]);

            // Note
            $stmtN = $pdo->prepare("
                INSERT INTO gm_notes (program_id, user_id, note_role, note_text)
                VALUES (?, ?, 'admin', 'Workflow reset (bulk)')
            ");
            $stmtN->execute([$pid, $input['user_id']]);

            $results[] = ["success" => true, "program_id" => $pid];
        }

        Response::json([
            "success" => true,
            "message" => "Bulk reset complete",
            "results" => $results
        ]);
    }

    public static function verify_bulk($pdo)
    {
        self::bulk_verify($pdo);
    }

    public static function reject_bulk($pdo)
    {
        self::bulk_reject($pdo);
    }

}

?>
