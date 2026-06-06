<?php

class DashboardController
{
    /* =========================================================
       BUILD VISIBILITY FILTER
       ========================================================= */
    private static function buildWhere(array &$params): string
    {
        $role      = (int)($_SESSION['role'] ?? 0);
        $libraryId = (int)($_SESSION['library_id'] ?? 0);

        // ROLE 1 — HQ (no restriction)
        if ($role === 1) {
            return "";
        }

        if (($role === 2 || $role === 3) && $libraryId > 0) {
            list($scopeSql, $scopeValues) = LibraryHierarchy::programScopeWhere($GLOBALS['pdo'], $libraryId, 'library_id');
            foreach ($scopeValues as $value) {
                $params[] = $value;
            }
            return $scopeSql;
        }

        // Safety fallback
        return " AND 1 = 0 ";
    }


    /* =========================================================
       DASHBOARD SUMMARY (CARDS)
       ========================================================= */
    public static function summary(PDO $pdo)
    {
        $params = [];
        $where  = self::buildWhere($params);

        $stmt = $pdo->prepare("
            SELECT
                COUNT(*)                                            AS total,
                SUM(verification_status = 'verified')                AS verified,
                SUM(verification_status = 'pending')                 AS pending,
                SUM(verification_status = 'rejected')                AS rejected
            FROM gm_programs
            WHERE is_deleted = 0
            $where
        ");
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        Response::json([
            'success' => true,
            'cards' => [
                'total'    => (int)$row['total'],
                'verified' => (int)$row['verified'],
                'pending'  => (int)$row['pending'],
                'rejected' => (int)$row['rejected'],
            ]
        ]);
    }

    /* =========================================================
       VERIFIED PROGRAMS GRAPH (CURRENT MONTH, PER DAY)
       ========================================================= */
    public static function verifiedGraph(PDO $pdo)
    {
        $params = [];
        $where  = self::buildWhere($params);

        $stmt = $pdo->prepare("
            SELECT
                DATE(program_start) AS day,
                COUNT(*)            AS total
            FROM gm_programs
            WHERE is_deleted = 0
              AND verification_status = 'verified'
              AND MONTH(program_start) = MONTH(CURDATE())
              AND YEAR(program_start)  = YEAR(CURDATE())
              $where
            GROUP BY DATE(program_start)
            ORDER BY day ASC
        ");
        $stmt->execute($params);

        Response::json([
            'success' => true,
            'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }
    
    /* =========================================================
       VERIFIED PROGRAMS GRAPH (CURRENT YEAR, PER MONTH)
       ========================================================= */
    public static function verifiedMonthly(PDO $pdo)
    {
        $params = [];
        $where  = self::buildWhere($params);
    
        // Init 12 months with zero
        $months = array_fill(1, 12, 0);
    
        $stmt = $pdo->prepare("
            SELECT
                MONTH(program_start) AS month,
                COUNT(*)             AS total
            FROM gm_programs
            WHERE is_deleted = 0
              AND verification_status = 'verified'
              AND YEAR(program_start) = YEAR(CURDATE())
              $where
            GROUP BY MONTH(program_start)
            ORDER BY month
        ");
        $stmt->execute($params);
    
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $months[(int)$row['month']] = (int)$row['total'];
        }
    
        // Re-index to 0–11 for JS (Jan = 0)
        $data = array_values($months);
    
        Response::json([
            'success' => true,
            'monthly' => $data
        ]);
    }

    /* =========================================================
       RECENT PROGRAMS (LAST 5, ANY STATUS)
       ========================================================= */
    public static function recent(PDO $pdo)
    {
        $params = [];
        $where  = self::buildWhere($params);

        $stmt = $pdo->prepare("
            SELECT
                program_id,
                program_name,
                verification_status AS status,
                program_start,
                created_at
            FROM gm_programs
            WHERE is_deleted = 0
            $where
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->execute($params);

        Response::json([
            'success' => true,
            'programs' => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }
}
?>
