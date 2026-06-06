<?php

class LibraryHierarchy
{
    const MAX_DEPTH = 4;

    public static function getLibrary(PDO $pdo, $libraryId)
    {
        if (empty($libraryId)) {
            return null;
        }

        $stmt = $pdo->prepare("
            SELECT id, name, type_id, parent_id, status
            FROM gm_libraries
            WHERE id = ?
            LIMIT 1
        ");
        $stmt->execute([(int)$libraryId]);
        $library = $stmt->fetch(PDO::FETCH_ASSOC);

        return $library ?: null;
    }

    public static function getAncestors(PDO $pdo, $libraryId)
    {
        $ancestors = [];
        $seen = [];
        $current = self::getLibrary($pdo, $libraryId);

        while ($current && !empty($current['parent_id'])) {
            $parentId = (int)$current['parent_id'];

            if (isset($seen[$parentId])) {
                break;
            }

            $seen[$parentId] = true;
            $parent = self::getLibrary($pdo, $parentId);

            if (!$parent) {
                break;
            }

            $ancestors[] = $parent;
            $current = $parent;
        }

        return $ancestors;
    }

    public static function getDepth(PDO $pdo, $libraryId)
    {
        $library = self::getLibrary($pdo, $libraryId);
        if (!$library) {
            return 0;
        }

        return count(self::getAncestors($pdo, $libraryId)) + 1;
    }

    public static function getParentId(PDO $pdo, $libraryId)
    {
        $library = self::getLibrary($pdo, $libraryId);
        return $library ? $library['parent_id'] : null;
    }

    public static function getRootId(PDO $pdo, $libraryId)
    {
        $root = self::getLibrary($pdo, $libraryId);
        if (!$root) {
            return null;
        }

        foreach (self::getAncestors($pdo, $libraryId) as $ancestor) {
            $root = $ancestor;
        }

        return (int)$root['id'];
    }

    public static function getDescendantIds(PDO $pdo, $libraryId, $includeSelf = true)
    {
        if (empty($libraryId)) {
            return [];
        }

        $ids = $includeSelf ? [(int)$libraryId] : [];
        $frontier = [(int)$libraryId];
        $seen = [(int)$libraryId => true];

        while (!empty($frontier)) {
            $placeholders = implode(',', array_fill(0, count($frontier), '?'));
            $stmt = $pdo->prepare("
                SELECT id
                FROM gm_libraries
                WHERE parent_id IN ($placeholders)
            ");
            $stmt->execute($frontier);
            $children = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

            $frontier = [];
            foreach ($children as $childId) {
                if (isset($seen[$childId])) {
                    continue;
                }

                $seen[$childId] = true;
                $ids[] = $childId;
                $frontier[] = $childId;
            }
        }

        return $ids;
    }

    public static function assertCanCreateChild(PDO $pdo, $parentId)
    {
        $parent = self::getLibrary($pdo, $parentId);
        if (!$parent) {
            return "Parent library not found";
        }

        if ((int)self::getDepth($pdo, $parentId) >= self::MAX_DEPTH) {
            return "Maximum library depth is " . self::MAX_DEPTH . " layers";
        }

        return true;
    }

    public static function isAncestorOrSelf(PDO $pdo, $ancestorId, $libraryId)
    {
        if (empty($ancestorId) || empty($libraryId)) {
            return false;
        }

        if ((int)$ancestorId === (int)$libraryId) {
            return true;
        }

        foreach (self::getAncestors($pdo, $libraryId) as $ancestor) {
            if ((int)$ancestor['id'] === (int)$ancestorId) {
                return true;
            }
        }

        return false;
    }

    public static function canVerifyProgram(PDO $pdo, $userId, $programId)
    {
        $stmt = $pdo->prepare("
            SELECT u.role_id, u.library_id AS user_library_id,
                   p.library_id AS program_library_id, p.parent_library_id
            FROM gm_users u
            JOIN gm_programs p ON p.program_id = ?
            WHERE u.id = ?
              AND u.status = 'active'
              AND p.is_deleted = 0
            LIMIT 1
        ");
        $stmt->execute([(int)$programId, (int)$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return false;
        }

        if ((int)$row['role_id'] === 1) {
            return true;
        }

        return !empty($row['parent_library_id'])
            && (int)$row['user_library_id'] === (int)$row['parent_library_id'];
    }

    public static function programScopeWhere(PDO $pdo, $libraryId, $column = 'p.library_id')
    {
        $ids = self::getDescendantIds($pdo, $libraryId, true);
        if (empty($ids)) {
            return [' AND 1 = 0 ', []];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return [" AND $column IN ($placeholders) ", $ids];
    }
}

?>
