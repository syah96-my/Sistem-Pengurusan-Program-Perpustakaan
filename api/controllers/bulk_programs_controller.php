<?php

class BulkProgramsController
{
    /* ============================
       Safe Input Reader
    ============================ */
    private static function getInput()
    {
        $raw = file_get_contents("php://input");
        $json = json_decode($raw, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        return $_POST;
    }


    /* ============================
       Validate required fields
    ============================ */
    private static function validateRow($row)
    {
        $required = [
            "library_id",
            "program_type_id",
            "scale_id",
            "mode",
            "program_name",
            "program_start",
            "program_end",
            "user_id"
        ];

        foreach ($required as $r) {
            if (!isset($row[$r]) || $row[$r] === "") {
                return "$r is required";
            }
        }

        return true;
    }


    /* ============================
       Single row insert logic (pure)
    ============================ */
    private static function insertSingle($pdo, $row)
    {
        // Validate row
        $valid = self::validateRow($row);
        if ($valid !== true) {
            return ["success" => false, "error" => $valid];
        }

        $lib = LibraryHierarchy::getLibrary($pdo, $row["library_id"]);
        if (!$lib) {
            return ["success" => false, "error" => "Invalid library_id"];
        }

        $parent_library_id = $lib["parent_id"] ?: null;
        $row["library_type_id"] = $lib["type_id"];

        $status = "incomplete";
        $verified_by = null;
        $verified_at = null;

        // Auto stage (past = completed)
        $now = new DateTime("now", new DateTimeZone("Asia/Kuala_Lumpur"));
        $end = new DateTime($row["program_end"], new DateTimeZone("Asia/Kuala_Lumpur"));
        $program_stage = ($end >= $now ? "pre_program" : "completed");

        $public_token = bin2hex(random_bytes(16));

        // Insert program
        $stmt = $pdo->prepare("
            INSERT INTO gm_programs (
                parent_library_id, library_type_id, library_id, program_type_id,
                scale_id, program_mode, platform_id, program_name, program_start, program_end,
                location, officiated_by, public_token, cover_image_url, program_details, document_url,
                program_stage, verification_status, verified_by, verified_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $parent_library_id,
            $row["library_type_id"],
            $row["library_id"],
            $row["program_type_id"],
            $row["scale_id"],
            $row["mode"],
            ($row["platform_id"] ?? null),
            $row["program_name"],
            $row["program_start"],
            $row["program_end"],
            ($row["location"] ?? null),
            ($row["officiated_by"] ?? null),
            $public_token,
            ($row["cover_image_url"] ?? null),
            ($row["program_details"] ?? null),
            ($row["document_url"] ?? null),
            $program_stage,
            $status,
            $verified_by,
            $verified_at
        ]);

        $program_id = $pdo->lastInsertId();

        // Insert target groups
        if (!empty($row["target_group_ids"]) && is_array($row["target_group_ids"])) {

            $stmtTG = $pdo->prepare("
                INSERT INTO gm_program_target_groups (program_id, target_group_id)
                VALUES (?, ?)
            ");

            foreach ($row["target_group_ids"] as $gid) {
                $stmtTG->execute([$program_id, $gid]);
            }
        }

        return [
            "success" => true,
            "program_id" => $program_id,
            "stage" => $program_stage,
            "status" => $status
        ];
    }


    /* ============================
       BULK IMPORT ENDPOINT
       route: programs/bulk_import
    ============================ */
    public static function bulk_import($pdo)
    {
        $input = self::getInput();

        if (empty($input["programs"]) || !is_array($input["programs"])) {
            Response::json(["error" => "programs array is required"], 400);
        }

        $results = [];

        foreach ($input["programs"] as $i => $row) {
            try {
                $results[$i] = self::insertSingle($pdo, $row);
            } catch (Exception $e) {
                $results[$i] = [
                    "success" => false,
                    "error" => $e->getMessage()
                ];
            }
        }

        Response::json([
            "success" => true,
            "message" => "Bulk import processed",
            "results" => $results
        ]);
    }
}

?>
