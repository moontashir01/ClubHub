<?php
session_start();
include 'connection.php';
require_once 'notification_helpers.php';



$email = $_SESSION['Email'] ?? '';
$name = $_SESSION['Name'] ?? 'Club Member';

$club_query = mysqli_query($con, "
    SELECT clubs.club_id, clubs.club_name
    FROM `user`
    INNER JOIN `students` ON `user`.email = students.student_email
    INNER JOIN `club_members` ON club_members.student_id = students.student_id
    INNER JOIN `clubs` ON club_members.club_id = clubs.club_id
    WHERE `user`.email = '$email' AND club_members.active = 1
    LIMIT 1
");

$club_row = $club_query ? mysqli_fetch_assoc($club_query) : null;
$club_id = $club_row ? intval($club_row['club_id']) : 0;
$club_name = $club_row ? $club_row['club_name'] : 'No Club Found';

$flash = $_GET['msg'] ?? '';
$flash_type = $_GET['type'] ?? 'success';
$allowed_flash = ['success', 'error', 'warning'];
if (!in_array($flash_type, $allowed_flash, true)) {
    $flash_type = 'success';
}

function redirect_with_msg($event_id, $msg, $type = 'success') {
    header("Location: sendVolunteer.php?event_id=" . intval($event_id) . "&msg=" . urlencode($msg) . "&type=" . urlencode($type));
    exit();
}

function sync_single_request_status(mysqli $con, int $event_id, int $club_id): void {
    if ($event_id <= 0 || $club_id <= 0) {
        return;
    }

    $sync_stmt = $con->prepare("
        UPDATE volunteer_request_club r
        LEFT JOIN (
            SELECT club_id, event_id, COUNT(*) AS assigned_count
            FROM volunteer_requests
            WHERE event_id = ? AND club_id = ?
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
        WHERE r.event_id = ? AND r.club_id = ?
    ");

    if ($sync_stmt) {
        $sync_stmt->bind_param("iiii", $event_id, $club_id, $event_id, $club_id);
        $sync_stmt->execute();
    }
}

$event_requests = [];
if ($club_id) {
    $req_stmt = $con->prepare("
        SELECT r.event_id, r.requested_count, r.note, r.deadline, e.event_name, e.event_date
        FROM volunteer_request_club r
        INNER JOIN events e ON e.event_id = r.event_id
        WHERE r.club_id = ?
          AND e.event_availablity = 1
          AND (e.club_id IS NULL OR LOWER(COALESCE(e.event_creator, '')) = 'admin')
          AND LOWER(COALESCE(r.status, '')) <> 'cancelled'
        ORDER BY e.event_date DESC
    ");
    $req_stmt->bind_param("i", $club_id);
    $req_stmt->execute();
    $req_result = $req_stmt->get_result();
    while ($row = $req_result->fetch_assoc()) {
        $event_requests[] = $row;
    }
}

$selected_event_id = 0;
if (isset($_GET['event_id'])) {
    $selected_event_id = intval($_GET['event_id']);
} elseif (!empty($event_requests)) {
    $selected_event_id = intval($event_requests[0]['event_id']);
}

$event_ids = array_map(static function ($req) {
    return intval($req['event_id']);
}, $event_requests);
if ($selected_event_id && !in_array($selected_event_id, $event_ids, true)) {
    $selected_event_id = !empty($event_requests) ? intval($event_requests[0]['event_id']) : 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_event_id = intval($_POST['event_id'] ?? 0);
    $member_ids = $_POST['member_ids'] ?? [];
    $member_ids = array_values(array_filter($member_ids, function ($id) {
        return trim($id) !== '';
    }));

    if (!$club_id) {
        $flash = 'No club found for this account.';
        $flash_type = 'error';
    } elseif ($selected_event_id && !in_array($selected_event_id, $event_ids, true)) {
        $flash = 'Invalid event selection.';
        $flash_type = 'error';
    } elseif (!$selected_event_id) {
        $flash = 'Please select an event to continue.';
        $flash_type = 'error';
    } else {
        $check_stmt = $con->prepare("
            SELECT 1
            FROM volunteer_request_club
            WHERE event_id = ? AND club_id = ? AND LOWER(COALESCE(status, '')) <> 'cancelled'
        ");
        $check_stmt->bind_param("ii", $selected_event_id, $club_id);
        $check_stmt->execute();
        $has_request = $check_stmt->get_result()->num_rows > 0;

        if (!$has_request) {
            $flash = 'No volunteer request found for this event.';
            $flash_type = 'warning';
        } elseif (empty($member_ids)) {
            $flash = 'Select at least one member to send.';
            $flash_type = 'warning';
        } else {
            $requested_stmt = $con->prepare("SELECT requested_count FROM volunteer_request_club WHERE event_id = ? AND club_id = ?");
            $requested_stmt->bind_param("ii", $selected_event_id, $club_id);
            $requested_stmt->execute();
            $requested_row = $requested_stmt->get_result()->fetch_assoc();
            $requested_count = $requested_row ? intval($requested_row['requested_count']) : 0;

            $assigned_stmt = $con->prepare("
                SELECT COUNT(*) AS cnt
                FROM volunteer_requests
                WHERE event_id = ? AND club_id = ?
            ");
            $assigned_stmt->bind_param("ii", $selected_event_id, $club_id);
            $assigned_stmt->execute();
            $assigned_row = $assigned_stmt->get_result()->fetch_assoc();
            $assigned_count = $assigned_row ? intval($assigned_row['cnt']) : 0;

            $remaining_count = max(0, $requested_count - $assigned_count);
            $selected_count = count($member_ids);

            if ($remaining_count <= 0) {
                $flash = 'This request is already fulfilled. No more volunteers can be sent.';
                $flash_type = 'warning';
            } elseif ($selected_count > $remaining_count) {
                $flash = "You selected $selected_count members but only $remaining_count are still needed. Please adjust your selection.";
                $flash_type = 'warning';
            } else {
            $check_existing = $con->prepare("SELECT 1 FROM volunteer_requests WHERE event_id = ? AND student_id = ?");
            $select_member = $con->prepare("SELECT student_id, full_name, student_email FROM students WHERE student_id = ?");
            $insert_vol = $con->prepare("
                INSERT INTO volunteer_requests (club_id, full_name, student_id, student_email, event_id)
                VALUES (?, ?, ?, ?, ?)
            ");

            $added = 0;
            foreach ($member_ids as $member_id) {
                $member_id = trim($member_id);
                if ($member_id === '') {
                    continue;
                }

                $check_existing->bind_param("is", $selected_event_id, $member_id);
                $check_existing->execute();
                if ($check_existing->get_result()->num_rows > 0) {
                    continue;
                }

                $select_member->bind_param("s", $member_id);
                $select_member->execute();
                $member_result = $select_member->get_result()->fetch_assoc();
                if (!$member_result) {
                    continue;
                }

                $insert_vol->bind_param(
                    "isssi",
                    $club_id,
                    $member_result['full_name'],
                    $member_result['student_id'],
                    $member_result['student_email'],
                    $selected_event_id
                );

                if ($insert_vol->execute()) {
                    $added++;
                }
            }

            sync_single_request_status($con, $selected_event_id, $club_id);

            if ($added > 0) {
                // Notify admin that this club sent volunteers
                $eventNameForNotif = 'an event';
                foreach ($event_requests as $requested_event) {
                    if (intval($requested_event['event_id']) === $selected_event_id) {
                        $eventNameForNotif = $requested_event['event_name'];
                        break;
                    }
                }
                notifyAdmin(
                    $con,
                    "🚀 " . $club_name . " has sent $added volunteer(s) for \"" . $eventNameForNotif . "\".",
                    'reqVolunteer.php?event_id=' . $selected_event_id
                );
                redirect_with_msg($selected_event_id, "Sent $added volunteer(s) for this event.");
            } else {
                redirect_with_msg($selected_event_id, "No new volunteers were added. Selected member(s) may already be assigned.", 'warning');
            }
            }
        }
    }
}

$selected_request = null;
foreach ($event_requests as $req) {
    if (intval($req['event_id']) === $selected_event_id) {
        $selected_request = $req;
        break;
    }
}

$assigned_count = 0;
if ($club_id && $selected_event_id) {
    sync_single_request_status($con, $selected_event_id, $club_id);

    $assigned_stmt = $con->prepare("
        SELECT COUNT(*) AS cnt
        FROM volunteer_requests
        WHERE event_id = ? AND club_id = ?
    ");
    $assigned_stmt->bind_param("ii", $selected_event_id, $club_id);
    $assigned_stmt->execute();
    $assigned_row = $assigned_stmt->get_result()->fetch_assoc();
    $assigned_count = $assigned_row ? intval($assigned_row['cnt']) : 0;
}

$requested_count = $selected_request ? intval($selected_request['requested_count']) : 0;
$remaining_count = max(0, $requested_count - $assigned_count);

$members = [];
if ($club_id && $selected_event_id) {
    $members_stmt = $con->prepare("
        SELECT s.student_id, s.full_name, s.student_email, cm.Role
        FROM club_members cm
        INNER JOIN students s ON s.student_id = cm.student_id
        WHERE cm.club_id = ?
          AND cm.active = 1
          AND s.student_id NOT IN (SELECT student_id FROM volunteer_requests WHERE event_id = ? AND club_id = ?)
        ORDER BY s.full_name
    ");
    $members_stmt->bind_param("iii", $club_id, $selected_event_id, $club_id);
    $members_stmt->execute();
    $members_result = $members_stmt->get_result();
    while ($row = $members_result->fetch_assoc()) {
        $members[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Volunteers | ClubHub</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <style>
        .page-wrap { padding: 120px 5% 60px; min-height: 100vh; background: var(--dark-bg); }
        .page-title { font-size: 2.2rem; font-weight: 800; margin-bottom: 10px; }
        .page-sub { color: var(--muted); margin-bottom: 30px; max-width: 720px; }
        .panel-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .panel-card { background: var(--card); border-radius: 12px; padding: 22px; border: 1px solid #252533; }
        .panel-card h3 { margin: 0 0 10px; font-size: 1.1rem; }
        .panel-card p { margin: 0; color: var(--muted); font-size: 0.95rem; }
        .stat-value { font-size: 2rem; font-weight: 800; margin-top: 8px; color: var(--pink); }
        .banner { padding: 12px 16px; border-radius: 10px; margin-bottom: 18px; font-weight: 600; border: 1px solid transparent; }
        .banner.success { background: rgba(74, 222, 128, 0.12); color: #4ade80; border-color: rgba(74, 222, 128, 0.3); }
        .banner.error { background: rgba(248, 113, 113, 0.12); color: #f87171; border-color: rgba(248, 113, 113, 0.3); }
        .banner.warning { background: rgba(251, 191, 36, 0.12); color: #fbbf24; border-color: rgba(251, 191, 36, 0.3); }
        .form-row { display: grid; grid-template-columns: 1fr 200px; gap: 16px; align-items: end; margin-bottom: 20px; }
        .input-label { font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase; color: var(--muted); margin-bottom: 6px; display: block; }
        .input-control { width: 100%; padding: 12px 14px; border-radius: 8px; border: 1px solid #2a2a38; background: var(--card); color: var(--text); }
        .action-btn { background: var(--pink); color: white; border: none; padding: 12px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; }
        .back-btn { display: inline-block; margin-bottom: 18px; text-decoration: none; background: transparent; color: var(--text); border: 1px solid #2a2a38; padding: 10px 14px; border-radius: 8px; font-weight: 700; }
        .back-btn:hover { border-color: var(--pink); color: var(--pink); }
        .member-table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: 12px; overflow: hidden; }
        .member-table th, .member-table td { padding: 12px 14px; border-bottom: 1px solid #252533; text-align: left; font-size: 0.92rem; }
        .member-table th { color: var(--pink); text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1px; }
        .member-table tr:last-child td { border-bottom: none; }
        .member-table td small { color: var(--muted); }
        .limit-note { margin: 0 0 14px; color: var(--muted); font-size: 0.9rem; }
        .limit-note strong { color: var(--pink); }
        .empty-state { padding: 24px; text-align: center; color: var(--muted); background: var(--card); border-radius: 12px; border: 1px solid #252533; }
    </style>
</head>
<body>
    <nav>
        <div class="logo">ClubHub</div>
        <div class="profile-menu" id="profile-menu">
            <button class="profile-trigger" id="profile-trigger" type="button" aria-expanded="false"><?php echo htmlspecialchars($name); ?></button>
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

    <div class="page-wrap">
        <a class="back-btn" href="Club_dashboard.php">← Back to Dashboard</a>
        <div class="page-title">Send Volunteers</div>
        <div class="page-sub">Manage volunteer responses for <?php echo htmlspecialchars($club_name); ?>. Select an event request and choose members to send.</div>

        <?php if ($flash): ?>
            <div class="banner <?php echo htmlspecialchars($flash_type); ?>"><?php echo htmlspecialchars($flash); ?></div>
        <?php endif; ?>

        <?php if (!$club_id): ?>
            <div class="empty-state">No club is associated with your account.</div>
        <?php elseif (empty($event_requests)): ?>
            <div class="empty-state">No volunteer requests found for your club yet.</div>
        <?php else: ?>
            <form method="get">
                <div class="form-row">
                    <div>
                        <label class="input-label" for="event_id">Select Requested Event</label>
                        <select class="input-control" name="event_id" id="event_id" onchange="this.form.submit()">
                            <?php foreach ($event_requests as $req): ?>
                                <option value="<?php echo $req['event_id']; ?>" <?php echo ($selected_event_id == $req['event_id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($req['event_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="input-label">Requested Count</label>
                        <div class="input-control" style="display:flex; align-items:center;"><?php echo $requested_count; ?></div>
                    </div>
                </div>
            </form>

            <div class="panel-grid">
                <div class="panel-card">
                    <h3>Assigned</h3>
                    <p>Volunteers already sent</p>
                    <div class="stat-value"><?php echo $assigned_count; ?></div>
                </div>
                <div class="panel-card">
                    <h3>Remaining</h3>
                    <p>Still needed for this event</p>
                    <div class="stat-value"><?php echo $remaining_count; ?></div>
                </div>
                <div class="panel-card">
                    <h3>Notes</h3>
                    <p><?php echo htmlspecialchars($selected_request['note'] ?? 'No special notes.'); ?></p>
                    <?php if (!empty($selected_request['deadline'])): ?>
                        <p style="margin-top: 10px;"><small>Deadline: <?php echo htmlspecialchars($selected_request['deadline']); ?></small></p>
                    <?php endif; ?>
                </div>
            </div>

            <form method="post" data-remaining="<?php echo $remaining_count; ?>">
                <input type="hidden" name="event_id" value="<?php echo $selected_event_id; ?>">
                <?php if (empty($members)): ?>
                    <div class="empty-state">No eligible members available to send for this event.</div>
                <?php else: ?>
                    <p class="limit-note" id="limit-note">You can select up to <strong><?php echo $remaining_count; ?></strong> member(s).</p>
                    <table class="member-table">
                        <thead>
                            <tr>
                                <th>Select</th>
                                <th>Member</th>
                                <th>Role</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><input type="checkbox" name="member_ids[]" value="<?php echo htmlspecialchars($member['student_id']); ?>"></td>
                                    <td><?php echo htmlspecialchars($member['full_name']); ?><br><small><?php echo htmlspecialchars($member['student_id']); ?></small></td>
                                    <td><?php echo htmlspecialchars($member['Role']); ?></td>
                                    <td><small><?php echo htmlspecialchars($member['student_email']); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div style="margin-top: 18px;">
                        <button type="submit" class="action-btn">Send Selected Volunteers</button>
                    </div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
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

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                profileMenu.classList.remove('open');
                profileTrigger.setAttribute('aria-expanded', 'false');
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

        const sendForm = document.querySelector('form[data-remaining]');
        if (sendForm) {
            const remaining = parseInt(sendForm.dataset.remaining || '0', 10);
            const limitNote = document.getElementById('limit-note');
            const checkboxes = Array.from(sendForm.querySelectorAll('input[name="member_ids[]"]'));

            const updateNote = () => {
                const selected = checkboxes.filter(cb => cb.checked).length;
                if (limitNote) {
                    limitNote.innerHTML = `You can select up to <strong>${remaining}</strong> member(s). Selected <strong>${selected}</strong>.`;
                }
                if (remaining <= 0) {
                    checkboxes.forEach(cb => cb.disabled = true);
                }
            };

            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    const selected = checkboxes.filter(item => item.checked).length;
                    if (selected > remaining) {
                        cb.checked = false;
                    }
                    updateNote();
                });
            });

            updateNote();
        }
    </script>
</body>
</html>
