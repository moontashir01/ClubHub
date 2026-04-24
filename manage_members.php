<?php
session_start();
include 'connection.php';



if (!isset($_SESSION['manage_members_csrf'])) {
    $_SESSION['manage_members_csrf'] = bin2hex(random_bytes(32));
}

function findClubForUser(mysqli $con, string $email, bool $ebOnly = true): ?array
{
    $roleClause = $ebOnly ? " AND UPPER(cm.Role) LIKE 'EB%'" : '';
    $sql = "
        SELECT c.club_id, c.club_name
        FROM students s
        INNER JOIN club_members cm ON cm.student_id = s.student_id AND cm.active = 1
        INNER JOIN clubs c ON c.club_id = cm.club_id
        WHERE s.student_email = ?
        $roleClause
        ORDER BY cm.member_id ASC
        LIMIT 1
    ";

    $stmt = $con->prepare($sql);
    if (!$stmt) {
        return null;
    }

    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row ?: null;
}

function setMembersFlash(string $type, string $message): void
{
    $_SESSION['manage_members_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function normalizeRoleInput(string $value): string
{
    $value = trim($value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim((string)$value);
}

function normalizeEbRoleName(string $roleName, bool &$autoPrefixed = false): string
{
    $roleName = normalizeRoleInput($roleName);
    $hadEbPrefix = preg_match('/^EB([\s\-_:]|$)/i', $roleName) === 1;
    $withoutPrefix = preg_replace('/^EB[\s\-_:]*/i', '', $roleName);
    $withoutPrefix = preg_replace('/^Executive\s*Board[\s\-_:]*/i', '', (string)$withoutPrefix);
    $withoutPrefix = normalizeRoleInput((string)$withoutPrefix);

    if ($withoutPrefix === '') {
        $withoutPrefix = 'Executive';
    }

    $autoPrefixed = !$hadEbPrefix;
    return 'EB-' . $withoutPrefix;
}

function roleDisplayName(string $roleName): string
{
    $normalized = normalizeRoleInput($roleName);
    $withoutPrefix = preg_replace('/^EB[\s\-_:]*/i', '', $normalized);
    $withoutPrefix = normalizeRoleInput((string)$withoutPrefix);

    if ($withoutPrefix === '') {
        return 'Executive';
    }

    return $withoutPrefix;
}

function buildManageMembersUrl(string $searchQuery, bool $openRolesModal): string
{
    $params = [];
    if ($searchQuery !== '') {
        $params['q'] = $searchQuery;
    }
    if ($openRolesModal) {
        $params['roles'] = '1';
    }

    if (empty($params)) {
        return 'manage_members.php';
    }

    return 'manage_members.php?' . http_build_query($params);
}

$email = (string)$_SESSION['Email'];
$displayName = $_SESSION['Name'] ?? null;
if (!$displayName && isset($_SESSION['Email'])) {
    $displayName = explode('@', (string)$_SESSION['Email'])[0];
}
if (!$displayName) {
    $displayName = 'Guest';
}

$clubId = intval($_SESSION['club_id'] ?? ($_SESSION['Club_id'] ?? 0));
$clubName = '';

if ($clubId > 0) {
    $clubStmt = $con->prepare('SELECT club_name FROM clubs WHERE club_id = ? LIMIT 1');
    if ($clubStmt) {
        $clubStmt->bind_param('i', $clubId);
        $clubStmt->execute();
        $nameRow = $clubStmt->get_result()->fetch_assoc();
        $clubName = $nameRow['club_name'] ?? '';
        $clubStmt->close();
    }
}

if ($clubId <= 0 || $clubName === '') {
    $clubInfo = findClubForUser($con, $email, true);
    if (!$clubInfo) {
        $clubInfo = findClubForUser($con, $email, false);
    }

    if ($clubInfo) {
        $clubId = intval($clubInfo['club_id']);
        $clubName = (string)$clubInfo['club_name'];
        $_SESSION['club_id'] = $clubId;
    }
}

$baseRoles = ['Member', 'Executive', 'President', 'EB-Executive', 'EB-President'];
$customRoles = [];
$memberRoleNames = [];
$roleTableReady = false;

if ($clubId > 0) {
    $checkRoleTableSql = 'SELECT 1 FROM club_role_definitions LIMIT 1';
    $roleTableReady = mysqli_query($con, $checkRoleTableSql) !== false;

    if ($roleTableReady) {
        $rolesStmt = $con->prepare(
            'SELECT role_id, role_name
             FROM club_role_definitions
             WHERE club_id = ? AND is_active = 1
             ORDER BY role_name ASC'
        );

        if ($rolesStmt) {
            $rolesStmt->bind_param('i', $clubId);
            $rolesStmt->execute();
            $rolesResult = $rolesStmt->get_result();

            while ($row = $rolesResult->fetch_assoc()) {
                $customRoles[] = $row;
            }

            $rolesStmt->close();
        }
    }
}

$memberRolesStmt = null;
if ($clubId > 0) {
    $memberRolesStmt = $con->prepare(
        'SELECT DISTINCT Role AS role_name
         FROM club_members
         WHERE club_id = ?
           AND active = 1
           AND Role IS NOT NULL
           AND TRIM(Role) <> ""
         ORDER BY role_name ASC'
    );

    if ($memberRolesStmt) {
        $memberRolesStmt->bind_param('i', $clubId);
        $memberRolesStmt->execute();
        $memberRolesResult = $memberRolesStmt->get_result();

        while ($roleRow = $memberRolesResult->fetch_assoc()) {
            $normalized = normalizeRoleInput((string)$roleRow['role_name']);
            if ($normalized !== '') {
                $memberRoleNames[] = $normalized;
            }
        }

        $memberRolesStmt->close();
    }
}

$customRoleNames = array_map(
    static function (array $row): string {
        return (string)$row['role_name'];
    },
    $customRoles
);
$customRoleNames = array_values(array_unique($customRoleNames));
$memberRoleNames = array_values(array_unique($memberRoleNames));
$allowedRoles = array_values(array_unique(array_merge($baseRoles, $customRoleNames, $memberRoleNames)));

$customRoleLookup = array_fill_keys($customRoleNames, true);
$baseRoleLookup = array_fill_keys($baseRoles, true);

$searchQuery = trim((string)($_GET['q'] ?? ''));
$openRolesModal = ((string)($_GET['roles'] ?? '')) === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = trim((string)($_POST['action'] ?? ''));
    $redirectQ = trim((string)($_POST['redirect_q'] ?? ''));
    $redirectRoles = ((string)($_POST['redirect_roles'] ?? '')) === '1';
    $redirectUrl = buildManageMembersUrl($redirectQ, $redirectRoles);

    $csrfToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals((string)$_SESSION['manage_members_csrf'], $csrfToken)) {
        setMembersFlash('error', 'Security check failed. Please try again.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($clubId <= 0) {
        setMembersFlash('error', 'No club is linked to this account, so members cannot be managed.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'define_role') {
        if (!$roleTableReady) {
            setMembersFlash('error', 'Role definition is unavailable right now. Please try again.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $roleName = normalizeRoleInput((string)($_POST['role_name'] ?? ''));
        $isEbRole = ((string)($_POST['is_eb_role'] ?? '')) === '1';

        if ($roleName === '') {
            setMembersFlash('error', 'Please enter a role name.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (strlen($roleName) > 100) {
            setMembersFlash('error', 'Role name is too long. Please keep it under 100 characters.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $autoPrefixed = false;
        $looksLikeEb = preg_match('/^EB([\s\-_:]|$)/i', $roleName) === 1
            || stripos($roleName, 'executive board') !== false;
        if ($isEbRole || $looksLikeEb) {
            $roleName = normalizeEbRoleName($roleName, $autoPrefixed);
        }

        $insertRoleStmt = $con->prepare(
            'INSERT INTO club_role_definitions (club_id, role_name, is_active)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE
                is_active = 1,
                role_name = VALUES(role_name)'
        );

        if (!$insertRoleStmt) {
            setMembersFlash('error', 'Failed to save role. Please try again.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $insertRoleStmt->bind_param('is', $clubId, $roleName);
        $insertRoleStmt->execute();
        $insertRoleStmt->close();

        if ($isEbRole && $autoPrefixed) {
            setMembersFlash('success', 'Role saved. EB prefix was added automatically.');
        } else {
            setMembersFlash('success', 'Role saved successfully.');
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'remove_defined_role') {
        if (!$roleTableReady) {
            setMembersFlash('error', 'Role definition is unavailable right now. Please try again.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $roleId = intval($_POST['role_id'] ?? 0);
        if ($roleId <= 0) {
            setMembersFlash('error', 'Invalid role selected.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $findRoleStmt = $con->prepare('SELECT role_name FROM club_role_definitions WHERE role_id = ? AND club_id = ? LIMIT 1');
        $findRoleStmt->bind_param('ii', $roleId, $clubId);
        $findRoleStmt->execute();
        $roleRow = $findRoleStmt->get_result()->fetch_assoc();
        $findRoleStmt->close();

        if (!$roleRow) {
            setMembersFlash('error', 'Role not found for this club.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $roleName = (string)$roleRow['role_name'];

        $usageStmt = $con->prepare('SELECT COUNT(*) AS total FROM club_members WHERE club_id = ? AND Role = ? AND active = 1');
        $usageStmt->bind_param('is', $clubId, $roleName);
        $usageStmt->execute();
        $usageRow = $usageStmt->get_result()->fetch_assoc();
        $usageStmt->close();

        $inUse = intval($usageRow['total'] ?? 0) > 0;
        if ($inUse) {
            setMembersFlash('error', 'Role is assigned to active members and cannot be removed yet.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $removeRoleStmt = $con->prepare('UPDATE club_role_definitions SET is_active = 0 WHERE role_id = ? AND club_id = ?');
        $removeRoleStmt->bind_param('ii', $roleId, $clubId);
        $removeRoleStmt->execute();
        $removeRoleStmt->close();

        setMembersFlash('success', 'Role removed from available options.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'add_member') {
        $studentId = trim((string)($_POST['student_id'] ?? ''));
        $role = trim((string)($_POST['role'] ?? 'Member'));

        if ($studentId === '') {
            setMembersFlash('error', 'Student ID is required.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (!in_array($role, $allowedRoles, true)) {
            $role = 'Member';
        }

        $studentStmt = $con->prepare('SELECT student_id FROM students WHERE student_id = ? LIMIT 1');
        $studentStmt->bind_param('s', $studentId);
        $studentStmt->execute();
        $studentExists = $studentStmt->get_result()->num_rows > 0;
        $studentStmt->close();

        if (!$studentExists) {
            setMembersFlash('error', 'Student not found.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $activeStmt = $con->prepare('SELECT member_id FROM club_members WHERE club_id = ? AND student_id = ? AND active = 1 LIMIT 1');
        $activeStmt->bind_param('is', $clubId, $studentId);
        $activeStmt->execute();
        $activeRow = $activeStmt->get_result()->fetch_assoc();
        $activeStmt->close();

        if ($activeRow) {
            setMembersFlash('error', 'This student is already an active member of your club.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $inactiveStmt = $con->prepare('SELECT member_id FROM club_members WHERE club_id = ? AND student_id = ? AND active = 0 ORDER BY member_id DESC LIMIT 1');
        $inactiveStmt->bind_param('is', $clubId, $studentId);
        $inactiveStmt->execute();
        $inactiveRow = $inactiveStmt->get_result()->fetch_assoc();
        $inactiveStmt->close();

        if ($inactiveRow) {
            $memberId = intval($inactiveRow['member_id']);
            $reactivateStmt = $con->prepare('UPDATE club_members SET Role = ?, active = 1 WHERE member_id = ? AND club_id = ?');
            $reactivateStmt->bind_param('sii', $role, $memberId, $clubId);
            $reactivateStmt->execute();
            $reactivateStmt->close();
            setMembersFlash('success', 'Member reactivated and added back to your club.');
        } else {
            $insertStmt = $con->prepare('INSERT INTO club_members (student_id, club_id, Role, active) VALUES (?, ?, ?, 1)');
            $insertStmt->bind_param('sis', $studentId, $clubId, $role);
            $insertStmt->execute();
            $insertStmt->close();
            setMembersFlash('success', 'Member added to your club successfully.');
        }

        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'update_role') {
        $memberId = intval($_POST['member_id'] ?? 0);
        $newRole = trim((string)($_POST['role'] ?? ''));

        if ($memberId <= 0 || $newRole === '') {
            setMembersFlash('error', 'Invalid role update request.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $memberStmt = $con->prepare('SELECT Role FROM club_members WHERE member_id = ? AND club_id = ? AND active = 1 LIMIT 1');
        $memberStmt->bind_param('ii', $memberId, $clubId);
        $memberStmt->execute();
        $memberRow = $memberStmt->get_result()->fetch_assoc();
        $memberStmt->close();

        if (!$memberRow) {
            setMembersFlash('error', 'Member not found in this club.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $currentRole = (string)$memberRow['Role'];
        if ($newRole === $currentRole) {
            setMembersFlash('success', 'Role is already set to the selected value.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        if (!in_array($newRole, $allowedRoles, true)) {
            setMembersFlash('error', 'Please choose a valid role.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $updateStmt = $con->prepare('UPDATE club_members SET Role = ? WHERE member_id = ? AND club_id = ? AND active = 1');
        $updateStmt->bind_param('sii', $newRole, $memberId, $clubId);
        $updateStmt->execute();
        $updateStmt->close();

        setMembersFlash('success', 'Member role updated successfully.');
        header('Location: ' . $redirectUrl);
        exit;
    }

    if ($action === 'remove_member') {
        $memberId = intval($_POST['member_id'] ?? 0);

        if ($memberId <= 0) {
            setMembersFlash('error', 'Invalid member selected.');
            header('Location: ' . $redirectUrl);
            exit;
        }

        $removeStmt = $con->prepare('UPDATE club_members SET active = 0 WHERE member_id = ? AND club_id = ? AND active = 1');
        $removeStmt->bind_param('ii', $memberId, $clubId);
        $removeStmt->execute();

        if ($removeStmt->affected_rows > 0) {
            setMembersFlash('success', 'Member removed from your club.');
        } else {
            setMembersFlash('error', 'Member could not be removed (not found or already inactive).');
        }

        $removeStmt->close();
        header('Location: ' . $redirectUrl);
        exit;
    }

    setMembersFlash('error', 'Unknown action requested.');
    header('Location: ' . $redirectUrl);
    exit;
}

$flash = $_SESSION['manage_members_flash'] ?? null;
unset($_SESSION['manage_members_flash']);

$searchResults = [];
if ($clubId > 0 && $searchQuery !== '') {
    $like = '%' . $searchQuery . '%';

    $searchStmt = $con->prepare(
        'SELECT
            s.student_id,
            s.full_name,
            s.student_email,
            CASE WHEN cm.member_id IS NULL THEN 0 ELSE 1 END AS is_active_member
         FROM students s
         LEFT JOIN club_members cm
            ON cm.student_id = s.student_id
           AND cm.club_id = ?
           AND cm.active = 1
         WHERE s.student_id LIKE ? OR s.full_name LIKE ?
         ORDER BY CASE WHEN s.student_id = ? THEN 0 ELSE 1 END, s.full_name ASC
         LIMIT 50'
    );
    $searchStmt->bind_param('isss', $clubId, $like, $like, $searchQuery);
    $searchStmt->execute();
    $result = $searchStmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $searchResults[] = $row;
    }
    $searchStmt->close();
}

$members = [];
if ($clubId > 0) {
    $membersStmt = $con->prepare(
        "SELECT
            cm.member_id,
            cm.student_id,
            cm.Role,
            s.full_name,
            s.student_email
         FROM club_members cm
         INNER JOIN students s ON s.student_id = cm.student_id
         WHERE cm.club_id = ?
           AND cm.active = 1
         ORDER BY
            CASE UPPER(cm.Role)
                WHEN 'PRESIDENT' THEN 1
                WHEN 'EB-PRESIDENT' THEN 1
                WHEN 'EXECUTIVE' THEN 2
                WHEN 'EB-EXECUTIVE' THEN 2
                WHEN 'INCHARGE' THEN 2
                ELSE 3
            END,
            s.full_name ASC"
    );
    $membersStmt->bind_param('i', $clubId);
    $membersStmt->execute();
    $membersResult = $membersStmt->get_result();

    while ($row = $membersResult->fetch_assoc()) {
        $members[] = $row;
    }

    $membersStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Members | ClubHub</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <style>
        html { scroll-snap-type: none; }
        body { min-height: 100vh; }

        .members-page {
            min-height: 100vh;
            padding: 120px 5% 60px 5%;
            background: var(--dark-bg);
        }

        .members-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .members-meta {
            color: var(--muted);
            margin: 0;
        }

        .back-link {
            display: inline-block;
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--table-border);
            background: var(--card);
            border-radius: 8px;
            padding: 10px 14px;
            transition: 0.2s ease;
            font-weight: 600;
        }

        .back-link:hover {
            border-color: var(--pink);
            color: var(--pink);
        }

        .quick-actions {
            margin-bottom: 22px;
        }

        .role-card {
            width: 100%;
            max-width: 360px;
            text-align: left;
            background: linear-gradient(135deg, rgba(255, 77, 141, 0.22), rgba(255, 77, 141, 0.08));
            border: 1px solid rgba(255, 77, 141, 0.55);
            color: var(--text);
            border-radius: 12px;
            padding: 16px;
            cursor: pointer;
            transition: 0.2s ease;
        }

        .role-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 77, 141, 0.2);
        }

        .role-card[disabled] {
            opacity: 0.45;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .role-card-title {
            display: block;
            font-size: 1.05rem;
            font-weight: 800;
            margin-bottom: 4px;
        }

        .role-card-subtitle {
            display: block;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--table-border);
            border-radius: 12px;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .panel-header {
            padding: 16px 18px;
            border-bottom: 1px solid var(--table-row-border);
            font-weight: 700;
        }

        .panel-body {
            padding: 18px;
        }

        .search-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 240px;
            padding: 11px 12px;
            border-radius: 8px;
            border: 1px solid var(--table-border);
            background: rgba(255, 255, 255, 0.02);
            color: var(--text);
            font-size: 0.95rem;
        }

        .btn {
            border: 1px solid transparent;
            border-radius: 8px;
            padding: 10px 14px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s ease;
            background: var(--pink);
            color: #fff;
        }

        .btn:hover { filter: brightness(1.05); }

        .btn-secondary {
            background: transparent;
            border-color: var(--table-border);
            color: var(--text);
        }

        .btn-secondary:hover {
            border-color: var(--pink);
            color: var(--pink);
        }

        .btn-danger {
            background: rgba(239, 68, 68, 0.18);
            border-color: rgba(239, 68, 68, 0.4);
            color: #fca5a5;
        }

        .btn-danger:hover {
            background: rgba(239, 68, 68, 0.24);
            color: #fecaca;
        }

        .btn-small {
            padding: 7px 10px;
            font-size: 0.82rem;
        }

        .btn[disabled] {
            opacity: 0.55;
            cursor: not-allowed;
        }

        .table-wrap { overflow-x: auto; }

        .members-table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text);
        }

        .members-table th,
        .members-table td {
            padding: 12px 14px;
            border-bottom: 1px solid var(--table-row-border);
            text-align: left;
            vertical-align: middle;
        }

        .members-table thead th {
            background: rgba(255, 77, 141, 0.12);
            font-size: 0.92rem;
            letter-spacing: 0.02em;
        }

        .members-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
        }

        .inline-form {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }

        .role-select {
            min-width: 160px;
            padding: 8px 36px 8px 10px;
            border-radius: 7px;
            border: 1px solid var(--table-border);
            background-color: var(--card);
            color: var(--text);
            font-size: 0.92rem;
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image:
                linear-gradient(45deg, transparent 50%, var(--text) 50%),
                linear-gradient(135deg, var(--text) 50%, transparent 50%);
            background-position:
                calc(100% - 16px) calc(50% - 3px),
                calc(100% - 10px) calc(50% - 3px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
        }

        .role-select option {
            background-color: var(--card);
            color: var(--text);
        }

        .role-select-compact {
            min-width: 132px;
            max-width: 170px;
        }

        .member-tag {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .empty-state {
            color: var(--muted);
            text-align: center;
            padding: 22px;
        }

        .flash {
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
            border: 1px solid transparent;
            font-weight: 600;
        }

        .flash-success {
            background: rgba(16, 185, 129, 0.16);
            border-color: rgba(16, 185, 129, 0.38);
            color: #6ee7b7;
        }

        .flash-error {
            background: rgba(239, 68, 68, 0.16);
            border-color: rgba(239, 68, 68, 0.38);
            color: #fca5a5;
        }

        .roles-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.75);
            z-index: 2200;
            align-items: center;
            justify-content: center;
            padding: 18px;
        }

        .roles-modal.open { display: flex; }

        .roles-modal-card {
            width: min(640px, 100%);
            background: var(--card);
            color: var(--text);
            border: 1px solid var(--table-border);
            border-radius: 14px;
            box-shadow: 0 18px 35px rgba(2, 6, 23, 0.45);
            overflow: hidden;
        }

        .roles-modal-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 16px 18px;
            border-bottom: 1px solid var(--table-row-border);
        }

        .roles-modal-title {
            font-size: 1.08rem;
            font-weight: 800;
        }

        .roles-close {
            border: 1px solid var(--table-border);
            background: transparent;
            color: var(--text);
            width: 34px;
            height: 34px;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
        }

        .roles-modal-body {
            padding: 16px 18px;
        }

        .define-role-form {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }

        .define-role-form .search-input {
            min-width: 220px;
        }

        .checkbox-inline {
            width: 100%;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--muted);
            font-size: 0.88rem;
        }

        .roles-help {
            color: var(--muted);
            font-size: 0.88rem;
            margin: 0 0 12px 0;
        }

        .defined-roles {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .defined-role-item {
            border: 1px solid var(--table-border);
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .defined-role-name {
            font-weight: 700;
        }

        .role-pill {
            display: inline-block;
            margin-left: 8px;
            padding: 3px 8px;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 700;
            border: 1px solid rgba(255, 77, 141, 0.35);
            background: rgba(255, 77, 141, 0.15);
            color: #ff9dc0;
        }

        .roles-empty {
            color: var(--muted);
            font-size: 0.9rem;
        }

        body.light-theme .search-input,
        body.light-theme .role-select {
            background-color: #ffffff;
            color: #0f172a;
            border-color: #cfd6e4;
        }

        body.light-theme .role-select {
            background-image:
                linear-gradient(45deg, transparent 50%, #0f172a 50%),
                linear-gradient(135deg, #0f172a 50%, transparent 50%);
        }

        body.light-theme .role-select option {
            background-color: #ffffff;
            color: #0f172a;
        }

        body.light-theme .members-table tbody tr:hover {
            background: rgba(15, 23, 42, 0.04);
        }

        body.light-theme .members-table thead th {
            background: rgba(255, 77, 141, 0.18);
        }

        body.light-theme .btn-secondary {
            border-color: #cfd6e4;
        }

        body.light-theme .btn-danger {
            color: #7f1d1d;
            background: rgba(239, 68, 68, 0.2);
        }

        body.light-theme .member-tag {
            color: #065f46;
            background: rgba(16, 185, 129, 0.22);
        }

        body.light-theme .roles-modal {
            background: rgba(15, 23, 42, 0.46);
        }

        @media (max-width: 768px) {
            .members-page {
                padding-top: 105px;
            }

            .inline-form {
                flex-wrap: wrap;
            }

            .role-card {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">ClubHub</div>
        <div class="profile-menu" id="profile-menu">
            <button class="profile-trigger" id="profile-trigger" type="button" aria-expanded="false"><?php echo htmlspecialchars((string)$displayName); ?></button>
            <div class="profile-dropdown">
                <label class="theme-toggle-item" for="theme-switch">
                    <span>Change Theme</span>
                    <span class="theme-switch">
                        <input type="checkbox" id="theme-switch" aria-label="Toggle theme">
                        <span class="theme-slider"></span>
                    </span>
                </label>
                <a class="logout-link" href="logout.php">Log out</a>
            </div>
        </div>
    </nav>

    <section class="members-page">
        <div class="header">Manage Members</div>
        <div class="members-header">
            <p class="members-meta">
                <?php if ($clubId > 0): ?>
                    Managing members for <strong><?php echo htmlspecialchars($clubName !== '' ? $clubName : ('Club #' . $clubId)); ?></strong>.
                <?php else: ?>
                    No club was detected for this account.
                <?php endif; ?>
            </p>
            <a href="Club_dashboard.php" class="back-link">Back to Club Dashboard</a>
        </div>

        <?php if ($flash && !empty($flash['message'])): ?>
            <div class="flash <?php echo ($flash['type'] ?? '') === 'success' ? 'flash-success' : 'flash-error'; ?>">
                <?php echo htmlspecialchars((string)$flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="quick-actions">
            <button type="button" class="role-card" id="open-roles-modal" <?php echo $clubId <= 0 ? 'disabled' : ''; ?>>
                <span class="role-card-title">Define Roles</span>
                <span class="role-card-subtitle">Create and manage role options used in member role updates.</span>
            </button>
        </div>

        <div class="panel">
            <div class="panel-header">Search Students</div>
            <div class="panel-body">
                <form class="search-form" method="get" action="manage_members.php">
                    <input
                        class="search-input"
                        type="text"
                        name="q"
                        value="<?php echo htmlspecialchars($searchQuery); ?>"
                        placeholder="Search by Name or Student ID"
                        <?php echo $clubId <= 0 ? 'disabled' : ''; ?>
                    >
                    <button class="btn" type="submit" <?php echo $clubId <= 0 ? 'disabled' : ''; ?>>Search</button>
                    <?php if ($searchQuery !== ''): ?>
                        <a class="btn btn-secondary" href="manage_members.php">Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if ($clubId > 0 && $searchQuery !== ''): ?>
                <div class="table-wrap">
                    <table class="members-table">
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($searchResults)): ?>
                                <tr>
                                    <td colspan="4" class="empty-state">No students matched your search.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($searchResults as $result): ?>
                                    <?php $alreadyMember = intval($result['is_active_member']) === 1; ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars((string)$result['student_id']); ?></td>
                                        <td><?php echo htmlspecialchars((string)$result['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars((string)$result['student_email']); ?></td>
                                        <td>
                                            <?php if ($alreadyMember): ?>
                                                <span class="member-tag">Already in club</span>
                                            <?php else: ?>
                                                <form method="post" class="inline-form" action="manage_members.php">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['manage_members_csrf']); ?>">
                                                    <input type="hidden" name="action" value="add_member">
                                                    <input type="hidden" name="student_id" value="<?php echo htmlspecialchars((string)$result['student_id']); ?>">
                                                    <input type="hidden" name="redirect_q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                                                    <select class="role-select role-select-compact" name="role" aria-label="Role when adding member">
                                                        <?php foreach ($allowedRoles as $roleOption): ?>
                                                            <option value="<?php echo htmlspecialchars($roleOption); ?>" <?php echo $roleOption === 'Member' ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars(roleDisplayName($roleOption)); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <button class="btn" type="submit">Add to Club</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="panel">
            <div class="panel-header">Current Club Members</div>
            <div class="table-wrap">
                <table class="members-table">
                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Change Role</th>
                            <th>Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($clubId <= 0): ?>
                            <tr>
                                <td colspan="6" class="empty-state">No club is linked to this account.</td>
                            </tr>
                        <?php elseif (empty($members)): ?>
                            <tr>
                                <td colspan="6" class="empty-state">No active members found for this club.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($members as $member): ?>
                                <?php
                                    $currentRole = (string)$member['Role'];
                                    $currentRoleAllowed = in_array($currentRole, $allowedRoles, true);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars((string)$member['student_id']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$member['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars((string)$member['student_email']); ?></td>
                                    <td><?php echo htmlspecialchars(roleDisplayName($currentRole)); ?></td>
                                    <td>
                                        <form method="post" class="inline-form" action="manage_members.php">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['manage_members_csrf']); ?>">
                                            <input type="hidden" name="action" value="update_role">
                                            <input type="hidden" name="member_id" value="<?php echo intval($member['member_id']); ?>">
                                            <input type="hidden" name="redirect_q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                                            <select class="role-select" name="role" aria-label="Update member role">
                                                <?php if (!$currentRoleAllowed): ?>
                                                    <option value="<?php echo htmlspecialchars($currentRole); ?>" selected>
                                                        <?php echo htmlspecialchars(roleDisplayName($currentRole)); ?> (current)
                                                    </option>
                                                <?php endif; ?>
                                                <?php foreach ($allowedRoles as $roleOption): ?>
                                                    <option value="<?php echo htmlspecialchars($roleOption); ?>" <?php echo $roleOption === $currentRole ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars(roleDisplayName($roleOption)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button class="btn btn-secondary" type="submit">Update</button>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" action="manage_members.php" onsubmit="return confirm('Remove this member from the club?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['manage_members_csrf']); ?>">
                                            <input type="hidden" name="action" value="remove_member">
                                            <input type="hidden" name="member_id" value="<?php echo intval($member['member_id']); ?>">
                                            <input type="hidden" name="redirect_q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                                            <button class="btn btn-danger" type="submit">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <div class="roles-modal" id="roles-modal" aria-hidden="true">
        <div class="roles-modal-card" role="dialog" aria-modal="true" aria-labelledby="roles-modal-title">
            <div class="roles-modal-head">
                <div class="roles-modal-title" id="roles-modal-title">Define Roles</div>
                <button type="button" class="roles-close" data-close-roles-modal aria-label="Close role modal">X</button>
            </div>
            <div class="roles-modal-body">
                <p class="roles-help">Executive Board prefix is handled in backend. Frontend shows only the role part.</p>

                <?php if ($clubId <= 0): ?>
                    <p class="roles-empty">No club is linked to this account yet.</p>
                <?php else: ?>
                    <?php if (!$roleTableReady): ?>
                        <p class="roles-empty">Role storage is not available. You can still see current role options below.</p>
                    <?php else: ?>
                        <form method="post" action="manage_members.php" class="define-role-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['manage_members_csrf']); ?>">
                            <input type="hidden" name="action" value="define_role">
                            <input type="hidden" name="redirect_q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                            <input type="hidden" name="redirect_roles" value="1">
                            <input class="search-input" type="text" name="role_name" maxlength="100" placeholder="e.g. General Secretary" required>
                            <button class="btn" type="submit">Save Role</button>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="is_eb_role" value="1">
                                Mark as Executive Board role (prefix handled in backend)
                            </label>
                        </form>
                    <?php endif; ?>

                    <p class="roles-help"><strong>Current available roles</strong> (same as role update dropdown):</p>
                    <div class="defined-roles">
                        <?php if (empty($allowedRoles)): ?>
                            <p class="roles-empty">No roles are available yet.</p>
                        <?php else: ?>
                            <?php foreach ($allowedRoles as $roleOption): ?>
                                <div class="defined-role-item">
                                    <div>
                                        <span class="defined-role-name"><?php echo htmlspecialchars(roleDisplayName($roleOption)); ?></span>
                                        <?php if (isset($customRoleLookup[$roleOption])): ?>
                                            <span class="role-pill">Custom</span>
                                        <?php elseif (isset($baseRoleLookup[$roleOption])): ?>
                                            <span class="role-pill">Default</span>
                                        <?php else: ?>
                                            <span class="role-pill">In Use</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($roleTableReady): ?>
                        <p class="roles-help"><strong>Custom roles</strong> (removable when not assigned to active members):</p>
                        <div class="defined-roles">
                            <?php if (empty($customRoles)): ?>
                                <p class="roles-empty">No custom roles defined yet for this club.</p>
                            <?php else: ?>
                                <?php foreach ($customRoles as $customRole): ?>
                                    <?php $roleName = (string)$customRole['role_name']; ?>
                                    <div class="defined-role-item">
                                        <div>
                                            <span class="defined-role-name"><?php echo htmlspecialchars(roleDisplayName($roleName)); ?></span>
                                        </div>
                                        <form method="post" action="manage_members.php" onsubmit="return confirm('Remove this custom role?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string)$_SESSION['manage_members_csrf']); ?>">
                                            <input type="hidden" name="action" value="remove_defined_role">
                                            <input type="hidden" name="role_id" value="<?php echo intval($customRole['role_id']); ?>">
                                            <input type="hidden" name="redirect_q" value="<?php echo htmlspecialchars($searchQuery); ?>">
                                            <input type="hidden" name="redirect_roles" value="1">
                                            <button class="btn btn-danger btn-small" type="submit">Remove</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const profileMenu = document.getElementById('profile-menu');
        const profileTrigger = document.getElementById('profile-trigger');

        profileTrigger.addEventListener('click', function (event) {
            event.stopPropagation();
            profileMenu.classList.toggle('open');
            profileTrigger.setAttribute('aria-expanded', profileMenu.classList.contains('open') ? 'true' : 'false');
        });

        document.addEventListener('click', function (event) {
            if (!profileMenu.contains(event.target)) {
                profileMenu.classList.remove('open');
                profileTrigger.setAttribute('aria-expanded', 'false');
            }
        });

        const rolesModal = document.getElementById('roles-modal');
        const openRolesModalButton = document.getElementById('open-roles-modal');
        const closeRolesButtons = document.querySelectorAll('[data-close-roles-modal]');

        function openRolesModal() {
            rolesModal.classList.add('open');
            rolesModal.setAttribute('aria-hidden', 'false');
        }

        function closeRolesModal() {
            rolesModal.classList.remove('open');
            rolesModal.setAttribute('aria-hidden', 'true');
        }

        if (openRolesModalButton) {
            openRolesModalButton.addEventListener('click', function () {
                if (!openRolesModalButton.disabled) {
                    openRolesModal();
                }
            });
        }

        closeRolesButtons.forEach(function (button) {
            button.addEventListener('click', closeRolesModal);
        });

        rolesModal.addEventListener('click', function (event) {
            if (event.target === rolesModal) {
                closeRolesModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                profileMenu.classList.remove('open');
                profileTrigger.setAttribute('aria-expanded', 'false');
                closeRolesModal();
            }
        });

        const themeSwitch = document.getElementById('theme-switch');
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'light') {
            document.body.classList.add('light-theme');
            themeSwitch.checked = true;
        }

        themeSwitch.addEventListener('change', function () {
            if (this.checked) {
                document.body.classList.add('light-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.body.classList.remove('light-theme');
                localStorage.setItem('theme', 'dark');
            }
        });

        <?php if ($openRolesModal): ?>
            openRolesModal();
        <?php endif; ?>
    </script>
</body>
</html>
