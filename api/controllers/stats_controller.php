<?php

class Stats_Controller
{
    /* ========================================================
       HELPER: Build dynamic filter SQL fragments
       If $onlyVerified = true, force p.verification_status = 'verified'
       Used by all endpoints EXCEPT program-status
    ======================================================== */
    private static function build_filters($pdo, &$values, $onlyVerified = true)
    {
        $where = " WHERE p.is_deleted = 0 ";

        // Force verified-only programs (for analytics)
        if ($onlyVerified) {
            $where .= " AND p.verification_status = 'verified' ";
        }

        // Filters allowed (FIXED library_type to use gm_libraries)
        $filters = [
            // FIX: use l.type_id, not p.library_type_id
            "library_type_id"   => "l.type_id",

            "program_type_id"   => "p.program_type_id",
            "scale_id"          => "p.scale_id",
            "mode"              => "p.program_mode",
            "platform_id"       => "p.platform_id"
        ];

        // Status filter only applies when verified-only mode is OFF
        if (!$onlyVerified) {
            $filters["status"] = "p.verification_status";
        }
        
        $role = intval($_SESSION['role'] ?? ($_GET['role_id'] ?? 0));
        $sessionLibraryId = intval($_SESSION['library_id'] ?? 0);
        $scopeLibraryId = null;

        if ($role !== 1 && $sessionLibraryId > 0) {
            $scopeLibraryId = $sessionLibraryId;
        } elseif (!empty($_GET['library_id'])) {
            $scopeLibraryId = (int)$_GET['library_id'];
        } elseif (!empty($_GET['parent_library_id'])) {
            $scopeLibraryId = (int)$_GET['parent_library_id'];
        }

        if ($scopeLibraryId) {
            list($scopeSql, $scopeValues) = LibraryHierarchy::programScopeWhere($pdo, $scopeLibraryId, 'p.library_id');
            $where .= $scopeSql;
            foreach ($scopeValues as $scopeValue) {
                $values[] = $scopeValue;
            }
        }

        foreach ($filters as $get => $column) {
            if (!empty($_GET[$get])) {
                $where .= " AND $column = ? ";
                $values[] = $_GET[$get];
            }
        }

        // Date range filters
        if (!empty($_GET["date_from"])) {
            $where .= " AND p.program_start >= ? ";
            $values[] = $_GET["date_from"];
        }

        if (!empty($_GET["date_to"])) {
            $where .= " AND p.program_start <= ? ";
            $values[] = $_GET["date_to"];
        }

        // UNIVERSAL SEARCH: library name
        if (!empty($_GET["search"])) {
            $where .= " AND l.name LIKE ? ";
            $values[] = "%" . $_GET["search"] . "%";
        }

        return $where;
    }

    /* ========================================================
       1. PROGRAM STATUS COUNTS (all statuses)
       route: stats/program-status
    ======================================================== */
 public static function program_status($pdo)
    {
        $values = [];
        $where = self::build_filters($pdo, $values, false); // show all statuses

        /* ======================================================
        1) OVERALL STATUS TOTALS
        ====================================================== */
        $sqlTotal = "
            SELECT 
                SUM(p.verification_status = 'incomplete') AS incomplete,
                SUM(p.verification_status = 'pending')    AS pending,
                SUM(p.verification_status = 'verified')   AS verified,
                SUM(p.verification_status = 'rejected')   AS rejected
            FROM gm_programs p
            JOIN gm_libraries l ON l.id = p.library_id
            $where
        ";

        $stmt = $pdo->prepare($sqlTotal);
        $stmt->execute($values);
        $total = $stmt->fetch(PDO::FETCH_ASSOC);

        /* ======================================================
        2) STATUS BY PROGRAM TYPE
        ====================================================== */
        $sqlByType = "
            SELECT
                pt.id AS program_type_id,
                pt.type_name,
                SUM(p.verification_status = 'incomplete') AS incomplete,
                SUM(p.verification_status = 'pending')    AS pending,
                SUM(p.verification_status = 'verified')   AS verified,
                SUM(p.verification_status = 'rejected')   AS rejected
            FROM gm_programs p
            JOIN gm_program_types pt ON pt.id = p.program_type_id
            JOIN gm_libraries l ON l.id = p.library_id
            $where
            GROUP BY pt.id, pt.type_name
            ORDER BY pt.type_name
        ";

        $stmt = $pdo->prepare($sqlByType);
        $stmt->execute($values);
        $byType = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /* ======================================================
        RESPONSE
        ====================================================== */
        Response::json([
            "success" => true,
            "status" => [
                "overall" => [
                    "incomplete" => intval($total["incomplete"]),
                    "pending"    => intval($total["pending"]),
                    "verified"   => intval($total["verified"]),
                    "rejected"   => intval($total["rejected"])
                ],
                "by_program_type" => array_map(function ($r) {
                    return [
                        "program_type_id" => (int)$r["program_type_id"],
                        "type_name"       => $r["type_name"],
                        "incomplete"      => (int)$r["incomplete"],
                        "pending"         => (int)$r["pending"],
                        "verified"        => (int)$r["verified"],
                        "rejected"        => (int)$r["rejected"]
                    ];
                }, $byType)
            ]
        ]);
    }


    /* ========================================================
       2. PROGRAM TYPE COUNTS PER LIBRARY
       route: stats/program-type
       VERIFIED ONLY
    ======================================================== */
    public static function program_type($pdo)
    {
        $values = [];
        $where = self::build_filters($pdo, $values, true);

        $sql = "
            SELECT 
                t.type_name,
                l.name AS library_name,
                COUNT(*) AS total
            FROM gm_programs p
            JOIN gm_program_types t ON t.id = p.program_type_id
            JOIN gm_libraries l ON l.id = p.library_id
            $where
            GROUP BY p.library_id, p.program_type_id
            ORDER BY l.name, t.type_name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        Response::json([
            "success" => true,
            "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /* ========================================================
       3. SCALE COUNTS
       route: stats/scale
       VERIFIED ONLY
    ======================================================== */
    public static function scale($pdo)
    {
        $values = [];
        $where = self::build_filters($pdo, $values, true);

        $sql = "
            SELECT 
                s.scale_name,
                l.name AS library_name,
                COUNT(*) AS total
            FROM gm_programs p
            JOIN gm_scales s ON s.id = p.scale_id
            JOIN gm_libraries l ON l.id = p.library_id
            $where
            GROUP BY p.library_id, p.scale_id
            ORDER BY l.name, s.scale_name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        Response::json([
            "success" => true,
            "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /* ========================================================
       4. MODE COUNTS
       route: stats/mode
       VERIFIED ONLY
    ======================================================== */
    public static function mode($pdo)
    {
        $values = [];
        $where = self::build_filters($pdo, $values, true);

        $sql = "
            SELECT 
                p.program_mode AS mode,
                l.name AS library_name,
                COUNT(*) AS total
            FROM gm_programs p
            JOIN gm_libraries l ON l.id = p.library_id
            $where
            GROUP BY p.library_id, p.program_mode
            ORDER BY l.name, p.program_mode
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        Response::json([
            "success" => true,
            "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /* ========================================================
       5. PROGRAM TARGET GROUP COUNTS
       route: stats/program-target
       VERIFIED ONLY
    ======================================================== */
    public static function program_target($pdo)
    {
        $values = [];
        $where = self::build_filters($pdo, $values, true);

        $sql = "
            SELECT 
                tg.group_name,
                l.name AS library_name,
                COUNT(DISTINCT p.program_id) AS total
            FROM gm_program_target_groups ptg
            JOIN gm_programs p ON p.program_id = ptg.program_id
            JOIN gm_target_groups tg ON tg.id = ptg.target_group_id
            JOIN gm_libraries l ON l.id = p.library_id
            $where
            GROUP BY p.library_id, ptg.target_group_id
            ORDER BY l.name, tg.group_name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        Response::json([
            "success" => true,
            "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /* ========================================================
       6. PARTICIPANT SUMMARY
       route: stats/participants
       VERIFIED ONLY
    ======================================================== */
    public static function participants($pdo)
    {
        $values = [];
        $where = self::build_filters($pdo, $values, true);

        $sql = "
            SELECT 
                l.name AS library_name,
                SUM(pps.total_participant_count) AS total,
                SUM(pps.physical_participant_count) AS physical,
                SUM(pps.online_participant_count) AS online,
                SUM(pps.self_registered_participant_count) AS self_registered,
                SUM(pps.staff_uploaded_participant_count) AS staff_uploaded
            FROM gm_program_participant_stats pps
            JOIN gm_programs p ON p.program_id = pps.program_id
            JOIN gm_libraries l ON l.id = p.library_id
            $where
            GROUP BY p.library_id
            ORDER BY l.name
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        Response::json([
            "success" => true,
            "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /* ========================================================
       7. SEARCH PROGRAMS (Google-style)
       VERIFIED ONLY
    ======================================================== */
    public static function search_programs($pdo)
    {
        $q = $_GET["q"] ?? "";
        $limit = intval($_GET["limit"] ?? 20);
        $offset = intval($_GET["offset"] ?? 0);

        $values = [];
        $where = self::build_filters($pdo, $values, true);

        if (!empty($q)) {
            $where .= " AND p.program_name LIKE ? ";
            $values[] = "%$q%";
        }

        $sql = "
            SELECT 
                p.program_id,
                p.program_name,
                p.program_start,
                p.program_mode AS mode,
                p.verification_status AS status,
                l.name AS library_name
            FROM gm_programs p
            JOIN gm_libraries l ON l.id = p.library_id
            $where
            ORDER BY p.program_start DESC
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);

        Response::json([
            "success" => true,
            "results" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

    /* ========================================================
       8. PROGRAM DETAIL
       route: stats/program-detail
    ======================================================== */
    public static function program_detail($pdo)
    {
        if (empty($_GET["program_id"])) {
            Response::json(["error" => "program_id required"], 400);
        }

        $programId = (int)$_GET["program_id"];

        $sql = "
            SELECT
                p.*,
                p.program_mode AS mode,
                p.verification_status AS status,
                l.name AS library_name,
                pl.name AS parent_library_name,
                pt.type_name AS program_type_name,
                sc.scale_name,
                pf.platform_name,
                s.total_participant_count,
                s.physical_participant_count,
                s.online_participant_count,
                s.self_registered_participant_count,
                s.staff_uploaded_participant_count,
                s.is_manual_override
            FROM gm_programs p
            JOIN gm_libraries l ON l.id = p.library_id
            LEFT JOIN gm_libraries pl ON pl.id = p.parent_library_id
            LEFT JOIN gm_program_types pt ON pt.id = p.program_type_id
            LEFT JOIN gm_scales sc ON sc.id = p.scale_id
            LEFT JOIN gm_platforms pf ON pf.id = p.platform_id
            LEFT JOIN gm_program_participant_stats s ON s.program_id = p.program_id
            WHERE p.program_id = ?
              AND p.is_deleted = 0
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([$programId]);
        $program = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$program) {
            Response::json(["error" => "Program not found"], 404);
        }

        $role = (int)($_SESSION['role'] ?? 0);
        $sessionLibraryId = (int)($_SESSION['library_id'] ?? 0);
        if ($role !== 1 && !LibraryHierarchy::isAncestorOrSelf($pdo, $sessionLibraryId, $program['library_id'])) {
            Response::json(["error" => "Program not found"], 404);
        }

        $tg = $pdo->prepare("
            SELECT tg.id, tg.group_name
            FROM gm_program_target_groups ptg
            JOIN gm_target_groups tg ON tg.id = ptg.target_group_id
            WHERE ptg.program_id = ?
            ORDER BY tg.group_name
        ");
        $tg->execute([$programId]);
        $program["target_groups"] = $tg->fetchAll(PDO::FETCH_ASSOC);

        Response::json([
            "success" => true,
            "program" => $program
        ]);
    }
}

?>
