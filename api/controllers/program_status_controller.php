<?php

class ProgramStatusController
{
    /* ============================================================
       Recalculate status for ONE program
    ============================================================ */
    public static function recalcOne($pdo)
    {
        $input = $_POST ?: json_decode(file_get_contents("php://input"), true);

        if (empty($input["program_id"])) {
            Response::json(["error" => "program_id required"], 400);
        }

        self::recalculate($pdo, (int)$input["program_id"]);
    }

    /* ============================================================
       Recalculate status for ALL incomplete programs
       (used on login)
    ============================================================ */
    public static function recalcAll($pdo)
    {
        $stmt = $pdo->prepare("
            SELECT program_id
            FROM gm_programs
            WHERE verification_status = 'incomplete'
              AND is_deleted = 0
        ");
        $stmt->execute();

        $updated = 0;

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
            if (self::recalculate($pdo, (int)$pid, false)) {
                $updated++;
            }
        }

        Response::json([
            "success" => true,
            "updated" => $updated
        ]);
    }

    /* ============================================================
       INTERNAL — recalc logic
    ============================================================ */
    private static function recalculate($pdo, int $program_id, bool $respond = true)
    {
        /* Fetch program */
        $stmt = $pdo->prepare("
            SELECT *
            FROM gm_programs
            WHERE program_id = ? AND is_deleted = 0
        ");
        $stmt->execute([$program_id]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$program) {
            if ($respond) {
                Response::json(["error" => "Program not found"], 404);
            }
            return false;
        }

        /* Fetch target groups */
        $tgStmt = $pdo->prepare("
            SELECT target_group_id
            FROM gm_program_target_groups
            WHERE program_id = ?
        ");
        $tgStmt->execute([$program_id]);
        $targetGroups = $tgStmt->fetchAll(PDO::FETCH_COLUMN);

        /* Resolve participant count */
        $statStmt = $pdo->prepare("
            SELECT total_participant_count, is_manual_override
            FROM gm_program_participant_stats
            WHERE program_id = ?
        ");
        $statStmt->execute([$program_id]);
        $stats = $statStmt->fetch(PDO::FETCH_ASSOC);

        $participantCount = $stats ? (int)$stats["total_participant_count"] : 0;

        /* Build computeStatus input */
        $input = [
            "parent_library_id" => $program["parent_library_id"],
            "library_type_id"   => $program["library_type_id"],
            "program_type_id"   => $program["program_type_id"],
            "scale_id"          => $program["scale_id"],
            "mode"              => $program["program_mode"],
            "program_name"      => $program["program_name"],
            "program_start"     => $program["program_start"],
            "program_end"       => $program["program_end"],
            "cover_image_url"         => $program["cover_image_url"],
            "platform_id"       => $program["platform_id"],
            "location"          => $program["location"],
            "target_group_ids"  => $targetGroups,
            "participant_count" => $participantCount
        ];

        $newStatus = ProgramsController::computeStatus($input);

        /* Do not downgrade status */
        if ($newStatus === $program["verification_status"]) {
            if ($respond) {
                Response::json([
                    "success" => true,
                    "status"  => $program["verification_status"],
                    "changed" => false
                ]);
            }
            return false;
        }

        /* Root libraries do not need higher-level verification. */
        $verified_by = null;
        $verified_at = null;

        if (empty($program["parent_library_id"]) && $newStatus !== "incomplete") {
            $newStatus = "verified";
            $verified_by = $_SESSION["user_id"] ?? null;
            $verified_at = date("Y-m-d H:i:s");
        }

        /* Update status */
        $upd = $pdo->prepare("
            UPDATE gm_programs
            SET verification_status = ?, verified_by = ?, verified_at = ?, updated_at = NOW()
            WHERE program_id = ?
        ");
        $upd->execute([
            $newStatus,
            $verified_by,
            $verified_at,
            $program_id
        ]);

        if ($respond) {
            Response::json([
                "success" => true,
                "old_status" => $program["verification_status"],
                "new_status" => $newStatus,
                "changed" => true
            ]);
        }

        return true;
    }

    /* ============================================================
   Recalculate status for programs by library_id
   (used on login sync)
============================================================ */
    public static function recalcByLibrary($pdo)
    {
        $library_id = $_SESSION["library_id"] ?? null;

        if (!$library_id) {
            Response::json(["error" => "library_id not found in session"], 401);
        }

        $updated = self::syncByLibrary($pdo, $library_id);

        Response::json([
            "success" => true,
            "library_id" => $library_id,
            "updated" => $updated
        ]);
    }

    public static function syncByLibrary($pdo, $library_id)
    {
        $stmt = $pdo->prepare("
            SELECT program_id
            FROM gm_programs
            WHERE is_deleted = 0
            AND verification_status = 'incomplete'
            AND library_id = ?
        ");
        $stmt->execute([$library_id]);

        $updated = 0;

        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $pid) {
            if (self::recalculate($pdo, (int)$pid, false)) {
                $updated++;
            }
        }

        return $updated;
    }

}
?>
