<?php

class ParticipantsController {

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
       GET PROGRAM MODE (physical/online/hybrid)
    ============================================================ */
    private static function getProgramMode($pdo, $program_id)
    {
        $stmt = $pdo->prepare("SELECT program_mode FROM gm_programs WHERE program_id=? AND is_deleted=0");
        $stmt->execute([$program_id]);
        return $stmt->fetchColumn();
    }



    /* ============================================================
       VALIDATE attendance_mode BASED ON program.mode
    ============================================================ */
    private static function validateAttendanceMode($program_mode, $attendance_mode)
    {
        if ($program_mode === "physical" && $attendance_mode !== "physical") {
            return "Program is physical-only. attendance_mode must be 'physical'.";
        }

        if ($program_mode === "online" && $attendance_mode !== "online") {
            return "Program is online-only. attendance_mode must be 'online'.";
        }

        if ($program_mode === "hybrid" && !in_array($attendance_mode, ["physical", "online"])) {
            return "Hybrid program only accepts attendance_mode 'physical' or 'online'.";
        }

        return null; // valid
    }




    /* ============================================================
    INTERNAL: Update Stats Row for One Program
    (RESPECT MANUAL OVERRIDE)
    ============================================================ */
    private static function updateStats($pdo, $program_id)
    {

        // 1️⃣ Check manual override ONLY if explicitly enabled
        $check = $pdo->prepare("
            SELECT is_manual_override
            FROM gm_program_participant_stats
            WHERE program_id = ?
            LIMIT 1
        ");
        $check->execute([$program_id]);
        $row = $check->fetch(PDO::FETCH_ASSOC);

        // Skip auto rebuild when stats are manually overridden.
        if ($row && (int)$row['is_manual_override'] === 1) {
            return;
        }


        // 2️⃣ Calculate stats from participants table
        $calc = $pdo->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(gender = 'male') AS male,
                SUM(gender = 'female') AS female,
                AVG(age) AS average_age,
                SUM(attendance_mode = 'physical') AS physical,
                SUM(attendance_mode = 'online') AS online,
                SUM(registration_source = 'self') AS self_reg,
                SUM(registration_source = 'staff_upload') AS staff_up
            FROM gm_participants
            WHERE program_id = ?
        ");
        $calc->execute([$program_id]);
        $s = $calc->fetch(PDO::FETCH_ASSOC);

        // 3️⃣ Insert or update stats WITHOUT deleting row
        $pdo->prepare("
            INSERT INTO gm_program_participant_stats (
                program_id,
                total_participant_count,
                male_participant_count,
                female_participant_count,
                average_age,
                physical_participant_count,
                online_participant_count,
                self_registered_participant_count,
                staff_uploaded_participant_count,
                is_manual_override
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ON DUPLICATE KEY UPDATE
                total_participant_count = VALUES(total_participant_count),
                male_participant_count = VALUES(male_participant_count),
                female_participant_count = VALUES(female_participant_count),
                average_age = VALUES(average_age),
                physical_participant_count = VALUES(physical_participant_count),
                online_participant_count = VALUES(online_participant_count),
                self_registered_participant_count = VALUES(self_registered_participant_count),
                staff_uploaded_participant_count = VALUES(staff_uploaded_participant_count)
        ")->execute([
            $program_id,
            (int)$s['total'],
            (int)$s['male'],
            (int)$s['female'],
            $s['average_age'],
            (int)$s['physical'],
            (int)$s['online'],
            (int)$s['self_reg'],
            (int)$s['staff_up']
        ]);
    }





    /* ============================================================
       ADD PARTICIPANT
    ============================================================ */
    public static function add($pdo)
    {
        $input = self::getInput();

        $required = ['program_id', 'name', 'attendance_mode', 'registration_source', 'email'];
        foreach ($required as $r)
            if (empty($input[$r])) Response::json(["error" => "$r is required"], 400);

        if (strlen(trim($input["name"])) < 2)
            Response::json(["error" => "Name too short"], 400);

        if (!filter_var($input["email"], FILTER_VALIDATE_EMAIL))
            Response::json(["error" => "Invalid email format"], 400);


        /* ==========================================
           CHECK PROGRAM MODE RESTRICTION
        ========================================== */
        $program_mode = self::getProgramMode($pdo, $input["program_id"]);
        if (!$program_mode)
            Response::json(["error" => "Invalid program_id"], 400);

        $err = self::validateAttendanceMode($program_mode, $input["attendance_mode"]);
        if ($err) Response::json(["error" => $err], 400);


        /* Validate registration_source */
        if (!in_array($input["registration_source"], ['self','staff_upload']))
            Response::json(["error" => "registration_source must be self or staff_upload"], 400);


        /* INSERT */
        try {
            $stmt = $pdo->prepare("
                INSERT INTO gm_participants (
                    program_id, participant_name, gender, age, email, phone_number,
                    position_title, organization_name, attendance_mode, attendance_time,
                    registration_source
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");

            $stmt->execute([
                $input["program_id"],
                $input["name"],
                $input["gender"] ?? null,
                $input["age"] ?? null,
                $input["email"],
                $input["phone"] ?? null,
                $input["occupation"] ?? null,
                $input["company"] ?? null,
                $input["attendance_mode"],
                $input["registration_source"]
            ]);

        } catch (PDOException $e) {

            if ($e->getCode() == 23000) {
                Response::json([
                    "error" => "This email is already registered for this program."
                ], 409);
            }

            throw $e;
        }


        self::updateStats($pdo, $input["program_id"]);

        Response::json([
            "success" => true,
            "message" => "Participant added"
        ]);
    }




    /* ============================================================
       BULK UPLOAD
    ============================================================ */
    public static function bulk_upload($pdo)
    {
        $input = self::getInput();

        if (empty($input["program_id"]))
            Response::json(["error" => "program_id is required"], 400);

        if (empty($input["participants"]) || !is_array($input["participants"]))
            Response::json(["error" => "participants must be array"], 400);

        $program_id = $input["program_id"];

        /* ==========================================
           GET PROGRAM MODE once
        ========================================== */
        $program_mode = self::getProgramMode($pdo, $program_id);
        if (!$program_mode)
            Response::json(["error" => "Invalid program_id"], 400);

        $stmt = $pdo->prepare("
            INSERT INTO gm_participants (
                program_id, participant_name, gender, age, email, phone_number,
                position_title, organization_name, attendance_mode, attendance_time,
                registration_source
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
        ");

        $results = [];

        $pdo->beginTransaction();

        try {

            foreach ($input["participants"] as $p) {

                if (empty($p["name"]) || strlen(trim($p["name"])) < 2) {
                    $results[] = ["success" => false, "error" => "Invalid name"];
                    continue;
                }

                if (empty($p["email"]) || !filter_var($p["email"], FILTER_VALIDATE_EMAIL)) {
                    $results[] = ["success" => false, "error" => "Invalid email"];
                    continue;
                }

                $attendance_mode = $p["attendance_mode"] ?? 'physical';

                // validate attendance_mode based on program mode
                $err = self::validateAttendanceMode($program_mode, $attendance_mode);
                if ($err) {
                    $results[] = ["success"=>false, "error"=>$err];
                    continue;
                }

                try {
                    $stmt->execute([
                        $program_id,
                        $p["name"],
                        $p["gender"] ?? null,
                        $p["age"] ?? null,
                        $p["email"],
                        $p["phone"] ?? null,
                        $p["occupation"] ?? null,
                        $p["company"] ?? null,
                        $attendance_mode,
                        $p["registration_source"] ?? 'staff_upload'
                    ]);

                    $results[] = ["success" => true];

                } catch (PDOException $e) {

                    if ($e->getCode() == 23000) {
                        $results[] = [
                            "success" => false,
                            "error" => "Duplicate email for this program: {$p['email']}"
                        ];
                        continue;
                    }

                    throw $e;
                }
            }

            $pdo->commit();

        } catch (Exception $e) {
            $pdo->rollBack();
            Response::json(["error"=>"Bulk upload failed","details"=>$e->getMessage()],500);
        }


        self::updateStats($pdo, $program_id);

        /* ============================================================
   AUTO MOVE PROGRAM TO PENDING IF ELIGIBLE
============================================================ */

// 1️⃣ Re-check participant count
$cntStmt = $pdo->prepare("
    SELECT total_participant_count
    FROM gm_program_participant_stats
    WHERE program_id = ?
");
$cntStmt->execute([$program_id]);
$total = (int)$cntStmt->fetchColumn();

// Only proceed if participants exist
if ($total > 0) {

    // 2️⃣ Load program data
    $progStmt = $pdo->prepare("
        SELECT *
        FROM gm_programs
        WHERE program_id = ? AND is_deleted = 0
    ");
    $progStmt->execute([$program_id]);
    $program = $progStmt->fetch(PDO::FETCH_ASSOC);

    if ($program && $program["verification_status"] === "incomplete") {

        // 3️⃣ Load target groups
        $tgStmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM gm_program_target_groups 
            WHERE program_id = ?
        ");
        $tgStmt->execute([$program_id]);
        $tgCount = (int)$tgStmt->fetchColumn();

        // 4️⃣ Check required fields (same logic as ProgramsController)
        $ready =
            !empty($program["library_type_id"]) &&
            !empty($program["program_type_id"]) &&
            !empty($program["scale_id"]) &&
            !empty($program["program_mode"]) &&
            !empty($program["program_name"]) &&
            !empty($program["program_start"]) &&
            !empty($program["program_end"]) &&
            !empty($program["cover_image_url"]) &&
            $tgCount > 0;

        // Conditional requirements
        if (in_array($program["program_mode"], ["online","hybrid"])) {
            $ready = $ready && !empty($program["platform_id"]);
        }

        if (in_array($program["program_mode"], ["physical","hybrid"])) {
            $ready = $ready && !empty($program["location"]);
        }

        // 5️⃣ Promote status
        if ($ready) {
            $pdo->prepare("
                UPDATE gm_programs
                SET verification_status = 'pending', updated_at = NOW()
                WHERE program_id = ?
            ")->execute([$program_id]);
        }
    }
}

        Response::json([
            "success" => true,
            "message" => "Bulk upload completed",
            "results" => $results
        ]);
    }




    /* ============================================================
       LIST PARTICIPANTS
    ============================================================ */
    public static function list($pdo)
    {
        $program_id = $_GET["program_id"] ?? null;
        if (!$program_id)
            Response::json(["error" => "program_id required"], 400);

        $page  = max(1, intval($_GET["page"]  ?? 1));
        $limit = max(1, intval($_GET["limit"] ?? 50));
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare("
            SELECT
                participant_id,
                program_id,
                participant_name AS name,
                gender,
                age,
                email,
                phone_number AS phone,
                position_title AS occupation,
                organization_name AS company,
                attendance_mode,
                attendance_time,
                registration_source,
                created_at,
                updated_at
            FROM gm_participants
            WHERE program_id = ?
            ORDER BY created_at ASC
            LIMIT $offset, $limit
        ");
        $stmt->execute([$program_id]);

        $countStmt = $pdo->prepare("
            SELECT COUNT(*) FROM gm_participants WHERE program_id = ?
        ");
        $countStmt->execute([$program_id]);
        $total = $countStmt->fetchColumn();

        Response::json([
            "success" => true,
            "page" => $page,
            "limit" => $limit,
            "total" => $total,
            "participants" => $stmt->fetchAll()
        ]);
    }




    /* ============================================================
       MANUALLY REBUILD STATS
    ============================================================ */
    public static function rebuild_stats($pdo)
    {
        $input = self::getInput();

        if (empty($input["program_id"]))
            Response::json(["error" => "program_id required"], 400);

        self::updateStats($pdo, $input["program_id"]);

        Response::json([
            "success" => true,
            "message" => "Stats rebuilt"
        ]);
    }

}

?>
