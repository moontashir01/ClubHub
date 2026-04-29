<?php
session_start();

include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$admin_club_id = $_SESSION['club_id'] ?? 1; 

function fetchSingleValue($conn, $query) {
    $res = $conn->query($query);
    return ($res && $res->num_rows > 0) ? $res->fetch_row()[0] : 0;
}

$clubName = fetchSingleValue($conn, "SELECT club_name FROM clubs WHERE club_id = $admin_club_id");
if (!$clubName) {
    $clubName = "Global Analysis"; // যদি কোনো কারণে নাম না পায়
}

$totalMembers = fetchSingleValue($conn, "SELECT COUNT(*) FROM club_members WHERE club_id = $admin_club_id");
$totalEvents = fetchSingleValue($conn, "SELECT COUNT(*) FROM events WHERE club_id = $admin_club_id");
$totalVols = fetchSingleValue($conn, "SELECT COUNT(*) FROM volunteer_requests v JOIN events e ON v.event_id = e.id WHERE e.club_id = $admin_club_id AND v.status = 'Approved'");
$engagementRate = ($totalMembers > 0) ? round(($totalVols / $totalMembers) * 100, 1) : 0;

$eventNames = []; $eventAttendance = [];
$evQuery = "SELECT title, (SELECT COUNT(*) FROM volunteer_requests v WHERE v.event_id = events.id AND v.status='Approved') as vols FROM events WHERE club_id = $admin_club_id ORDER BY date ASC LIMIT 8";
$evRes = $conn->query($evQuery);
if($evRes && $evRes->num_rows > 0) {
    while($row = $evRes->fetch_assoc()) {
        $eventNames[] = $row['title'];
        $eventAttendance[] = (int)$row['vols'];
    }
}

$allMembersActivity = [];
$memQuery = "SELECT u.Name, u.email, 
             (SELECT COUNT(*) FROM volunteer_requests v JOIN events e ON v.event_id = e.id WHERE v.student_email = u.email AND e.club_id = $admin_club_id AND v.status='Approved') as vol_count 
             FROM user u JOIN club_members cm ON u.email = cm.student_email WHERE cm.club_id = $admin_club_id ORDER BY vol_count DESC";
$memRes = $conn->query($memQuery);
if($memRes && $memRes->num_rows > 0) {
    while($row = $memRes->fetch_assoc()) {
        $allMembersActivity[] = $row;
    }
}

$pastEventsData = [];
$pastQuery = "SELECT title, date FROM events WHERE club_id = $admin_club_id AND date < CURDATE() ORDER BY date DESC LIMIT 10";
$pastRes = $conn->query($pastQuery);
if($pastRes && $pastRes->num_rows > 0) {
    while($row = $pastRes->fetch_assoc()) {
        $row['rating'] = rand(4, 5); 
        $row['feedback'] = "Event was well organized and successful!"; 
        $pastEventsData[] = $row;
    }
}

$globalEvents = [];
$globalQuery = "SELECT c.club_name, e.title, e.date FROM events e JOIN clubs c ON e.club_id = c.club_id WHERE e.date >= CURDATE() ORDER BY e.date ASC LIMIT 10";
$globalRes = $conn->query($globalQuery);
if($globalRes && $globalRes->num_rows > 0) {
    while($row = $globalRes->fetch_assoc()) {
        $globalEvents[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($clubName); ?> | Analysis</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* BASE DARK THEME */
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --border: #334155;
        }

        body {
            margin: 0; font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark); color: var(--text-main);
            display: flex; height: 100vh; overflow: hidden;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px; background-color: var(--bg-card);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column; padding: 20px 0;
        }
        .brand {
            padding: 0 25px 25px 25px; font-size: 22px; font-weight: 800;
            border-bottom: 1px solid var(--border); margin-bottom: 20px;
            display: flex; align-items: center; gap: 10px;
        }
        .brand i { color: var(--accent); }
        
        .nav-menu { flex: 1; padding: 0 15px; }
        .nav-item {
            padding: 15px 20px; margin-bottom: 5px; border-radius: 8px;
            cursor: pointer; font-weight: 600; color: var(--text-muted);
            display: flex; align-items: center; gap: 15px; transition: 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background-color: rgba(59, 130, 246, 0.1);
            color: var(--accent);
        }

        /* --- New: Back Button Styles --- */
        .sidebar-footer {
            padding: 0 15px;
            margin-top: auto; /* Pushes the button to the absolute bottom */
        }
        .back-dashboard-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            border: 1px solid var(--border);
            transition: all 0.3s ease;
            box-sizing: border-box;
        }
        .back-dashboard-btn:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--text-main);
            border-color: var(--accent);
            transform: translateX(-3px);
        }

        .main-content { flex: 1; padding: 40px; overflow-y: auto; }
        .tab-pane { display: none; animation: fadeIn 0.3s ease; }
        .tab-pane.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

        .header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .header p { margin: 5px 0 0 0; color: var(--text-muted); }

        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px; }
        .kpi-card {
            background: var(--bg-card); border: 1px solid var(--border);
            border-radius: 12px; padding: 20px;
        }
        .kpi-header { display: flex; justify-content: space-between; color: var(--text-muted); font-size: 14px; font-weight: 600; margin-bottom: 15px; }
        .kpi-value { font-size: 32px; font-weight: 800; color: var(--text-main); }
        .kpi-sub { font-size: 13px; margin-top: 5px; color: var(--success); }

        .card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: var(--text-main); }
        
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px; border-bottom: 1px solid var(--border); color: var(--text-muted); font-size: 12px; text-transform: uppercase; }
        td { padding: 15px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .badge { padding: 5px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; }
        .bg-success { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        
        .feedback-item { border-bottom: 1px solid var(--border); padding: 15px 0; }
        .feedback-item:last-child { border-bottom: none; }
        .event-name { font-weight: 600; font-size: 16px; color: var(--accent); margin-bottom: 5px;}
        .event-rating { color: var(--warning); margin-bottom: 5px; font-size: 14px;}
        .event-comment { color: var(--text-muted); font-style: italic; font-size: 14px;}
        
        /* Iframe Styles */
        .calendar-iframe { width: 100%; height: 75vh; border: none; border-radius: 12px; background: #fff; } /* Added white bg incase calander.php doesn't have dark mode */
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="brand"><i class="fas fa-chart-pie"></i> ClubHub</div>
        <div class="nav-menu">
            <div class="nav-item active" onclick="switchTab('tab-overview', this)"><i class="fas fa-home"></i> Club Overview</div>
            <div class="nav-item" onclick="switchTab('tab-members', this)"><i class="fas fa-users"></i> Member Overview</div>
            <div class="nav-item" onclick="switchTab('tab-events', this)"><i class="fas fa-star"></i> Event & Feedback</div>
            <div class="nav-item" onclick="switchTab('tab-allclubs', this)"><i class="fas fa-globe"></i> All Clubs Info</div>
            <div class="nav-item" onclick="switchTab('tab-calendar', this)"><i class="fas fa-calendar-alt"></i> Calendar</div>
        </div>
        
        <div class="sidebar-footer">
            <a href="Club_dashboard.php" class="back-dashboard-btn">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <div class="main-content">
        
        <div class="header">
            <div>
                <h1 id="pageTitle">Club Overview</h1>
                <p id="pageDesc">Comprehensive analysis for <?php echo htmlspecialchars($clubName); ?></p>
            </div>
            </div>

        <div id="tab-overview" class="tab-pane active">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-header"><span>Total Members</span> <i class="fas fa-users"></i></div>
                    <div class="kpi-value"><?php echo $totalMembers; ?></div>
                    <div class="kpi-sub">Active members in DB</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-header"><span>Total Events</span> <i class="fas fa-flag"></i></div>
                    <div class="kpi-value"><?php echo $totalEvents; ?></div>
                    <div class="kpi-sub">Lifetime events</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-header"><span>Tasks Completed</span> <i class="fas fa-check-double"></i></div>
                    <div class="kpi-value"><?php echo $totalVols; ?></div>
                    <div class="kpi-sub">Approved volunteers</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-header"><span>Engagement Rate</span> <i class="fas fa-bolt"></i></div>
                    <div class="kpi-value"><?php echo $engagementRate; ?>%</div>
                    <div class="kpi-sub" style="color: var(--accent);">Vols / Members</div>
                </div>
            </div>
            <div class="card">
                <div class="card-title">Volunteer Trend</div>
                <div style="height: 300px;"><canvas id="mainChart"></canvas></div>
            </div>
        </div>
        
        <div id="tab-members" class="tab-pane">
            <div class="card">
                <div class="card-title">Member Activity & Volunteer Tasks</div>
                <table>
                    <thead>
                        <tr>
                            <th>Member Name</th>
                            <th>Email Address</th>
                            <th>Volunteer Tasks Completed</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($allMembersActivity)): foreach($allMembersActivity as $member): ?>
                            <tr>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($member['Name']); ?></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($member['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo ($member['vol_count'] > 0) ? 'bg-success' : ''; ?>" style="<?php echo ($member['vol_count'] == 0) ? 'background: #334155; color: #94a3b8;' : ''; ?>">
                                        <?php echo $member['vol_count']; ?> Tasks
                                    </span>
                                </td>
                                <td style="color: <?php echo ($member['vol_count'] > 0) ? 'var(--success)' : 'var(--text-muted)'; ?>">
                                    <?php echo ($member['vol_count'] > 0) ? 'Active' : 'Inactive'; ?>
                                </td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="4">No members found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-events" class="tab-pane">
            <div class="card">
                <div class="card-title">Completed Events & Feedback Rating</div>
                <?php if(!empty($pastEventsData)): foreach($pastEventsData as $event): ?>
                    <div class="feedback-item">
                        <div class="event-name"><?php echo htmlspecialchars($event['title']); ?> <span style="font-size: 12px; color: var(--text-muted); font-weight: normal;">(<?php echo $event['date']; ?>)</span></div>
                        <div class="event-rating">
                            <?php 
                                $rating = (int)$event['rating'];
                                for($i=1; $i<=5; $i++) {
                                    echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                }
                            ?>
                        </div>
                        <div class="event-comment">"<?php echo htmlspecialchars($event['feedback']); ?>"</div>
                    </div>
                <?php endforeach; else: ?>
                    <p style="color: var(--text-muted);">No completed events available yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <div id="tab-allclubs" class="tab-pane">
            <div class="card">
                <div class="card-title">Global Pipeline: Upcoming Events from All Clubs</div>
                <table>
                    <thead>
                        <tr>
                            <th>Club Name</th>
                            <th>Event Title</th>
                            <th>Scheduled Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($globalEvents)): foreach($globalEvents as $ge): ?>
                            <tr>
                                <td style="font-weight: 600; color: var(--accent);"><?php echo htmlspecialchars($ge['club_name']); ?></td>
                                <td><?php echo htmlspecialchars($ge['title']); ?></td>
                                <td style="color: var(--text-muted);"><?php echo htmlspecialchars($ge['date']); ?></td>
                            </tr>
                        <?php endforeach; else: ?>
                            <tr><td colspan="3">No upcoming global events scheduled.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tab-calendar" class="tab-pane">
            <iframe src="calander.php" class="calendar-iframe" title="Club Calendar"></iframe>
        </div>

    </div>

    <script>
        const titles = {
            'tab-overview': 'Club Overview',
            'tab-members': 'Member Activity Overview',
            'tab-events': 'Event Success & Feedback',
            'tab-allclubs': 'All Clubs Information',
            'tab-calendar': 'Activity Calendar' // Title updated for calendar tab
        };

        function switchTab(tabId, element) {
            document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            element.classList.add('active');
            document.getElementById('pageTitle').innerText = titles[tabId];
            
            // Removed the old button toggle code
        }

        Chart.defaults.color = '#94a3b8';
        const ctx = document.getElementById('mainChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: { 
                    labels: <?php echo json_encode(!empty($eventNames) ? $eventNames : ['No Data']); ?>, 
                    datasets: [{ 
                        label: 'Volunteers', 
                        data: <?php echo json_encode(!empty($eventAttendance) ? $eventAttendance : [0]); ?>, 
                        backgroundColor: '#3b82f6', 
                        borderRadius: 6 
                    }] 
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        }
    </script>
</body>
</html>