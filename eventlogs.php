<?php
session_start();
include 'connection.php';

$email = $_SESSION['Email'] ?? '';
$clubId = intval($_SESSION['club_id'] ?? ($_SESSION['Club_id'] ?? 0));
$clubName = '';
$hasSecurityMessage = false;

$messageColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM events LIKE 'security_message'");
if ($messageColumnCheck && mysqli_num_rows($messageColumnCheck) > 0) {
    $hasSecurityMessage = true;
}

$adminColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM events LIKE 'admin_clearance'");
$hasAdminClearance = ($adminColumnCheck && mysqli_num_rows($adminColumnCheck) > 0);

$securityColumnCheck = mysqli_query($con, "SHOW COLUMNS FROM events LIKE 'security_clearance'");
$hasSecurityClearance = ($securityColumnCheck && mysqli_num_rows($securityColumnCheck) > 0);

if ($clubId <= 0) {
    $clubStmt = $con->prepare("\n        SELECT c.club_id, c.club_name\n        FROM `user` u\n        INNER JOIN students s ON s.student_email = u.email\n        INNER JOIN club_members cm ON cm.student_id = s.student_id AND cm.active = 1\n        INNER JOIN clubs c ON c.club_id = cm.club_id\n        WHERE u.email = ?\n          AND UPPER(cm.Role) LIKE 'EB%'\n        LIMIT 1\n    ");
    $clubStmt->bind_param("s", $email);
    $clubStmt->execute();
    $clubResult = $clubStmt->get_result()->fetch_assoc();

    if ($clubResult) {
        $clubId = intval($clubResult['club_id']);
        $clubName = $clubResult['club_name'];
        $_SESSION['club_id'] = $clubId;
    }
}

if ($clubId > 0 && $clubName === '') {
    $nameStmt = $con->prepare("SELECT club_name FROM clubs WHERE club_id = ? LIMIT 1");
    $nameStmt->bind_param("i", $clubId);
    $nameStmt->execute();
    $nameRow = $nameStmt->get_result()->fetch_assoc();
    $clubName = $nameRow['club_name'] ?? '';
}

$events = [];
if ($clubId > 0) {
    $messageSelect = $hasSecurityMessage ? "e.security_message" : "NULL AS security_message";
    $adminSelect = $hasAdminClearance ? "e.admin_clearance" : "'Pending' AS admin_clearance";
    $securitySelect = $hasSecurityClearance ? "e.security_clearance" : "'Pending' AS security_clearance";
    
    $eventsSql = "
        SELECT
            e.event_id,
            e.event_name,
            e.event_date,
            $securitySelect,
            $adminSelect,
            e.event_availablity,
            $messageSelect
        FROM events e
        WHERE e.club_id = ?
        ORDER BY e.event_date DESC, e.event_id DESC
    ";
    $eventsStmt = $con->prepare($eventsSql);
    $eventsStmt->bind_param("i", $clubId);
    $eventsStmt->execute();
    $eventsResult = $eventsStmt->get_result();

    while ($row = $eventsResult->fetch_assoc()) {
        $events[] = $row;
    }
}

$displayName = $_SESSION['Name'] ?? null;
if (!$displayName && isset($_SESSION['Email'])) {
    $displayName = explode('@', $_SESSION['Email'])[0];
}
if (!$displayName) {
    $displayName = 'Guest';
}

function resolve_status(array $event): array
{
    $security = strtolower(trim((string)($event['security_clearance'] ?? 'Pending')));
    $admin = strtolower(trim((string)($event['admin_clearance'] ?? 'Pending')));
    $isPublished = intval($event['event_availablity'] ?? 0) === 1 && $security === 'approved';

    if ($isPublished) {
        return ['Published', 'status-published'];
    }

    if ($security === 'pending') {
        return ['Awaiting Security', 'status-pending'];
    }

    if ($security === 'approved' && $admin === 'pending') {
        return ['Awaiting Admin', 'status-pending'];
    }

    if ($security === 'rejected' || $admin === 'rejected') {
        return ['Rejected', 'status-rejected'];
    }

    return ['Under Review', 'status-pending'];
}

function clearance_badge_class(string $clearance): string
{
    $value = strtolower(trim($clearance));
    if ($value === 'approved') {
        return 'status-published';
    }
    if ($value === 'rejected') {
        return 'status-rejected';
    }
    return 'status-pending';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Logs | ClubHub</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <style>
        html { scroll-snap-type: none; }
        body { min-height: 100vh; }
        .logs-section { min-height: 100vh; padding: 120px 5% 60px 5%; background: var(--dark-bg); }
        .logs-meta { color: var(--muted); margin: 0 0 20px 0; }
        .logs-header-row { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
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
        .back-link:hover { border-color: var(--pink); color: var(--pink); }
        .logs-card {
            background: var(--card);
            border: 1px solid var(--table-border);
            border-radius: 12px;
            overflow: hidden;
        }
        .table-wrap { overflow-x: auto; }
        .logs-table { width: 100%; border-collapse: collapse; color: var(--text); }
        .logs-table th,
        .logs-table td { padding: 14px 16px; border-bottom: 1px solid var(--table-row-border); text-align: left; }
        .logs-table thead th { background: rgba(255, 77, 141, 0.12); color: var(--text); font-size: 0.92rem; letter-spacing: 0.02em; }
        .logs-table tbody tr:hover { background: rgba(255, 255, 255, 0.03); }
        .status-badge {
            padding: 6px 12px;
            border-radius: 999px;
            font-weight: 600;
            font-size: 0.82rem;
            display: inline-block;
            border: 1px solid transparent;
        }
        .status-stack {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }
        .status-pending { background: rgba(234, 179, 8, 0.16); color: #facc15; border-color: rgba(234, 179, 8, 0.4); }
        .status-published { background: rgba(16, 185, 129, 0.18); color: #6ee7b7; border-color: rgba(16, 185, 129, 0.42); }
        .status-rejected { background: rgba(239, 68, 68, 0.18); color: #fca5a5; border-color: rgba(239, 68, 68, 0.38); }
        .empty-state { color: var(--muted); text-align: center; padding: 28px; }

        body.light-theme .logs-table thead th { background: rgba(255, 77, 141, 0.18); }
        body.light-theme .logs-table tbody tr:hover { background: rgba(15, 23, 42, 0.05); }
        body.light-theme .status-pending { color: #8a5a00; background: rgba(234, 179, 8, 0.24); border-color: rgba(234, 179, 8, 0.4); }
        body.light-theme .status-published { color: #0f5132; background: rgba(16, 185, 129, 0.22); border-color: rgba(16, 185, 129, 0.4); }
        body.light-theme .status-rejected { color: #842029; background: rgba(239, 68, 68, 0.2); border-color: rgba(239, 68, 68, 0.38); }
    </style>
</head>
<body>
    <nav>
        <div class="logo">ClubHub</div>
        <div class="profile-menu" id="profile-menu">
            <button class="profile-trigger" id="profile-trigger" type="button" aria-expanded="false"><?php echo htmlspecialchars($displayName); ?></button>
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

    <section class="logs-section">
        <div class="header">Event Logs</div>
        <div class="logs-header-row">
            <p class="logs-meta">
                <?php if ($clubName): ?>
                    Showing events for <strong><?php echo htmlspecialchars($clubName); ?></strong>.
                <?php else: ?>
                    Showing events for your club.
                <?php endif; ?>
            </p>
            <a href="Club_dashboard.php" class="back-link">Back to Club Dashboard</a>
        </div>

        <div class="logs-card">
            <div class="table-wrap">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Event Name</th>
                            <th>Event Date</th>
                            <th>Status</th>
                            <th>Security Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($clubId <= 0): ?>
                            <tr>
                                <td colspan="4" class="empty-state">No club is linked to this account.</td>
                            </tr>
                        <?php elseif (empty($events)): ?>
                            <tr>
                                <td colspan="4" class="empty-state">No events found for your club yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <?php [$statusText, $statusClass] = resolve_status($event); ?>
                                <?php
                                    $securityClearance = trim((string)($event['security_clearance'] ?? 'Pending'));
                                    $adminClearance = trim((string)($event['admin_clearance'] ?? 'Pending'));
                                    $isPublishedClearance =
                                        strtolower($securityClearance) === 'approved' &&
                                        strtolower($adminClearance) === 'approved';
                                    $reasonText = trim((string)($event['security_message'] ?? ''));
                                    if (strtolower($statusText) === 'rejected') {
                                        if ($reasonText === '') {
                                            $reasonText = 'No reason provided by Security Office.';
                                        }
                                    } else {
                                        if ($reasonText === '') {
                                            $reasonText = '-';
                                        }
                                    }
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($event['event_name']); ?></td>
                                    <td>
                                        <?php
                                            if (!empty($event['event_date'])) {
                                                echo htmlspecialchars(date("d M Y, h:i A", strtotime($event['event_date'])));
                                            } else {
                                                echo 'TBD';
                                            }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($isPublishedClearance): ?>
                                            <span class="status-badge status-published">Published</span>
                                        <?php else: ?>
                                            <div class="status-stack">
                                                <span class="status-badge <?php echo htmlspecialchars(clearance_badge_class($securityClearance)); ?>">
                                                    <?php echo htmlspecialchars('Security: ' . $securityClearance); ?>
                                                </span>
                                                <span class="status-badge <?php echo htmlspecialchars(clearance_badge_class($adminClearance)); ?>">
                                                    <?php echo htmlspecialchars('Admin: ' . $adminClearance); ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($reasonText); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

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
    </script>
</body>
</html>
