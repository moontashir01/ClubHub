<?php
session_start();
include 'connection.php';
require_once 'notification_helpers.php';

$adminRole = $_SESSION['AdminRole'] ?? ($_SESSION['Portal'] ?? 'Admin');
$name = $_SESSION['Name'] ?? 'System Admin';

if ($adminRole === 'Student Affairs') {
    $name = 'Office of Student Affairs';
} elseif ($adminRole === 'Security') {
    $name = 'Campus Security Head';
} elseif ($adminRole === 'Registrar') {
    $name = 'University Registrar';
}

$initial = strtoupper(substr($name, 0, 1));

$setup_error = '';
$table_check = @mysqli_query($con, "SHOW TABLES LIKE 'volunteer_request_club'");
if (!$table_check || mysqli_num_rows($table_check) === 0) {
    $setup_error = 'Volunteer request tables are missing. Please import the latest club_hub.sql.';
}

$events = [];
$event_query = @mysqli_query(
    $con,
    "SELECT event_id, event_name, event_date, event_creator
     FROM events
     WHERE event_availablity = 1
       AND (club_id IS NULL OR LOWER(COALESCE(event_creator, '')) = 'admin')
     ORDER BY event_date DESC"
);
if ($event_query) {
    while ($row = mysqli_fetch_assoc($event_query)) {
        $events[] = $row;
    }
}

$clubs = [];
$club_query = @mysqli_query($con, "SELECT club_id, club_name FROM clubs ORDER BY club_name");
if ($club_query) {
    while ($row = mysqli_fetch_assoc($club_query)) {
        $clubs[] = $row;
    }
}

$selected_event_id = 0;
if (isset($_GET['event_id'])) {
    $selected_event_id = intval($_GET['event_id']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_event_id = intval($_POST['event_id'] ?? 0);
}

$event_ids = array_map(static function ($ev) {
    return intval($ev['event_id']);
}, $events);

if ($selected_event_id && !in_array($selected_event_id, $event_ids, true)) {
    $selected_event_id = 0;
}

if (!$selected_event_id && !empty($events)) {
    $selected_event_id = intval($events[0]['event_id']);
}

$flash = $_GET['msg'] ?? '';
$flash_type = $_GET['type'] ?? 'success';
$allowed_flash = ['success', 'error', 'warning'];
if (!in_array($flash_type, $allowed_flash, true)) {
    $flash_type = 'success';
}

function redirect_with_msg($event_id, $msg, $type = 'success') {
    header("Location: reqVolunteer.php?event_id=" . intval($event_id) . "&msg=" . urlencode($msg) . "&type=" . urlencode($type));
    exit();
}

function sync_request_statuses(mysqli $con, int $event_id): void {
    if ($event_id <= 0) {
        return;
    }

    $sync_stmt = $con->prepare("
        UPDATE volunteer_request_club r
        LEFT JOIN (
            SELECT club_id, event_id, COUNT(*) AS assigned_count
            FROM volunteer_requests
            WHERE event_id = ?
            GROUP BY club_id, event_id
        ) a ON a.event_id = r.event_id AND a.club_id = r.club_id
        SET r.status = CASE
            WHEN LOWER(COALESCE(r.status, '')) = 'cancelled' THEN 'Cancelled'
            WHEN r.requested_count <= 0 THEN 'Open'
            WHEN COALESCE(a.assigned_count, 0) >= r.requested_count THEN 'Fulfilled'
            WHEN COALESCE(a.assigned_count, 0) > 0 THEN 'In Progress'
            ELSE 'Open'
        END,
        r.updated_at = CURRENT_TIMESTAMP
        WHERE r.event_id = ?
    ");

    if ($sync_stmt) {
        $sync_stmt->bind_param("ii", $event_id, $event_id);
        $sync_stmt->execute();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if (!$selected_event_id) {
        $flash = 'Please select an event to continue.';
        $flash_type = 'error';
    } elseif ($action === 'save_requests') {
        $note = trim($_POST['note'] ?? '');
        $deadline = trim($_POST['deadline'] ?? '');
        $deadline = $deadline !== '' ? $deadline : null;

        $club_ids = $_POST['club_id'] ?? [];
        $club_counts = $_POST['club_count'] ?? [];
        $club_map = [];

        for ($i = 0; $i < count($club_ids); $i++) {
            $club_id = intval($club_ids[$i] ?? 0);
            $count = intval($club_counts[$i] ?? 0);
            if ($club_id > 0 && $count > 0) {
                if (!isset($club_map[$club_id])) {
                    $club_map[$club_id] = 0;
                }
                $club_map[$club_id] += $count;
            }
        }

        $upsert = $con->prepare("
            INSERT INTO volunteer_request_club (event_id, club_id, requested_count, note, deadline, status)
            VALUES (?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                requested_count = VALUES(requested_count),
                note = VALUES(note),
                deadline = VALUES(deadline),
                status = VALUES(status),
                updated_at = CURRENT_TIMESTAMP
        ");
        $delete_stmt = $con->prepare("DELETE FROM volunteer_request_club WHERE event_id = ? AND club_id = ?");

        foreach ($clubs as $club) {
            $club_id = intval($club['club_id']);
            $count = intval($club_map[$club_id] ?? 0);

            if ($count > 0) {
                $status = 'Open';
                $upsert->bind_param("iiisss", $selected_event_id, $club_id, $count, $note, $deadline, $status);
                $upsert->execute();
            } else {
                $delete_stmt->bind_param("ii", $selected_event_id, $club_id);
                $delete_stmt->execute();
            }
        }
        sync_request_statuses($con, $selected_event_id);

        // Notify each club that received a volunteer request
        $eventNameForNotif = '';
        foreach ($events as $ev) {
            if (intval($ev['event_id']) === $selected_event_id) {
                $eventNameForNotif = $ev['event_name'];
                break;
            }
        }
        foreach ($club_map as $notif_club_id => $notif_count) {
            notifyClub(
                $con,
                intval($notif_club_id),
                "🙋 Admin has requested $notif_count volunteer(s) for \"" . $eventNameForNotif . "\".",
                'sendVolunteer.php'
            );
        }

        redirect_with_msg($selected_event_id, 'Volunteer requests saved.');
    } elseif ($action === 'cancel_request') {
        $club_id = intval($_POST['club_id'] ?? 0);
        if ($club_id <= 0) {
            redirect_with_msg($selected_event_id, 'Invalid club for cancellation.', 'error');
        }

        $request_check = $con->prepare("
            SELECT requested_count, status
            FROM volunteer_request_club
            WHERE event_id = ? AND club_id = ?
            LIMIT 1
        ");
        $request_check->bind_param("ii", $selected_event_id, $club_id);
        $request_check->execute();
        $request_row = $request_check->get_result()->fetch_assoc();

        if (!$request_row) {
            redirect_with_msg($selected_event_id, 'No request found for this club.', 'warning');
        }

        $assigned_stmt = $con->prepare("
            SELECT COUNT(*) AS assigned_count
            FROM volunteer_requests
            WHERE event_id = ? AND club_id = ?
        ");
        $assigned_stmt->bind_param("ii", $selected_event_id, $club_id);
        $assigned_stmt->execute();
        $assigned_row = $assigned_stmt->get_result()->fetch_assoc();

        $requested_count = intval($request_row['requested_count'] ?? 0);
        $assigned_count = intval($assigned_row['assigned_count'] ?? 0);
        $remaining_count = max(0, $requested_count - $assigned_count);
        $current_status = strtolower(trim($request_row['status'] ?? ''));

        if ($remaining_count <= 0 || $current_status === 'cancelled') {
            redirect_with_msg($selected_event_id, 'Only pending requests can be cancelled.', 'warning');
        }

        $new_requested_count = $assigned_count;
        $cancel_status = 'Cancelled';
        $cancel_stmt = $con->prepare("
            UPDATE volunteer_request_club
            SET requested_count = ?, status = ?, updated_at = CURRENT_TIMESTAMP
            WHERE event_id = ? AND club_id = ?
        ");
        $cancel_stmt->bind_param("isii", $new_requested_count, $cancel_status, $selected_event_id, $club_id);
        $cancel_stmt->execute();

        $message = $assigned_count > 0
            ? "Pending part cancelled. $assigned_count volunteer(s) already assigned remain active."
            : "Pending request cancelled.";
        redirect_with_msg($selected_event_id, $message);
    } elseif ($action === 'clear_event') {
        $clear_requests = $con->prepare("DELETE FROM volunteer_request_club WHERE event_id = ?");
        $clear_requests->bind_param("i", $selected_event_id);
        $clear_requests->execute();

        $clear_assignments = $con->prepare("DELETE FROM volunteer_requests WHERE event_id = ?");
        $clear_assignments->bind_param("i", $selected_event_id);
        $clear_assignments->execute();

        redirect_with_msg($selected_event_id, 'All volunteer requests and assignments cleared for this event.', 'warning');
    }
}

$clear_draft = ($flash === 'Volunteer requests saved.' || $flash_type === 'warning');

$selected_event = null;
foreach ($events as $ev) {
    if (intval($ev['event_id']) === $selected_event_id) {
        $selected_event = $ev;
        break;
    }
}

$request_rows = [];
$assigned_counts = [];
$assignments = [];
$assignments_by_club = [];
$pending_clubs = [];
$total_requested = 0;
$total_assigned = 0;
$note_prefill = '';
$deadline_prefill = '';
$existing_requests_map = [];

if ($selected_event_id) {
    sync_request_statuses($con, $selected_event_id);

    $request_query = $con->prepare("
        SELECT r.club_id, r.requested_count, r.note, r.deadline, r.status, c.club_name
        FROM volunteer_request_club r
        INNER JOIN clubs c ON c.club_id = r.club_id
        WHERE r.event_id = ?
        ORDER BY c.club_name
    ");
    $request_query->bind_param("i", $selected_event_id);
    $request_query->execute();
    $request_result = $request_query->get_result();
    while ($row = $request_result->fetch_assoc()) {
        $request_rows[] = $row;
        $total_requested += intval($row['requested_count']);
        $saved_status = strtolower(trim($row['status'] ?? ''));
        if ($saved_status !== 'cancelled' && intval($row['requested_count']) > 0) {
            $existing_requests_map[intval($row['club_id'])] = intval($row['requested_count']);
        }
    }
    if (!empty($request_rows)) {
        $note_prefill = $request_rows[0]['note'] ?? '';
        $deadline_prefill = $request_rows[0]['deadline'] ?? '';
    }

    $assigned_query = $con->prepare("
        SELECT club_id, COUNT(*) AS assigned_count
        FROM volunteer_requests
        WHERE event_id = ?
        GROUP BY club_id
    ");
    $assigned_query->bind_param("i", $selected_event_id);
    $assigned_query->execute();
    $assigned_result = $assigned_query->get_result();
    while ($row = $assigned_result->fetch_assoc()) {
        $assigned_counts[intval($row['club_id'])] = intval($row['assigned_count']);
        $total_assigned += intval($row['assigned_count']);
    }

    $assignment_query = $con->prepare("
        SELECT c.club_name, s.full_name, s.student_email, s.student_id, COALESCE(cm.Role, 'Member') AS club_role
        FROM volunteer_requests vr
        INNER JOIN students s ON s.student_id = vr.student_id
        INNER JOIN clubs c ON c.club_id = vr.club_id
        LEFT JOIN club_members cm ON cm.club_id = vr.club_id AND cm.student_id = vr.student_id
        WHERE vr.event_id = ?
        ORDER BY c.club_name, s.full_name
    ");
    $assignment_query->bind_param("i", $selected_event_id);
    $assignment_query->execute();
    $assignment_result = $assignment_query->get_result();
    while ($row = $assignment_result->fetch_assoc()) {
        $assignments[] = $row;
        $club_name = $row['club_name'];
        if (!isset($assignments_by_club[$club_name])) {
            $assignments_by_club[$club_name] = [];
        }
        $assignments_by_club[$club_name][] = $row;
    }

    foreach ($request_rows as $row) {
        $club_id = intval($row['club_id']);
        $assigned = $assigned_counts[$club_id] ?? 0;
        $remaining = intval($row['requested_count']) - $assigned;
        $saved_status = strtolower(trim($row['status'] ?? ''));
        if ($remaining > 0 && $saved_status !== 'cancelled') {
            $pending_clubs[] = [
                'club_name' => $row['club_name'],
                'remaining' => $remaining
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Volunteers | ClubHub Admin</title>
    <style>
        :root {
            --bg: #090a0f;
            --surface: #13141f;
            --surface-hover: #1c1d2b;
            --primary: #ff477e;
            --primary-glow: rgba(255, 71, 126, 0.3);
            --text: #f1f5f9;
            --muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
            --success: #4ade80;
            --warning: #fbbf24;
            --info: #60a5fa;
        }

        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); color: var(--text); display: flex; min-height: 100vh; overflow-x: hidden; }
        .sidebar { width: 280px; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; box-sizing: border-box; }
        .logo { font-size: 1.8rem; font-weight: 800; color: var(--text); margin-bottom: 40px; display: flex; align-items: center; gap: 10px; letter-spacing: 1px; }
        .logo span { color: var(--primary); }
        .admin-profile { background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 30px; display: flex; align-items: center; gap: 15px; transition: 0.3s; }
        .admin-profile:hover { border-color: var(--primary); background: rgba(255, 71, 126, 0.05); }
        .avatar { width: 45px; height: 45px; border-radius: 12px; background: linear-gradient(135deg, var(--primary), #ff9a9e); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
        .admin-info h4 { margin: 0; font-size: 1rem; color: var(--text); }
        .admin-info p { margin: 0; font-size: 0.75rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; font-weight: bold; margin-top: 4px; }
        .nav-label { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; font-weight: bold; }
        .nav-link { display: block; color: var(--muted); text-decoration: none; margin-bottom: 12px; padding: 10px; border-radius: 8px; transition: 0.3s; }
        .nav-link:hover { background: rgba(255, 71, 126, 0.1); color: var(--primary); }
        .nav-link.active { background: rgba(255, 71, 126, 0.12); color: var(--primary); font-weight: bold; }
        .logout-btn { margin-top: auto; padding: 15px; text-align: center; background: rgba(248, 113, 113, 0.1); color: #f87171; border-radius: 12px; text-decoration: none; font-weight: bold; transition: 0.3s; border: 1px solid transparent; }
        .logout-btn:hover { background: #f87171; color: white; box-shadow: 0 5px 15px rgba(248, 113, 113, 0.4); }

        .main-content { flex: 1; padding: 40px 50px; overflow-y: auto; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; gap: 20px; margin-bottom: 30px; }
        .title-wrap h1 { margin: 0; font-size: 2.2rem; font-weight: 700; background: linear-gradient(to right, #fff, #aaa); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .title-wrap p { margin: 6px 0 0 0; color: var(--primary); font-weight: bold; letter-spacing: 1px; text-transform: uppercase; font-size: 0.85rem; }
        .date-display { color: var(--muted); font-size: 0.9rem; background: var(--surface); padding: 10px 20px; border-radius: 20px; border: 1px solid var(--border); }

        .banner { padding: 12px 18px; border-radius: 12px; margin-bottom: 20px; border: 1px solid transparent; font-weight: 600; }
        .banner.success { background: rgba(74, 222, 128, 0.15); color: var(--success); border-color: rgba(74, 222, 128, 0.4); }
        .banner.error { background: rgba(248, 113, 113, 0.12); color: #f87171; border-color: rgba(248, 113, 113, 0.4); }
        .banner.warning { background: rgba(251, 191, 36, 0.12); color: var(--warning); border-color: rgba(251, 191, 36, 0.4); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); padding: 22px; border-radius: 20px; display: flex; align-items: center; gap: 15px; }
        .stat-card .stat-value { font-size: 1.8rem; font-weight: 800; }
        .stat-card .stat-label { color: var(--muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-top: 4px; }
        .stat-accent { width: 6px; height: 100%; background: var(--primary); border-radius: 6px; }

        .panel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 25px; }
        .panel { background: var(--surface); border: 1px solid var(--border); border-radius: 22px; padding: 28px; }
        .panel-wide { grid-column: 1 / -1; }
        .panel h2 { margin: 0 0 18px 0; font-size: 1.2rem; color: var(--text); }
        .panel .subtext { color: var(--muted); font-size: 0.9rem; margin-top: -8px; margin-bottom: 18px; }

        .admin-form-group { margin-bottom: 18px; }
        .admin-form-group label { display: block; font-size: 0.8rem; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; }
        .admin-input { width: 100%; padding: 14px; background: var(--bg); color: var(--text); border: 1px solid var(--border); border-radius: 10px; box-sizing: border-box; font-family: inherit; font-size: 1rem; transition: 0.3s; }
        .admin-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 71, 126, 0.2); }
        .input-compact { width: 110px; }
        .builder-list { display: grid; gap: 12px; }
        .builder-row { display: grid; grid-template-columns: 1.4fr 110px 38px; gap: 12px; align-items: center; }
        .builder-row select { appearance: none; }
        .builder-row .remove-btn { background: rgba(248, 113, 113, 0.12); border: 1px solid rgba(248, 113, 113, 0.4); color: #f87171; border-radius: 10px; padding: 10px 0; cursor: pointer; font-weight: 700; }
        .builder-row .remove-btn:hover { background: rgba(248, 113, 113, 0.2); }
        .builder-controls { display: flex; align-items: center; justify-content: space-between; margin-top: 14px; gap: 12px; }
        .add-btn { background: rgba(255, 71, 126, 0.15); border: 1px solid rgba(255, 71, 126, 0.4); color: var(--primary); padding: 10px 16px; border-radius: 10px; cursor: pointer; font-weight: 700; }
        .add-btn:hover { background: rgba(255, 71, 126, 0.25); }
        .builder-hint { color: var(--muted); font-size: 0.85rem; }

        .button-row { display: flex; flex-wrap: wrap; gap: 12px; }
        .submit-btn { background: var(--primary); border: none; padding: 12px 18px; border-radius: 10px; color: white; font-weight: bold; font-size: 0.95rem; cursor: pointer; transition: 0.3s; }
        .submit-btn:hover { background: #e63e70; box-shadow: 0 5px 15px var(--primary-glow); transform: translateY(-2px); }
        .ghost-btn { background: transparent; border: 1px solid var(--border); color: var(--text); padding: 12px 18px; border-radius: 10px; cursor: pointer; transition: 0.3s; font-weight: 600; }
        .ghost-btn:hover { border-color: var(--primary); color: var(--primary); }
        .danger-btn { background: rgba(248, 113, 113, 0.15); border: 1px solid rgba(248, 113, 113, 0.4); color: #f87171; padding: 12px 18px; border-radius: 10px; cursor: pointer; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid var(--border); text-align: left; font-size: 0.9rem; }
        th { color: var(--primary); text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; background: rgba(255,255,255,0.02); }

        .status-pill { padding: 4px 10px; border-radius: 999px; font-weight: 700; font-size: 0.75rem; display: inline-block; }
        .status-open { background: rgba(96, 165, 250, 0.15); color: var(--info); }
        .status-progress { background: rgba(251, 191, 36, 0.15); color: var(--warning); }
        .status-fulfilled { background: rgba(74, 222, 128, 0.15); color: var(--success); }
        .status-cancelled { background: rgba(248, 113, 113, 0.15); color: #f87171; }
        .status-empty { background: rgba(148, 163, 184, 0.12); color: var(--muted); }

        .status-stack { display: grid; gap: 12px; }
        .status-card {
            border: 1px solid var(--border);
            border-radius: 14px;
            background: linear-gradient(180deg, rgba(255,255,255,0.03), rgba(255,255,255,0.01));
            padding: 14px;
        }
        .status-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 12px; }
        .status-club { font-weight: 700; font-size: 1rem; color: var(--text); }
        .status-actions { display: flex; align-items: center; gap: 8px; }
        .status-metrics { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; }
        .metric-chip {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: rgba(255,255,255,0.02);
            padding: 8px 10px;
            text-align: center;
        }
        .metric-chip .label { display: block; font-size: 0.7rem; text-transform: uppercase; color: var(--muted); letter-spacing: 0.8px; margin-bottom: 4px; }
        .metric-chip .value { display: block; font-size: 1rem; font-weight: 800; color: var(--text); }
        .cancel-inline-form { display: inline; margin: 0; }
        .cancel-btn {
            min-width: 32px;
            height: 32px;
            border-radius: 9px;
            border: 1px solid rgba(248, 113, 113, 0.5);
            background: rgba(248, 113, 113, 0.12);
            color: #f87171;
            font-weight: 900;
            cursor: pointer;
            line-height: 1;
        }
        .cancel-btn:hover { background: rgba(248, 113, 113, 0.24); }

        .club-accordion { display: grid; gap: 10px; }
        .club-group { border: 1px solid var(--border); border-radius: 12px; background: rgba(255, 255, 255, 0.02); overflow: hidden; }
        .club-group summary {
            cursor: pointer;
            padding: 12px 14px;
            list-style: none;
            font-weight: 700;
            color: var(--text);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .club-group summary::-webkit-details-marker { display: none; }
        .club-group summary::after { content: '+'; color: var(--primary); font-size: 1.05rem; font-weight: 800; }
        .club-group[open] summary::after { content: '-'; }
        .club-group table { margin-top: 0; }
        .club-table-wrap { width: 100%; overflow-x: auto; }
        .club-members-table { min-width: 680px; }
        .club-members-table th:nth-child(1), .club-members-table td:nth-child(1) { width: 30%; }
        .club-members-table th:nth-child(2), .club-members-table td:nth-child(2) { width: 20%; }
        .club-members-table th:nth-child(3), .club-members-table td:nth-child(3) { width: 30%; }
        .club-members-table th:nth-child(4), .club-members-table td:nth-child(4) { width: 20%; white-space: nowrap; }

        @media (max-width: 720px) {
            .status-metrics { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .status-head { flex-direction: column; align-items: flex-start; }
        }

        .empty-state { color: var(--muted); font-size: 0.9rem; text-align: center; padding: 16px 0; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="logo">ClubHub <span>Admin</span></div>

        <div class="admin-profile">
            <div class="avatar"><?php echo $initial; ?></div>
            <div class="admin-info">
                <h4><?php echo htmlspecialchars($name); ?></h4>
                <p><?php echo htmlspecialchars($adminRole); ?></p>
            </div>
        </div>

        <div class="nav-label">Volunteer Ops</div>
        <a class="nav-link" href="admin_dashboard.php">Dashboard</a>
        <span class="nav-link active">Request Volunteers</span>

        <a href="logout.php" class="logout-btn">Secure Logout</a>
    </aside>

    <main class="main-content">
        <div class="header">
            <div class="title-wrap">
                <h1>Volunteer Request Studio</h1>
                <p>Plan volunteers per club and track fulfillment</p>
            </div>
            <div class="date-display" id="current-date"></div>
        </div>

        <?php if ($setup_error): ?>
            <div class="banner warning"><?php echo htmlspecialchars($setup_error); ?></div>
        <?php endif; ?>

        <?php if ($flash): ?>
            <div class="banner <?php echo htmlspecialchars($flash_type); ?>"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-accent"></div>
                <div>
                    <div class="stat-value"><?php echo $selected_event_id ? $total_requested : 0; ?></div>
                    <div class="stat-label">Requested Volunteers</div>
                </div>
            </div>
            <div class="stat-card" style="--primary: #4ade80;">
                <div class="stat-accent" style="background:#4ade80;"></div>
                <div>
                    <div class="stat-value"><?php echo $selected_event_id ? $total_assigned : 0; ?></div>
                    <div class="stat-label">Assigned Volunteers</div>
                </div>
            </div>
            <div class="stat-card" style="--primary: #fbbf24;">
                <div class="stat-accent" style="background:#fbbf24;"></div>
                <div>
                    <div class="stat-value"><?php echo $selected_event_id ? count($pending_clubs) : 0; ?></div>
                    <div class="stat-label">Clubs Pending</div>
                </div>
            </div>
        </div>

        <div class="panel-grid">
            <section class="panel">
                <h2>Request Builder</h2>
                <p class="subtext">Pick an admin-created active event and set volunteer counts per club.</p>

                <form method="post" id="request-form" data-event="<?php echo $selected_event_id; ?>">
                    <div class="admin-form-group">
                        <label for="event_id" style="margin:0;">Select Event</label>
                        <select class="admin-input" name="event_id" id="event_id" required>
                            <option value="">-- Choose Event --</option>
                            <?php foreach ($events as $ev): ?>
                                <option value="<?php echo $ev['event_id']; ?>" <?php echo ($selected_event_id == $ev['event_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($ev['event_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-form-group">
                        <label>Volunteer Notes (Optional)</label>
                        <input class="admin-input" type="text" name="note" id="note-input" placeholder="e.g., Arrival by 7:30 AM, wear club shirt" value="<?php echo htmlspecialchars($note_prefill); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label>Response Deadline (Optional)</label>
                        <input class="admin-input" type="date" name="deadline" id="deadline-input" value="<?php echo htmlspecialchars($deadline_prefill); ?>">
                    </div>

                    <div class="admin-form-group">
                        <label>Club Requests (Compact Builder)</label>
                        <div class="builder-list" id="club-builder"></div>
                        <div class="builder-controls">
                            <button type="button" class="add-btn" id="add-row">Add club request</button>
                            <span class="builder-hint">Pick a club and set a volunteer count. Duplicate clubs are blocked.</span>
                        </div>
                    </div>

                    <div class="button-row">
                        <button type="submit" name="action" value="save_requests" class="submit-btn">Save Requests</button>
                    </div>
                </form>

                <form method="post" style="margin-top: 16px;">
                    <input type="hidden" name="action" value="clear_event">
                    <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">
                    <button type="submit" class="danger-btn" <?php echo $selected_event_id ? '' : 'disabled'; ?>>Clear Requests for Event</button>
                </form>
            </section>

            <section class="panel">
                <h2>Request Status</h2>
                <p class="subtext">Track which clubs have fulfilled volunteer assignments.</p>

                <?php if (!$selected_event_id): ?>
                    <div class="empty-state">Select an event to see status.</div>
                <?php elseif (empty($request_rows)): ?>
                    <div class="empty-state">No requests have been created for this event yet.</div>
                <?php else: ?>
                    <div class="status-stack">
                        <?php foreach ($request_rows as $row): ?>
                            <?php
                                $club_id = intval($row['club_id']);
                                $assigned = $assigned_counts[$club_id] ?? 0;
                                $requested = intval($row['requested_count']);
                                $remaining = $requested - $assigned;
                                $saved_status = strtolower(trim($row['status'] ?? ''));
                                if ($saved_status === 'fulfilled') {
                                    $status_class = 'status-fulfilled';
                                    $status_label = 'Fulfilled';
                                } elseif ($saved_status === 'in progress' || $saved_status === 'in_progress') {
                                    $status_class = 'status-progress';
                                    $status_label = 'In progress';
                                } elseif ($saved_status === 'cancelled') {
                                    $status_class = 'status-cancelled';
                                    $status_label = 'Cancelled';
                                } elseif ($saved_status === 'open') {
                                    $status_class = 'status-open';
                                    $status_label = 'Open';
                                } elseif ($requested <= 0) {
                                    $status_class = 'status-empty';
                                    $status_label = 'Not requested';
                                } elseif ($remaining <= 0) {
                                    $status_class = 'status-fulfilled';
                                    $status_label = 'Fulfilled';
                                } elseif ($assigned > 0) {
                                    $status_class = 'status-progress';
                                    $status_label = 'In progress';
                                } else {
                                    $status_class = 'status-open';
                                    $status_label = 'Open';
                                }
                            ?>
                            <div class="status-card">
                                <div class="status-head">
                                    <div class="status-club"><?php echo htmlspecialchars($row['club_name']); ?></div>
                                    <div class="status-actions">
                                        <span class="status-pill <?php echo $status_class; ?>"><?php echo $status_label; ?></span>
                                        <?php if ($remaining > 0 && $saved_status !== 'cancelled'): ?>
                                            <form method="post" class="cancel-inline-form" onsubmit="return confirm('Cancel this pending request?');">
                                                <input type="hidden" name="action" value="cancel_request">
                                                <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">
                                                <input type="hidden" name="club_id" value="<?php echo $club_id; ?>">
                                                <button type="submit" class="cancel-btn" title="Cancel pending request">x</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="status-metrics">
                                    <div class="metric-chip">
                                        <span class="label">Requested</span>
                                        <span class="value"><?php echo $requested; ?></span>
                                    </div>
                                    <div class="metric-chip">
                                        <span class="label">Assigned</span>
                                        <span class="value"><?php echo $assigned; ?></span>
                                    </div>
                                    <div class="metric-chip">
                                        <span class="label">Remaining</span>
                                        <span class="value"><?php echo max(0, $remaining); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="panel">
                <h2>Pending Clubs</h2>
                <p class="subtext">Clubs still working on sending volunteers.</p>
                <?php if (!$selected_event_id): ?>
                    <div class="empty-state">Select an event to see pending clubs.</div>
                <?php elseif (empty($pending_clubs)): ?>
                    <div class="empty-state">All clubs are fulfilled or no requests yet.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Club</th>
                                <th>Remaining Volunteers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_clubs as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['club_name']); ?></td>
                                    <td><?php echo intval($row['remaining']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="panel panel-wide">
                <h2>Assigned Volunteers</h2>
                <p class="subtext">Assigned volunteers grouped by club. Click a club to expand members and their role.</p>
                <?php if (!$selected_event_id): ?>
                    <div class="empty-state">Select an event to see volunteer assignments.</div>
                <?php elseif (empty($assignments)): ?>
                    <div class="empty-state">No volunteers have been assigned yet.</div>
                <?php else: ?>
                    <div class="club-accordion">
                        <?php foreach ($assignments_by_club as $club_name => $members): ?>
                            <details class="club-group">
                                <summary>
                                    <span><?php echo htmlspecialchars($club_name); ?></span>
                                    <span><?php echo count($members); ?> member(s)</span>
                                </summary>
                                <div class="club-table-wrap">
                                    <table class="club-members-table">
                                        <thead>
                                            <tr>
                                                <th>Member</th>
                                                <th>Student ID</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($members as $member): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['student_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['student_email']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['club_role']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </details>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <script>
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').innerText = new Date().toLocaleDateString('en-US', options);

        const clubs = <?php echo json_encode($clubs); ?>;
        const existingRequests = <?php echo json_encode($existing_requests_map); ?>;
        const builder = document.getElementById('club-builder');
        const addRowBtn = document.getElementById('add-row');
        const requestForm = document.getElementById('request-form');
        const noteInput = document.getElementById('note-input');
        const deadlineInput = document.getElementById('deadline-input');
        const eventSelect = document.getElementById('event_id');

        const storageKey = (eventId) => `volunteer_builder_${eventId}`;

        function getRowData() {
            return Array.from(builder.querySelectorAll('.builder-row')).map(row => {
                const select = row.querySelector('select[name="club_id[]"]');
                const input = row.querySelector('input[name="club_count[]"]');
                return {
                    club_id: select ? select.value : '',
                    count: input ? input.value : ''
                };
            }).filter(item => item.club_id || item.count);
        }

        function saveDraft(eventId) {
            if (!eventId) return;
            const payload = {
                note: noteInput ? noteInput.value : '',
                deadline: deadlineInput ? deadlineInput.value : '',
                rows: getRowData()
            };
            localStorage.setItem(storageKey(eventId), JSON.stringify(payload));
        }

        function loadDraft(eventId) {
            if (!eventId) return false;
            const raw = localStorage.getItem(storageKey(eventId));
            if (!raw) return false;
            try {
                const payload = JSON.parse(raw);
                if (noteInput) noteInput.value = payload.note || '';
                if (deadlineInput) deadlineInput.value = payload.deadline || '';
                builder.innerHTML = '';
                (payload.rows || []).forEach(row => buildRow(row.club_id, row.count));
                if (!(payload.rows || []).length) {
                    buildRow();
                }
                return true;
            } catch (e) {
                return false;
            }
        }

        function buildRow(selectedId = '', countValue = '') {
            const row = document.createElement('div');
            row.className = 'builder-row';

            const select = document.createElement('select');
            select.className = 'admin-input';
            select.name = 'club_id[]';
            select.required = true;

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Select Club --';
            select.appendChild(defaultOption);

            clubs.forEach(club => {
                const option = document.createElement('option');
                option.value = club.club_id;
                option.textContent = club.club_name;
                if (String(club.club_id) === String(selectedId)) {
                    option.selected = true;
                }
                select.appendChild(option);
            });

            const input = document.createElement('input');
            input.type = 'number';
            input.className = 'admin-input input-compact';
            input.name = 'club_count[]';
            input.min = '1';
            input.max = '50';
            input.placeholder = '0';
            input.required = true;
            if (countValue) {
                input.value = countValue;
            }

            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'remove-btn';
            remove.textContent = 'X';
            remove.addEventListener('click', () => {
                row.remove();
                refreshClubOptions();
                saveDraft(requestForm ? requestForm.dataset.event : '');
            });

            select.addEventListener('change', () => {
                refreshClubOptions();
                saveDraft(requestForm ? requestForm.dataset.event : '');
            });
            input.addEventListener('input', () => saveDraft(requestForm ? requestForm.dataset.event : ''));

            row.appendChild(select);
            row.appendChild(input);
            row.appendChild(remove);
            builder.appendChild(row);

            refreshClubOptions();
        }

        function refreshClubOptions() {
            const selected = new Set();
            builder.querySelectorAll('select[name="club_id[]"]').forEach(sel => {
                if (sel.value) {
                    selected.add(sel.value);
                }
            });

            builder.querySelectorAll('select[name="club_id[]"]').forEach(sel => {
                const current = sel.value;
                Array.from(sel.options).forEach(option => {
                    if (!option.value) return;
                    option.disabled = option.value !== current && selected.has(option.value);
                });
            });
        }

        addRowBtn.addEventListener('click', () => {
            buildRow();
            saveDraft(requestForm ? requestForm.dataset.event : '');
        });

        const existingEntries = Object.entries(existingRequests);
        const currentEventId = requestForm ? requestForm.dataset.event : '';
        const loadedFromDraft = loadDraft(currentEventId);
        if (!loadedFromDraft) {
            if (existingEntries.length) {
                existingEntries.forEach(([clubId, count]) => buildRow(clubId, count));
            } else {
                buildRow();
            }
        }

        if (noteInput) {
            noteInput.addEventListener('input', () => saveDraft(currentEventId));
        }
        if (deadlineInput) {
            deadlineInput.addEventListener('change', () => saveDraft(currentEventId));
        }

        if (eventSelect) {
            eventSelect.addEventListener('change', () => {
                const currentId = requestForm ? requestForm.dataset.event : '';
                saveDraft(currentId);
                const nextId = eventSelect.value;
                if (!nextId) return;
                const url = new URL(window.location.href);
                url.searchParams.set('event_id', nextId);
                window.location.href = url.toString();
            });
        }

        window.addEventListener('beforeunload', () => {
            const currentId = requestForm ? requestForm.dataset.event : '';
            saveDraft(currentId);
        });

        <?php if ($clear_draft): ?>
        if (currentEventId) {
            localStorage.removeItem(storageKey(currentEventId));
        }
        <?php endif; ?>
    </script>
</body>
</html>
