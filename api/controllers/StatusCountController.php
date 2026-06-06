<?php

class StatusCountController
{
    /* ============================================
       GET: status count
       route: statuscount/summary

       OPTIONAL filters (any combination):
         parent_library_id=#
         library_id=#
         library_type_id=#
         program_type_id=#

       RETURNS (always same shape):
         {
           success: true,
           summary: {
             incomplete: #,
             pending: #,
             verified: #,
             rejected: #
           }
         }
    ============================================ */
    public static function summary(PDO $pdo)
    {
        // Optional filters
        $parentId      = $_GET['parent_library_id'] ?? null;
        $libraryId     = $_GET['library_id'] ?? null;
        $libraryTypeId = $_GET['library_type_id'] ?? null;
        $programTypeId = $_GET['program_type_id'] ?? null;

        $where  = " WHERE is_deleted = 0 ";
        $values = [];

        $role = (int)($_SESSION['role'] ?? 0);
        $sessionLibraryId = (int)($_SESSION['library_id'] ?? 0);
        $scopeLibraryId = null;

        if ($role !== 1 && $sessionLibraryId > 0) {
            $scopeLibraryId = $sessionLibraryId;
        } elseif (!empty($libraryId)) {
            $scopeLibraryId = (int)$libraryId;
        } elseif (!empty($parentId)) {
            $scopeLibraryId = (int)$parentId;
        }

        if ($scopeLibraryId) {
            list($scopeSql, $scopeValues) = LibraryHierarchy::programScopeWhere($pdo, $scopeLibraryId, 'library_id');
            $where .= $scopeSql;
            foreach ($scopeValues as $scopeValue) {
                $values[] = $scopeValue;
            }
        }

        if (!empty($libraryTypeId)) {
            $where .= " AND library_type_id = ? ";
            $values[] = $libraryTypeId;
        }

        if (!empty($programTypeId)) {
            $where .= " AND program_type_id = ? ";
            $values[] = $programTypeId;
        }

        // Flat summary for backward compatibility.
        $sql = "
            SELECT
                SUM(verification_status = 'incomplete') AS incomplete,
                SUM(verification_status = 'pending')    AS pending,
                SUM(verification_status = 'verified')   AS verified,
                SUM(verification_status = 'rejected')   AS rejected
            FROM gm_programs
            $where
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($values);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        Response::json([
            "success" => true,
            "summary" => [
                "incomplete" => (int)($row['incomplete'] ?? 0),
                "pending"    => (int)($row['pending'] ?? 0),
                "verified"   => (int)($row['verified'] ?? 0),
                "rejected"   => (int)($row['rejected'] ?? 0)
            ]
        ]);
    }
}
