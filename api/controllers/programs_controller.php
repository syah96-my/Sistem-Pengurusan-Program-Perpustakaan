<?php

class ProgramsController {

    private static function normalizeProgramRow(array $row)
    {
        if (isset($row['program_mode']) && !isset($row['mode'])) {
            $row['mode'] = $row['program_mode'];
        }
        if (isset($row['verification_status']) && !isset($row['status'])) {
            $row['status'] = $row['verification_status'];
        }
        if (isset($row['public_token']) && !isset($row['uid'])) {
            $row['uid'] = $row['public_token'];
        }
        if (isset($row['document_url']) && !isset($row['url_link'])) {
            $row['url_link'] = $row['document_url'];
        }
        if (isset($row['cover_image_url']) && !isset($row['image_url'])) {
            $row['image_url'] = $row['cover_image_url'];
        }
        if (isset($row['officiated_by']) && !isset($row['officiate'])) {
            $row['officiate'] = $row['officiated_by'];
        }
        if (isset($row['is_deleted']) && !isset($row['deleted'])) {
            $row['deleted'] = $row['is_deleted'];
        }
        if (isset($row['total_participant_count']) && !isset($row['total_participants'])) {
            $row['total_participants'] = $row['total_participant_count'];
        }
        if (isset($row['physical_participant_count']) && !isset($row['physical_count'])) {
            $row['physical_count'] = $row['physical_participant_count'];
        }
        if (isset($row['online_participant_count']) && !isset($row['online_count'])) {
            $row['online_count'] = $row['online_participant_count'];
        }
        if (isset($row['self_registered_participant_count']) && !isset($row['self_registered_count'])) {
            $row['self_registered_count'] = $row['self_registered_participant_count'];
        }
        if (isset($row['staff_uploaded_participant_count']) && !isset($row['staff_uploaded_count'])) {
            $row['staff_uploaded_count'] = $row['staff_uploaded_participant_count'];
        }
        if (isset($row['is_manual_override']) && !isset($row['manual_override'])) {
            $row['manual_override'] = $row['is_manual_override'];
        }
        return $row;
    }

    /* ============================================================
       HELPER — Read JSON or POST
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
       Helper — resolve effective participant count
       Supports manual override (future-safe)
    ============================================================ */
    private static function resolveParticipantCount($pdo, $program_id)
    {
        $stmt = $pdo->prepare("
            SELECT physical_participant_count, online_participant_count
            FROM gm_program_participant_stats
            WHERE program_id = ?
        ");
        $stmt->execute([$program_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return 0;

        return (int)$row["physical_participant_count"] + (int)$row["online_participant_count"];
    }

    private static function applySessionScope($pdo, &$where, &$values, $alias = 'p')
    {
        $role = (int)($_SESSION['role'] ?? 0);
        $libraryId = (int)($_SESSION['library_id'] ?? 0);

        if ($role === 1) {
            return;
        }

        if ($libraryId <= 0) {
            $where .= " AND 1 = 0 ";
            return;
        }

        list($scopeSql, $scopeValues) = LibraryHierarchy::programScopeWhere($pdo, $libraryId, $alias . '.library_id');
        $where .= $scopeSql;
        foreach ($scopeValues as $scopeValue) {
            $values[] = $scopeValue;
        }
    }


    /* ============================================================
       STATUS AUTO-CHECK (NOW REQUIRES TARGET GROUP)
    ============================================================ */
    public static function computeStatus($input)
    {
        // Always required fields
        $required = [
            "library_type_id",
            "program_type_id",
            "scale_id",
            "mode",
            "program_name",
            "program_start",
            "program_end",
            "cover_image_url",
            "participant_count"
        ];
    
        // Check always-required fields
        foreach ($required as $r) {
            if (empty($input[$r]) && $input[$r] !== "0") {
                return "incomplete";
            }
        }
    
        // participant_count must be > 0
        if (intval($input["participant_count"]) <= 0) {
            return "incomplete";
        }
    
        // target_group_ids must exist and not empty
        if (
            empty($input["target_group_ids"]) ||
            !is_array($input["target_group_ids"]) ||
            count($input["target_group_ids"]) === 0
        ) {
            return "incomplete";
        }
    
        // Conditional: platform_id required if online or hybrid
        if (in_array($input["mode"], ["online","hybrid"])) {
            if (empty($input["platform_id"])) {
                return "incomplete";
            }
        }
    
        // Conditional: location required if physical or hybrid
        if (in_array($input["mode"], ["physical","hybrid"])) {
            if (empty($input["location"])) {
                return "incomplete";
            }
        }
    
        // If all checks passed → pending
        return "pending";
    }


    /* ============================================================
       INTERNAL — CREATE PROGRAM
    ============================================================ */
    private static function createSingle($pdo, $input)
    {
        $required = [
            'library_id','program_type_id','scale_id','mode',
            'program_name','program_start','program_end','user_id'
        ];

        foreach ($required as $r) {
            if (!isset($input[$r])) {
                return ["success" => false, "error" => "$r is required"];
            }
        }

        $library = LibraryHierarchy::getLibrary($pdo, $input['library_id']);
        if (!$library) {
            return ["success" => false, "error" => "Invalid library_id"];
        }

        $parent_library_id = $library['parent_id'] ?: null;
        $input['library_type_id'] = $library['type_id'];

        /* ============================================================
           PARTICIPANT COUNT — allow manual override on create
        ============================================================ */
        // Ensure target_group_ids always exists
        $input["target_group_ids"] = $input["target_group_ids"] ?? [];

        /* PROGRAM STAGE */
        $nowMY = new DateTime("now", new DateTimeZone("Asia/Kuala_Lumpur"));
        $end   = new DateTime($input["program_end"], new DateTimeZone("Asia/Kuala_Lumpur"));
        $program_stage = ($end >= $nowMY) ? "pre_program" : "completed";

        /* ============================================================
        MANUAL PARTICIPANT OVERRIDE (CREATE — PAST PROGRAM ONLY)
        ============================================================ */

        $is_manual_override = 0;
        $physical = 0;
        $online   = 0;

        // Only allow manual override if program already completed
        if (
            $program_stage === "completed" &&
            !empty($input["is_manual_override"])
        ) {
            $is_manual_override = 1;

            $physical = (int)($input["manual_physical"] ?? 0);
            $online   = (int)($input["manual_online"] ?? 0);

            // Enforce mode rules
            if ($input["mode"] === "physical") {
                $online = 0;
            }
            if ($input["mode"] === "online") {
                $physical = 0;
            }
        }

        // Set participant_count properly
        $input["participant_count"] = $physical + $online;
        /* ============================================================
        STATUS CHECK (AFTER participant_count resolved)
        ============================================================ */
        $status = self::computeStatus($input);

        /* Root libraries do not need higher-level verification. */
        if ($parent_library_id === null && $status !== "incomplete") {
            $status = "verified";
            $verified_by = $input["user_id"];
            $verified_at = date("Y-m-d H:i:s");
        } else {
            $verified_by = null;
            $verified_at = null;
        }


        $public_token = bin2hex(random_bytes(16));

        /* INSERT PROGRAM */
        $stmt = $pdo->prepare("
            INSERT INTO gm_programs (
                parent_library_id, library_type_id, library_id, program_type_id,
                scale_id, program_mode, platform_id, program_name, program_start, program_end,
                location, officiated_by, public_token, cover_image_url, program_details, document_url,
                program_stage, verification_status, verified_by, verified_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $parent_library_id,
            $input["library_type_id"],
            $input["library_id"],
            $input["program_type_id"],
            $input["scale_id"],
            $input["mode"],
            ($input["platform_id"] ?? null),
            $input["program_name"],
            $input["program_start"],
            $input["program_end"],
            $input["location"] ?? null,
            $input["officiated_by"] ?? null,
            $public_token,
            $input["cover_image_url"] ?? null,
            $input["program_details"] ?? null,
            $input["document_url"] ?? null,
            $program_stage,
            $status,
            $verified_by,
            $verified_at
        ]);

        $program_id = $pdo->lastInsertId();
        
        /* CREATE INITIAL PARTICIPANT STATS */
        $pdo->prepare("
            INSERT INTO gm_program_participant_stats
                (program_id, is_manual_override, physical_participant_count, online_participant_count, total_participant_count)
            VALUES (?, ?, ?, ?, ?)
        ")->execute([
            $program_id,
            $is_manual_override,
            $physical,
            $online,
            $physical + $online
        ]);





        /* TARGET GROUPS */
        if (!empty($input["target_group_ids"])) {
            $stmtTG = $pdo->prepare("
                INSERT INTO gm_program_target_groups (program_id, target_group_id)
                VALUES (?, ?)
            ");
            foreach ($input["target_group_ids"] as $gid) {
                $stmtTG->execute([$program_id, $gid]);
            }
        }
        return [
            "success" => true,
            "program_id" => $program_id,
            "public_token" => $public_token,
            "status" => $status,
            "program_stage" => $program_stage
        ];



    }

    /* ============================================================
       PUBLIC CREATE API (Duplicate UID safe)
    ============================================================ */
    public static function create($pdo)
    {
        $input = self::getInput();
        try {
            $res = self::createSingle($pdo, $input);
            if (!$res["success"]) {
                Response::json($res, 400);
            }
            Response::json($res);
        }
        catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                Response::json(["success"=>false,"error"=>"Duplicate submission detected"],409);
            }
            Response::json(["success"=>false,"error"=>"Database error","details"=>$e->getMessage()],500);
        }
    }

    /* ============================================================
       UPDATE PROGRAM
    ============================================================ */
    public static function update($pdo)
    {
        $input = self::getInput();
        if (empty($input["program_id"])) {
            Response::json(["error"=>"program_id required"],400);
        }

        $program_id = $input["program_id"];

        $stmt = $pdo->prepare("SELECT * FROM gm_programs WHERE program_id=? AND is_deleted=0");
        $stmt->execute([$program_id]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$old) {
            Response::json(["error"=>"Program not found"],404);
        }
        $old = self::normalizeProgramRow($old);

        $fields = [
            "parent_library_id","library_type_id","library_id","program_type_id",
            "scale_id","mode","platform_id",
            "program_name","program_start","program_end",
            "location","officiated_by","cover_image_url","program_details","document_url",
            "program_stage","participant_count","target_group_ids"
        ];

        $merged = [];
        foreach ($fields as $f) {
            if ($f === "target_group_ids") {
                if (
                    isset($input["target_group_ids"]) &&
                    is_array($input["target_group_ids"]) &&
                    count($input["target_group_ids"]) > 0
                ) {
                    // Use user-provided target groups
                    $merged[$f] = $input["target_group_ids"];
                } else {
                    // Fallback to existing DB target groups
                    $tgStmt = $pdo->prepare("
                        SELECT target_group_id
                        FROM gm_program_target_groups
                        WHERE program_id = ?
                    ");
                    $tgStmt->execute([$program_id]);
                    $merged[$f] = $tgStmt->fetchAll(PDO::FETCH_COLUMN);
                }
            } else {
                $merged[$f] = $input[$f] ?? $old[$f] ?? null;
            }
        }
        
        /* PROGRAM STAGE — Same logic as create() */
        try {
            $nowMY = new DateTime("now", new DateTimeZone("Asia/Kuala_Lumpur"));
            $end   = new DateTime($merged["program_end"], new DateTimeZone("Asia/Kuala_Lumpur"));
            $merged["program_stage"] = ($end >= $nowMY) ? "pre_program" : "completed";
        } catch (Exception $e) {
            // if program_end invalid, keep old stage
            $merged["program_stage"] = $old["program_stage"];
        }




        // Disallow manual override if program has not ended
        try {
            $nowMY = new DateTime("now", new DateTimeZone("Asia/Kuala_Lumpur"));
            $end   = new DateTime($merged["program_end"], new DateTimeZone("Asia/Kuala_Lumpur"));

            if ($end >= $nowMY) {
                unset($input["is_manual_override"]);
            }
        } catch (Exception $e) {
            unset($input["is_manual_override"]);
        }


/* ============================================================
   MANUAL PARTICIPANT OVERRIDE UPDATE
============================================================ */

// Case 1: manual override OFF
// Fetch current stats state
$statsStmt = $pdo->prepare("
    SELECT is_manual_override
    FROM gm_program_participant_stats
    WHERE program_id = ?
");
$statsStmt->execute([$program_id]);
$currentStats = $statsStmt->fetch(PDO::FETCH_ASSOC);

/* ============================================================
   MANUAL OVERRIDE STATE TRANSITIONS ONLY
============================================================ */

// Case A: manual override was ON → user turns it OFF
if (
    isset($input["is_manual_override"]) &&
    empty($input["is_manual_override"]) &&
    $currentStats &&
    (int)$currentStats["is_manual_override"] === 1
) {
    // DO NOT zero stats — just unlock auto calculation
    $pdo->prepare("
        UPDATE gm_program_participant_stats
        SET is_manual_override = 0
        WHERE program_id = ?
    ")->execute([$program_id]);
}

// Case B: manual override ON
if (!empty($input["is_manual_override"])) {

    $physical = (int)($input["manual_physical"] ?? 0);
    $online   = (int)($input["manual_online"] ?? 0);

    if ($merged["mode"] === "physical") $online = 0;
    if ($merged["mode"] === "online")   $physical = 0;

    $pdo->prepare("
        UPDATE gm_program_participant_stats
        SET is_manual_override = 1,
            physical_participant_count = ?,
            online_participant_count = ?,
            total_participant_count = ?
        WHERE program_id = ?
    ")->execute([
        $physical,
        $online,
        $physical + $online,
        $program_id
    ]);
}


// Case 2: manual override ON
if (!empty($input["is_manual_override"])) {

    $physical = (int)($input["manual_physical"] ?? 0);
    $online   = (int)($input["manual_online"] ?? 0);

    // Enforce mode rules
    if ($merged["mode"] === "physical") {
        $online = 0;
    }
    if ($merged["mode"] === "online") {
        $physical = 0;
    }

    $pdo->prepare("
        UPDATE gm_program_participant_stats
        SET is_manual_override = 1,
            physical_participant_count = ?,
            online_participant_count = ?,
            total_participant_count = ?
        WHERE program_id = ?
    ")->execute([
        $physical,
        $online,
        $physical + $online,
        $program_id
    ]);
}





        /* STATUS CHECK */
        $merged["participant_count"] = self::resolveParticipantCount($pdo, $program_id);
        $status = self::computeStatus($merged);

        $library = LibraryHierarchy::getLibrary($pdo, $merged["library_id"]);
        if (!$library) {
            Response::json(["error" => "Invalid library_id"], 400);
        }
        $merged["parent_library_id"] = $library["parent_id"] ?: null;
        $merged["library_type_id"] = $library["type_id"];

        /* Root libraries do not need higher-level verification. */
        if ($merged["parent_library_id"] === null && $status !== "incomplete") {
            $status = "verified";
            $verified_by = $input["user_id"] ?? null;
            $verified_at = date("Y-m-d H:i:s");
        } else {
            $verified_by = null;
            $verified_at = null;
        }

        /* UPDATE MAIN PROGRAM */
        $stmt = $pdo->prepare("
            UPDATE gm_programs SET
                parent_library_id=?, library_type_id=?, library_id=?, program_type_id=?,
                scale_id=?, program_mode=?, platform_id=?, program_name=?, program_start=?, program_end=?,
                location=?, officiated_by=?, cover_image_url=?, program_details=?, document_url=?, program_stage=?,
                verification_status=?, verified_by=?, verified_at=?, updated_at=NOW()
            WHERE program_id=? AND is_deleted=0
        ");

        $stmt->execute([
            $merged["parent_library_id"],
            $merged["library_type_id"],
            $merged["library_id"],
            $merged["program_type_id"],
            $merged["scale_id"],
            $merged["mode"],
            $merged["platform_id"],
            $merged["program_name"],
            $merged["program_start"],
            $merged["program_end"],
            $merged["location"],
            $merged["officiated_by"],
            $merged["cover_image_url"],
            $merged["program_details"],
            $merged["document_url"],
            $merged["program_stage"],
            $status,
            $verified_by,
            $verified_at,
            $program_id
        ]);

        /* TARGET GROUPS */
        if (isset($input["target_group_ids"])) {
            $pdo->prepare("DELETE FROM gm_program_target_groups WHERE program_id=?")->execute([$program_id]);
            $stmtTG = $pdo->prepare("INSERT INTO gm_program_target_groups (program_id,target_group_id) VALUES (?,?)");

            foreach ($input["target_group_ids"] as $gid) {
                $stmtTG->execute([$program_id, $gid]);
            }
        }

        Response::json([
            "success"=> true,
            "message"=>"Program updated",
            "status"=> $status
        ]);
    }

    /* ============================================================
       SET PROGRAM STAGE
    ============================================================ */
    public static function set_stage($pdo)
    {
        $input = self::getInput();
        if (empty($input["program_id"]) || empty($input["program_stage"])) {
            Response::json(["error"=>"program_id and program_stage required"],400);
        }

        $valid = ["pre_program","completed","cancel","cancelled","canceled"];
        if (!in_array($input["program_stage"], $valid)) {
            Response::json(["error"=>"Invalid program_stage"],400);
        }

        $stmt = $pdo->prepare("
            UPDATE gm_programs 
            SET program_stage=?, updated_at=NOW() 
            WHERE program_id=? AND is_deleted=0
        ");
        $stmt->execute([$input["program_stage"], $input["program_id"]]);

        Response::json([
            "success"=>true,
            "message"=>"Stage updated",
            "program_stage"=>$input["program_stage"]
        ]);
    }

    /* ============================================================
       DELETE PROGRAM
    ============================================================ */
    public static function delete($pdo)
    {
        $input = self::getInput();
        if (empty($input["program_id"])) {
            Response::json(["error"=>"program_id required"],400);
        }

        $stmt=$pdo->prepare("
            UPDATE gm_programs SET is_deleted=1, deleted_by=?, deleted_at=NOW()
            WHERE program_id=?
        ");
        $stmt->execute([$input["user_id"] ?? null, $input["program_id"]]);

        Response::json(["success"=>true,"message"=>"Program deleted"]);
    }

    /* ============================================================
       VIEW PROGRAM
    ============================================================ */
    public static function view($pdo)
    {
        if (empty($_GET["program_id"])) {
            Response::json(["error" => "program_id required"], 400);
        }
    
        $id = $_GET["program_id"];
    
        /* ---------------------------------------------------------
           FETCH PROGRAM WITH ALL RELATED NAMES
        --------------------------------------------------------- */
        $stmt = $pdo->prepare("
            SELECT 
                p.*,

                -- Direct Stats
                s.total_participant_count, s.is_manual_override, s.male_participant_count, s.female_participant_count, s.average_age,
                s.physical_participant_count, s.online_participant_count,
                s.self_registered_participant_count, s.staff_uploaded_participant_count,

                -- Library Names
                l.name AS library_name,
                pl.name AS parent_library_name,

                -- Type/Scale/Platform Names
                lt.type_name AS library_type_name,
                pt.type_name AS program_type_name,
                sc.scale_name,
                pf.platform_name

            FROM gm_programs p
            
            LEFT JOIN gm_program_participant_stats s 
                ON s.program_id = p.program_id
            
            LEFT JOIN gm_libraries l 
                ON l.id = p.library_id
            
            LEFT JOIN gm_libraries pl 
                ON pl.id = p.parent_library_id
            
            LEFT JOIN gm_library_types lt 
                ON lt.id = p.library_type_id
            
            LEFT JOIN gm_program_types pt 
                ON pt.id = p.program_type_id
            
            LEFT JOIN gm_scales sc 
                ON sc.id = p.scale_id
            
            LEFT JOIN gm_platforms pf 
                ON pf.id = p.platform_id

            WHERE p.program_id = ? 
              AND p.is_deleted = 0
        ");
        $stmt->execute([$id]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$program) {
            Response::json(["error" => "Program not found"], 404);
        }

        $program = self::normalizeProgramRow($program);

        $role = (int)($_SESSION['role'] ?? 0);
        $sessionLibraryId = (int)($_SESSION['library_id'] ?? 0);
        if ($role !== 1 && !LibraryHierarchy::isAncestorOrSelf($pdo, $sessionLibraryId, $program['library_id'])) {
            Response::json(["error" => "Program not found"], 404);
        }
    
        /* ---------------------------------------------------------
           TARGET GROUPS
        --------------------------------------------------------- */
        $tg = $pdo->prepare("
            SELECT tg.* 
            FROM gm_program_target_groups pt
            JOIN gm_target_groups tg ON tg.id = pt.target_group_id
            WHERE pt.program_id = ?
        ");
        $tg->execute([$id]);
        $program["target_groups"] = $tg->fetchAll();
    
        /* ---------------------------------------------------------
           NOTES
        --------------------------------------------------------- */
        $notes = $pdo->prepare("
            SELECT *
            FROM  gm_notes
            WHERE program_id = ?
            ORDER BY created_at DESC
        ");
        $notes->execute([$id]);
        $program["notes"] = $notes->fetchAll(PDO::FETCH_ASSOC);
        $program["note_text"] = $program["notes"];
    
        /* ---------------------------------------------------------
           RESPONSE
        --------------------------------------------------------- */
        Response::json([
            "success" => true,
            "program" => $program
        ]);
    }



    /* ============================================================
       SIMPLE LIST
    ============================================================ */
    public static function list($pdo)
    {
        $where=" WHERE p.is_deleted=0 ";
        $values=[];
        self::applySessionScope($pdo, $where, $values);

        $filters=[
            "library_id"=>"p.library_id",
            "parent_library_id"=>"p.parent_library_id",
            "status"=>"p.verification_status",
            "program_stage"=>"p.program_stage",
            "program_type_id"=>"p.program_type_id",
            "scale_id"=>"p.scale_id",
            "mode"=>"p.program_mode",
            "platform_id"=>"p.platform_id"
        ];

        foreach($filters as $key=>$col){
            if(!empty($_GET[$key])){
                $where.=" AND $col=? ";
                $values[]=$_GET[$key];
            }
        }

        if(!empty($_GET["target_group_id"])){
            $where.=" AND EXISTS (
                SELECT 1 FROM gm_program_target_groups tg
                WHERE tg.program_id=p.program_id AND tg.target_group_id=?
            )";
            $values[]=$_GET["target_group_id"];
        }

        if(!empty($_GET["keyword"])){
            $kw="%".$_GET["keyword"]."%";
            $where.=" AND (
                p.program_name LIKE ? OR p.program_details LIKE ?
                OR p.location LIKE ? OR p.officiated_by LIKE ?
            )";
            array_push($values,$kw,$kw,$kw,$kw);
        }

        $stmt=$pdo->prepare("
            SELECT p.* FROM gm_programs p
            $where
            ORDER BY p.program_start DESC
        ");
        $stmt->execute($values);

        $programs = array_map([self::class, 'normalizeProgramRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));
        Response::json(["success"=>true,"programs"=>$programs]);
    }

    /* ============================================================
       DATATABLES
    ============================================================ */
    public static function datatables($pdo)
    {
        $draw=$_GET["draw"] ?? 1;
        $start=$_GET["start"] ?? 0;
        $length=$_GET["length"] ?? 10;
        $search=$_GET["search"]["value"] ?? "";

        $where=" WHERE p.is_deleted=0 ";
        $values=[];
        self::applySessionScope($pdo, $where, $values);

        $filters=[
            "library_id"=>"p.library_id",
            "parent_library_id"=>"p.parent_library_id",
            "status"=>"p.verification_status",
            "program_stage"=>"p.program_stage",
            "program_type_id"=>"p.program_type_id",
            "scale_id"=>"p.scale_id",
            "mode"=>"p.program_mode",
            "platform_id"=>"p.platform_id",
            "status_filter"=>"p.verification_status"
        ];

        foreach($filters as $key=>$col){
            if(!empty($_GET[$key])){
                $where.=" AND $col=? ";
                $values[]=$_GET[$key];
            }
        }

        if(!empty($_GET["target_group_id"])){
            $where.=" AND EXISTS (
                SELECT 1 FROM gm_program_target_groups tg
                WHERE tg.program_id=p.program_id AND tg.target_group_id=?
            )";
            $values[]=$_GET["target_group_id"];
        }

        if(!empty($search)){
            $kw="%$search%";
            $where.=" AND (
                p.program_name LIKE ?
                OR p.program_details LIKE ?
                OR p.location LIKE ?
                OR p.officiated_by LIKE ?
            )";
            array_push($values,$kw,$kw,$kw,$kw);
        }

        $columns=[
            "p.program_id","p.program_name","p.program_start",
            "p.program_end","p.verification_status","p.program_stage","p.program_mode"
        ];

        $orderSql="";
        if(!empty($_GET["order"][0]["column"])){
            $i=intval($_GET["order"][0]["column"]);
            $dir=($_GET["order"][0]["dir"]==="desc") ? "DESC" : "ASC";
            if(isset($columns[$i])){
                $orderSql=" ORDER BY ".$columns[$i]." $dir ";
            }
        }

        $recordsTotal=$pdo->query("SELECT COUNT(*) FROM gm_programs WHERE is_deleted=0")->fetchColumn();

        $stmt=$pdo->prepare("SELECT COUNT(*) FROM gm_programs p $where");
        $stmt->execute($values);
        $recordsFiltered=$stmt->fetchColumn();

        $stmt=$pdo->prepare("
            SELECT p.*, s.total_participant_count, s.physical_participant_count, s.online_participant_count
            FROM gm_programs p
            LEFT JOIN gm_program_participant_stats s ON s.program_id=p.program_id
            $where
            $orderSql
            LIMIT $start, $length
        ");
        $stmt->execute($values);
        $data = array_map([self::class, 'normalizeProgramRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        foreach($data as &$row){
            $tg=$pdo->prepare("
                SELECT tg.group_name
                FROM gm_program_target_groups pg
                JOIN gm_target_groups tg ON tg.id=pg.target_group_id
                WHERE pg.program_id=?
            ");
            $tg->execute([$row["program_id"]]);
            $row["target_groups"]=$tg->fetchAll(PDO::FETCH_COLUMN);
        }

        Response::json([
            "draw"=>intval($draw),
            "recordsTotal"=>$recordsTotal,
            "recordsFiltered"=>$recordsFiltered,
            "data"=>$data
        ]);
    }

    public static function datatablesVerify($pdo)
    {
        $draw   = $_GET["draw"] ?? 1;
        $start  = $_GET["start"] ?? 0;
        $length = $_GET["length"] ?? 10;
        $search = $_GET["search"]["value"] ?? "";

        $where  = " WHERE p.is_deleted = 0 ";
        $values = [];

        if (!empty($_GET["library_id"])) {
            $libId = (int)$_GET["library_id"];
            $where .= " AND p.parent_library_id = ? ";
            $values[] = $libId;
        
            // prevent double filtering
            unset($_GET["parent_library_id"]);
            unset($_GET["library_id"]);
        }


        /* ============================================================
        STANDARD FILTERS (UNCHANGED FROM ORIGINAL)
        ============================================================ */
        $filters = [
            "library_id"        => "p.library_id",
            "parent_library_id" => "p.parent_library_id",
            "status"            => "p.verification_status",
            "program_stage"     => "p.program_stage",
            "program_type_id"   => "p.program_type_id",
            "scale_id"          => "p.scale_id",
            "mode"              => "p.program_mode",
            "platform_id"       => "p.platform_id",
            "status_filter"     => "p.verification_status"
        ];

        foreach ($filters as $key => $col) {
            if (!empty($_GET[$key])) {
                $where   .= " AND $col = ? ";
                $values[] = $_GET[$key];
            }
        }

        /* ============================================================
        TARGET GROUP FILTER
        ============================================================ */
        if (!empty($_GET["target_group_id"])) {
            $where .= " AND EXISTS (
                SELECT 1 FROM gm_program_target_groups tg
                WHERE tg.program_id = p.program_id
                AND tg.target_group_id = ?
            )";
            $values[] = $_GET["target_group_id"];
        }

        /* ============================================================
        SEARCH
        ============================================================ */
        if (!empty($search)) {
            $kw = "%$search%";
            $where .= " AND (
                p.program_name LIKE ?
                OR p.program_details LIKE ?
                OR p.location LIKE ?
                OR p.officiated_by LIKE ?
            )";
            array_push($values, $kw, $kw, $kw, $kw);
        }

        /* ============================================================
        ORDERING
        ============================================================ */
        $columns = [
            "p.program_id",
            "p.program_name",
            "p.program_start",
            "p.program_end",
            "p.verification_status",
            "p.program_stage",
            "p.program_mode"
        ];

        $orderSql = "";
        if (!empty($_GET["order"][0]["column"])) {
            $i   = (int)$_GET["order"][0]["column"];
            $dir = ($_GET["order"][0]["dir"] === "desc") ? "DESC" : "ASC";

            if (isset($columns[$i])) {
                $orderSql = " ORDER BY {$columns[$i]} $dir ";
            }
        }

        /* ============================================================
        TOTAL COUNTS
        ============================================================ */
        $recordsTotal = $pdo
            ->query("SELECT COUNT(*) FROM gm_programs WHERE is_deleted = 0")
            ->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM gm_programs p $where");
        $stmt->execute($values);
        $recordsFiltered = $stmt->fetchColumn();

        /* ============================================================
        DATA QUERY
        ============================================================ */
        $stmt = $pdo->prepare("
            SELECT p.*, s.total_participant_count, s.physical_participant_count, s.online_participant_count
            FROM gm_programs p
            LEFT JOIN gm_program_participant_stats s
                ON s.program_id = p.program_id
            $where
            $orderSql
            LIMIT $start, $length
        ");
        $stmt->execute($values);
        $data = array_map([self::class, 'normalizeProgramRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        /* ============================================================
        TARGET GROUP NAMES
        ============================================================ */
        foreach ($data as &$row) {
            $tg = $pdo->prepare("
                SELECT tg.group_name
                FROM gm_program_target_groups pg
                JOIN gm_target_groups tg
                ON tg.id = pg.target_group_id
                WHERE pg.program_id = ?
            ");
            $tg->execute([$row["program_id"]]);
            $row["target_groups"] = $tg->fetchAll(PDO::FETCH_COLUMN);
        }

        /* ============================================================
        RESPONSE
        ============================================================ */
        Response::json([
            "draw"            => (int)$draw,
            "recordsTotal"    => (int)$recordsTotal,
            "recordsFiltered" => (int)$recordsFiltered,
            "data"            => $data
        ]);
    }

    public static function datatables_deleted_with_notes($pdo)
    {
        $draw   = $_GET["draw"] ?? 1;
        $start  = $_GET["start"] ?? 0;
        $length = $_GET["length"] ?? 10;
        $search = $_GET["search"]["value"] ?? "";

        // Deleted only
        $where  = " WHERE p.is_deleted = 1 ";
        $values = [];
        self::applySessionScope($pdo, $where, $values);

        // SAME FILTERS
        $filters = [
            "library_id"        => "p.library_id",
            "parent_library_id" => "p.parent_library_id",
            "status"            => "p.verification_status",
            "program_stage"     => "p.program_stage",
            "program_type_id"   => "p.program_type_id",
            "scale_id"          => "p.scale_id",
            "mode"              => "p.program_mode",
            "platform_id"       => "p.platform_id",
            "status_filter"     => "p.verification_status"
        ];

        foreach ($filters as $key => $col) {
            if (!empty($_GET[$key])) {
                $where .= " AND $col = ? ";
                $values[] = $_GET[$key];
            }
        }

        // Must have notes
        $where .= " AND EXISTS (
            SELECT 1 FROM gm_notes n
            WHERE n.program_id = p.program_id
        )";

        // TARGET GROUP FILTER
        if (!empty($_GET["target_group_id"])) {
            $where .= " AND EXISTS (
                SELECT 1 FROM gm_program_target_groups tg
                WHERE tg.program_id = p.program_id
                AND tg.target_group_id = ?
            )";
            $values[] = $_GET["target_group_id"];
        }

        // SEARCH
        if (!empty($search)) {
            $kw = "%$search%";
            $where .= " AND (
                p.program_name LIKE ?
                OR p.program_details LIKE ?
                OR p.location LIKE ?
                OR p.officiated_by LIKE ?
            )";
            array_push($values, $kw, $kw, $kw, $kw);
        }

        // ORDER
        $columns = [
            "p.program_id",
            "p.program_name",
            "p.program_start",
            "p.program_end",
            "p.verification_status",
            "p.program_stage",
            "p.program_mode"
        ];

        $orderSql = "";
        if (!empty($_GET["order"][0]["column"])) {
            $i   = intval($_GET["order"][0]["column"]);
            $dir = ($_GET["order"][0]["dir"] === "desc") ? "DESC" : "ASC";
            if (isset($columns[$i])) {
                $orderSql = " ORDER BY {$columns[$i]} $dir ";
            }
        }

        // TOTAL DELETED
        $recordsTotal = $pdo
            ->query("SELECT COUNT(*) FROM gm_programs WHERE is_deleted = 1")
            ->fetchColumn();

        // FILTERED COUNT
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM gm_programs p $where");
        $stmt->execute($values);
        $recordsFiltered = $stmt->fetchColumn();

        // DATA
        $stmt = $pdo->prepare("
            SELECT
                p.*,
                s.total_participant_count,
                s.physical_participant_count,
                s.online_participant_count,
                n.note_text,
                n.created_at AS note_created_at,
                n.note_role AS note_role
            FROM gm_programs p
            LEFT JOIN gm_program_participant_stats s
                ON s.program_id = p.program_id
            LEFT JOIN gm_notes n
                ON n.program_id = p.program_id
            $where
            $orderSql
            LIMIT $start, $length
        ");
        $stmt->execute($values);
        $data = array_map([self::class, 'normalizeProgramRow'], $stmt->fetchAll(PDO::FETCH_ASSOC));

        // TARGET GROUPS
        foreach ($data as &$row) {
            $tg = $pdo->prepare("
                SELECT tg.group_name
                FROM gm_program_target_groups pg
                JOIN gm_target_groups tg ON tg.id = pg.target_group_id
                WHERE pg.program_id = ?
            ");
            $tg->execute([$row["program_id"]]);
            $row["target_groups"] = $tg->fetchAll(PDO::FETCH_COLUMN);
        }

        Response::json([
            "draw"            => intval($draw),
            "recordsTotal"    => $recordsTotal,
            "recordsFiltered" => $recordsFiltered,
            "data"            => $data
        ]);
    }



}

?>
