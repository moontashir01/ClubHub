<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($host, $user, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// একটি ছোট ফাংশন বানিয়ে দিলাম যাতে fetch_assoc() on bool এরর না আসে
function getCountSafe($conn, $query) {
    $res = $conn->query($query);
    if ($res && $res->num_rows > 0) {
        return $res->fetch_assoc()['t'] ?? 0;
    }
    return 0;
}

// ==========================================
// 1. OVERVIEW DATA
// ==========================================
$totalClubs = getCountSafe($conn, "SELECT COUNT(*) as t FROM clubs");
$totalEvents = getCountSafe($conn, "SELECT COUNT(*) as t FROM events");
$totalUsers = getCountSafe($conn, "SELECT COUNT(*) as t FROM user");

$pendingRooms = getCountSafe($conn, "SELECT COUNT(*) as t FROM room_bookings"); 
$pendingSpace = getCountSafe($conn, "SELECT COUNT(*) as t FROM space_bookings"); 
$volunteers = getCountSafe($conn, "SELECT COUNT(*) as t FROM volunteer_requests");

// ==========================================
// 2. USERS DATA
// ==========================================
$userData = [];
$res = $conn->query("SELECT Name, email FROM user");
if($res && $res->num_rows > 0) {
    while($row = $res->fetch_assoc()) {
        $row['vol_count'] = 0; 
        $userData[$row['email']] = $row;
    }
}

$volRes = $conn->query("SELECT student_email, COUNT(req_ID) as c FROM volunteer_requests GROUP BY student_email");
if($volRes && $volRes->num_rows > 0) {
    while($row = $volRes->fetch_assoc()) {
        if(isset($userData[$row['student_email']])) {
            $userData[$row['student_email']]['vol_count'] = $row['c'];
        }
    }
}

$userNamesArr = !empty($userData) ? array_column($userData, 'Name') : ['No Data'];
$userTasksArr = !empty($userData) ? array_column($userData, 'vol_count') : [0];

// ==========================================
// 3. CLUB RANKING & TASKS DATA
// ==========================================
$clubRankData = [];
$clubNamesList = []; 
$clubEventsList = [];
$clubMembersList = [];
$taskOnTime = [];
$taskLate = [];
$taskAttempts = [];

$clubRes = $conn->query("SELECT club_id, club_name FROM clubs");
if($clubRes && $clubRes->num_rows > 0) {
    while($row = $clubRes->fetch_assoc()) {
        $row['total_events'] = 0;
        $row['total_members'] = 0;
        $row['target'] = 0; 
        $row['achieved'] = 0; 
        $row['missed'] = 0;
        $clubRankData[$row['club_id']] = $row;
    }
}

$evRes = $conn->query("SELECT club_id, COUNT(id) as c FROM events GROUP BY club_id");
if($evRes && $evRes->num_rows > 0) {
    while($row = $evRes->fetch_assoc()) {
        if(isset($clubRankData[$row['club_id']])) {
            $clubRankData[$row['club_id']]['total_events'] = $row['c'];
        }
    }
}

$memRes = $conn->query("SELECT club_id, COUNT(id) as c FROM club_members GROUP BY club_id");
if($memRes && $memRes->num_rows > 0) {
    while($row = $memRes->fetch_assoc()) {
        if(isset($clubRankData[$row['club_id']])) {
            $clubRankData[$row['club_id']]['total_members'] = $row['c'];
        }
    }
}

$taskRes = $conn->query("
    SELECT e.club_id, COUNT(v.req_ID) as total_vols 
    FROM events e 
    LEFT JOIN volunteer_requests v ON e.id = v.event_id 
    GROUP BY e.club_id
");
if($taskRes && $taskRes->num_rows > 0) {
    while($row = $taskRes->fetch_assoc()) {
        if(isset($clubRankData[$row['club_id']])) {
            $clubRankData[$row['club_id']]['achieved'] = $row['total_vols'];
            $clubRankData[$row['club_id']]['target'] = $row['total_vols'] > 0 ? $row['total_vols'] + 5 : 0; 
            $clubRankData[$row['club_id']]['missed'] = $clubRankData[$row['club_id']]['target'] - $row['total_vols'];
        }
    }
}

if (!empty($clubRankData)) {
    usort($clubRankData, function($a, $b) { return $b['total_events'] <=> $a['total_events']; });

    foreach($clubRankData as $c) {
        $clubNamesList[] = $c['club_name'];
        $clubEventsList[] = $c['total_events'];
        $clubMembersList[] = $c['total_members'];
        
        $taskAttempts[] = $c['achieved'];
        $taskOnTime[] = $c['achieved']; 
        $taskLate[] = $c['missed'];
    }
} else {
    $clubNamesList = ['No Clubs'];
    $clubEventsList = [0];
    $clubMembersList = [0];
    $taskAttempts = [0];
    $taskOnTime = [0];
    $taskLate = [0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Analytics - Club Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --bg-dark: #0f172a; --bg-card: rgba(30, 41, 59, 0.85); /* Slightly transparent for background */
            --text-main: #f8fafc; --text-muted: #94a3b8;
            --accent: #3b82f6; --success: #10b981; --warning: #f59e0b; --danger: #ef4444; --border: rgba(51, 65, 85, 0.6);
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: var(--bg-dark);
            /* New Background Image with Dark Overlay */
            background-image: linear-gradient(rgba(15, 23, 42, 0.88), rgba(15, 23, 42, 0.95)), url('https://images.unsplash.com/photo-1551288049-bebda4e38f71?q=80&w=2070&auto=format&fit=crop');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--text-main); 
            margin: 0; display: flex; flex-direction: column; height: 100vh; overflow: hidden; 
        }
        
        .top-navbar {
            padding: 12px 30px; 
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center; z-index: 10;
        }
        .brand-logo { font-size: 26px; font-weight: 800; color: var(--text-main); letter-spacing: 1px; }
        .brand-logo span { color: var(--accent); }

        .menu-container {
            background: rgba(30, 41, 59, 0.5); border: 1px solid var(--border);
            border-radius: 16px; padding: 5px; backdrop-filter: blur(10px);
        }

        .menu { display: flex; justify-content: center; gap: 8px; margin: 0; padding: 0;}
        
        .link {
            display: inline-flex; justify-content: center; align-items: center; 
            width: 55px; height: 45px; border-radius: 12px; position: relative; z-index: 1; overflow: hidden; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            text-decoration: none; color: var(--text-muted); cursor: pointer;
            background: transparent; border: none; font-size: 15px;
        }
        
        .link:hover, .link.active { width: 160px; color: var(--accent); outline: 0; box-shadow: inset 0 0 0 1px rgba(59, 130, 246, 0.3); background: rgba(59, 130, 246, 0.1); }
        .link:hover .link-title, .link.active .link-title { transform: translateX(0); opacity: 1; }
        .link-icon { width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; left: 12px; position: absolute; font-size: 20px; }
        .link-title { transform: translateX(100%); transition: transform 0.3s; display: block; text-align: center; text-indent: 25px; width: 100%; font-weight: 600; opacity: 0; white-space: nowrap; }

        .user-profile { display: flex; align-items: center; gap: 15px; background: rgba(15, 23, 42, 0.5); padding: 6px 15px; border-radius: 30px; border: 1px solid var(--border); backdrop-filter: blur(5px);}
        .user-profile img { width: 35px; height: 35px; border-radius: 50%; }

        .main-content { flex: 1; overflow-y: auto; padding: 30px 40px; }
        .page-header { margin-bottom: 30px; }
        .page-header h1 { margin: 0; font-size: 28px; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.5); }
        .page-header p { margin: 5px 0 0 0; color: var(--text-muted); font-size: 15px; }
        
        .page-section { display: none; animation: fadeIn 0.4s ease; }
        .page-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(15px); } to { opacity: 1; transform: translateY(0); } }

        .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .kpi-card { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 22px; display: flex; align-items: center; justify-content: space-between; backdrop-filter: blur(10px); }
        .kpi-info h4 { margin: 0; font-size: 14px; color: var(--text-muted); font-weight: 600; text-transform: uppercase; }
        .kpi-info h2 { margin: 8px 0 0 0; font-size: 32px; font-weight: 800; color: var(--text-main); }
        .kpi-icon { width: 55px; height: 55px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 24px; }

        .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px; }
        .chart-container { background: var(--bg-card); border: 1px solid var(--border); border-radius: 16px; padding: 25px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); backdrop-filter: blur(10px);}
        .chart-header { margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 15px; font-weight: 700; font-size: 18px; color: #fff;}
        
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid var(--border); }
        th { color: var(--text-muted); font-weight: 600; text-transform: uppercase; font-size: 12px; }
        .rank-badge { background: var(--warning); color: #000; padding: 4px 10px; border-radius: 6px; font-weight: bold; font-size: 12px; }

        .individual-club-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; margin-top: 20px; }
        .club-perf-card { background: rgba(30, 41, 59, 0.6); border: 1px solid var(--border); border-radius: 16px; padding: 20px; text-align: center; backdrop-filter: blur(10px);}

        /* Uiverse Button */
        .btn-wrapper {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 1000;
        }

        .uiverse-btn {
            height: 4em;
            width: 15em;
            border: none;
            border-radius: 40px;
            background-color: #fff;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .uiverse-btn span {
            z-index: 1;
            display: inline-block;
            background-color: black;
            height: 3em;
            width: 11.5em;
            border-radius: 25px;
            color: #fff;
            line-height: 55px;
            font-size: 18px;
            letter-spacing: 3px;
            transition: all 0.5s;
        }

        .uiverse-btn .container {
            z-index: -1;
            width: 0;
            position: absolute;
            display: flex;
            justify-content: center;
            opacity: 0; 
            transition: all 0.4s;
        }

        .uiverse-btn .container i {
            padding: 0 10px;
            font-size: 24px;
            color: #000;
        }

        .uiverse-btn:hover span {
            width: 0;
            opacity: 0;
        }

        .uiverse-btn:hover .container {
            z-index: 2;
            width: 100%;
            opacity: 1;
        }
    </style>
</head>
<body>

    <div class="top-navbar">
        <div class="brand-logo">Club<span>Hub</span></div>
        <div class="menu-container">
            <div class="menu">
                <button class="link active" onclick="switchTab('overview', this)"><span class="link-icon"><i class="fas fa-chart-pie"></i></span><span class="link-title">Overview</span></button>
                <button class="link" onclick="switchTab('users', this)"><span class="link-icon"><i class="fas fa-users"></i></span><span class="link-title">Users</span></button>
                <button class="link" onclick="switchTab('clubs', this)"><span class="link-icon"><i class="fas fa-trophy"></i></span><span class="link-title">Rankings</span></button>
                <button class="link" onclick="switchTab('tasks', this)"><span class="link-icon"><i class="fas fa-clipboard-check"></i></span><span class="link-title">Club Tasks</span></button>
            </div>
        </div>
        <div class="user-profile">
            <i class="fas fa-bell" style="color: var(--warning);"></i>
            <img src="https://ui-avatars.com/api/?name=Admin&background=3b82f6&color=fff&bold=true" alt="Admin">
            <span style="font-weight: 600;">Admin</span>
        </div>
    </div>

    <div class="main-content">
        <div class="page-header">
            <h1 id="pageTitle">System Analytics</h1>
            <p id="pageDesc">Live tracking of clubs, events, and pending requests.</p>
        </div>

        <div id="overview" class="page-section active">
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-info"><h4>Total Users</h4><h2 style="color: var(--accent);"><?php echo $totalUsers; ?></h2></div>
                    <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.15); color: var(--accent);"><i class="fas fa-users"></i></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-info"><h4>Total Clubs</h4><h2 style="color: var(--success);"><?php echo $totalClubs; ?></h2></div>
                    <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.15); color: var(--success);"><i class="fas fa-layer-group"></i></div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-info"><h4>Pending Requests</h4><h2 style="color: var(--warning);"><?php echo $volunteers; ?></h2></div>
                    <div class="kpi-icon" style="background: rgba(245, 158, 11, 0.15); color: var(--warning);"><i class="fas fa-clipboard-list"></i></div>
                </div>
            </div>
            <div class="charts-grid">
                <div class="chart-container"><div class="chart-header">Club Strength Overview</div><canvas id="mainChart" height="100"></canvas></div>
                <div class="chart-container"><div class="chart-header">Resource Distribution</div><canvas id="pieChart" height="200"></canvas></div>
            </div>
        </div>

        <div id="users" class="page-section">
            <div class="chart-container" style="margin-bottom: 20px;">
                <div class="chart-header">Top Volunteers by Participation</div>
                <canvas id="userParticipationChart" height="80"></canvas>
            </div>
            <div class="chart-container">
                <div class="chart-header">All Registered Users</div>
                <div style="max-height: 400px; overflow-y: auto;">
                    <table>
                        <thead><tr><th>Name</th><th>Email</th><th>Task Score</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php if(!empty($userData)): ?>
                                <?php foreach($userData as $user): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($user['Name'] ?? ''); ?></strong></td>
                                    <td><?php echo htmlspecialchars($user['email'] ?? ''); ?></td>
                                    <td><span style="color: var(--accent); font-weight: bold;"><?php echo $user['vol_count']; ?></span> Tasks</td>
                                    <td><span style="color: var(--success); background: rgba(16,185,129,0.1); padding: 4px 8px; border-radius: 4px; font-size:12px;">Active</span></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="clubs" class="page-section">
            <div class="chart-container" style="margin-bottom: 20px;">
                <div class="chart-header">Total Events Organized by Club</div>
                <canvas id="clubEventsChart" height="80"></canvas>
            </div>
            <div class="chart-container">
                <div class="chart-header">Official Club Leaderboard</div>
                <table>
                    <thead><tr><th>Rank</th><th>Club Name</th><th>Total Successful Events</th><th>Performance</th></tr></thead>
                    <tbody>
                        <?php $rank = 1; if(!empty($clubRankData)): foreach($clubRankData as $club): ?>
                        <tr>
                            <td><?php if($rank==1) echo '<span class="rank-badge"><i class="fas fa-crown"></i> 1st</span>'; else if($rank==2) echo '<span class="rank-badge" style="background:#cbd5e1;">2nd</span>'; else echo "#".$rank; ?></td>
                            <td><strong><?php echo htmlspecialchars($club['club_name']); ?></strong></td>
                            <td><span style="color: var(--success); font-weight: bold;"><?php echo $club['total_events']; ?></span> Events</td>
                            <td><div style="width: 100%; background: rgba(15,23,42,0.8); border-radius: 5px; height: 10px;"><div style="width: <?php echo min(100, $club['total_events']*20); ?>%; background: linear-gradient(90deg, var(--success), #34d399); height: 100%; border-radius: 5px;"></div></div></td>
                        </tr>
                        <?php $rank++; endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="tasks" class="page-section">
            <div class="charts-grid" style="grid-template-columns: 1fr 1fr;">
                <div class="chart-container">
                    <div class="chart-header"><i class="fas fa-stopwatch" style="color:var(--warning); margin-right:8px;"></i>Task Fulfillment</div>
                    <canvas id="taskTimingChart" height="200"></canvas>
                </div>
                <div class="chart-container">
                    <div class="chart-header"><i class="fas fa-chart-bar" style="color:var(--accent); margin-right:8px;"></i>Total Volunteer Attempts</div>
                    <canvas id="taskAttemptsChart" height="200"></canvas>
                </div>
            </div>

            <div class="chart-container" style="margin-top: 20px; background: transparent; box-shadow: none; border: none; padding: 0;">
                <h3 style="margin: 0; color: var(--text-main); font-size: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);">
                    <i class="fas fa-bullseye" style="color:var(--accent); margin-right:8px;"></i> Individual Club Performance
                </h3>
                <div class="individual-club-grid" id="individualChartsContainer">
                    <?php if(!empty($clubRankData)): foreach($clubRankData as $index => $data): ?>
                        <div class="club-perf-card">
                            <div class="club-perf-title" style="font-weight: bold; margin-bottom: 5px;"><?php echo htmlspecialchars($data['club_name']); ?></div>
                            <div class="club-perf-stats" style="color: var(--text-muted); font-size: 14px; margin-bottom: 10px;">Admin Target: <?php echo $data['target']; ?> Vols</div>
                            <div style="position: relative; height: 180px; width: 100%; display: flex; justify-content: center;">
                                <canvas id="club_chart_<?php echo $data['club_id']; ?>"></canvas>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

    </div>

    <div class="btn-wrapper">
        <button class="uiverse-btn" onclick="window.location.href='admin_dashboard.php'">
            <span>BACK</span>
            <div class="container">
                <i class="fas fa-home"></i>
                <i class="fas fa-arrow-left"></i>
            </div>
        </button>
    </div>

    <script>
        const pageTitles = {
            'overview': { title: 'System Analytics', desc: 'Live tracking of clubs, events, and pending requests.' },
            'users': { title: 'User Analytics', desc: 'Monitor user registrations and overall platform activity.' },
            'clubs': { title: 'Club Leaderboard', desc: 'Ranking based on total successful events.' },
            'tasks': { title: 'Task & Volunteer Performance', desc: 'Monitor which clubs complete admin requests.' }
        };

        function switchTab(tabId, navElement) {
            document.querySelectorAll('.page-section').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.link').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            navElement.classList.add('active');
            document.getElementById('pageTitle').innerText = pageTitles[tabId].title;
            document.getElementById('pageDesc').innerText = pageTitles[tabId].desc;
        }

        Chart.defaults.color = '#e2e8f0'; 
        Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
        Chart.defaults.font.family = "'Inter', sans-serif";

        const defaultLabels = <?php echo json_encode(!empty($clubNamesList) ? $clubNamesList : ['No Data']); ?>;

        new Chart(document.getElementById('mainChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: defaultLabels,
                datasets: [{ label: 'Members', data: <?php echo json_encode(!empty($clubMembersList) ? $clubMembersList : [0]); ?>, backgroundColor: '#3b82f6', borderRadius: 6 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
        });

        new Chart(document.getElementById('pieChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Room Bookings', 'Space Bookings', 'Volunteer Reqs.'],
                datasets: [{ data: [<?php echo $pendingRooms; ?>, <?php echo $pendingSpace; ?>, <?php echo $volunteers; ?>], backgroundColor: ['#10b981', '#f59e0b', '#8b5cf6'], borderWidth: 0 }]
            },
            options: { cutout: '75%', plugins: { legend: { position: 'bottom', labels: { padding: 20, color: '#e2e8f0' } } } }
        });

        new Chart(document.getElementById('userParticipationChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(!empty($userNamesArr) ? $userNamesArr : ['No Users']); ?>,
                datasets: [{ label: 'Tasks Done', data: <?php echo json_encode(!empty($userTasksArr) ? $userTasksArr : [0]); ?>, backgroundColor: '#10b981', borderRadius: 4 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: {stepSize: 1} } } }
        });

        new Chart(document.getElementById('clubEventsChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: defaultLabels,
                datasets: [{ label: 'Events Hosted', data: <?php echo json_encode(!empty($clubEventsList) ? $clubEventsList : [0]); ?>, backgroundColor: '#f59e0b', borderRadius: 4 }]
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: {stepSize: 1} } } }
        });

        new Chart(document.getElementById('taskTimingChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: defaultLabels,
                datasets: [
                    { label: 'Achieved', data: <?php echo json_encode(!empty($taskOnTime) ? $taskOnTime : [0]); ?>, backgroundColor: '#10b981', borderRadius: 4 },
                    { label: 'Missed Target', data: <?php echo json_encode(!empty($taskLate) ? $taskLate : [0]); ?>, backgroundColor: '#ef4444', borderRadius: 4 }
                ]
            },
            options: { responsive: true, plugins: { legend: { position: 'top', labels: { color: '#e2e8f0'} } }, scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: {stepSize: 1} } } }
        });

        new Chart(document.getElementById('taskAttemptsChart').getContext('2d'), {
            type: 'polarArea',
            data: {
                labels: defaultLabels,
                datasets: [{
                    label: 'Total Volunteers',
                    data: <?php echo json_encode(!empty($taskAttempts) ? $taskAttempts : [0]); ?>,
                    backgroundColor: ['rgba(59, 130, 246, 0.7)', 'rgba(168, 85, 247, 0.7)', 'rgba(245, 158, 11, 0.7)'],
                    borderWidth: 1, borderColor: 'rgba(255,255,255,0.1)'
                }]
            },
            options: { responsive: true, plugins: { legend: { position: 'right', labels: { color: '#e2e8f0'} } } }
        });

        const perfData = <?php echo json_encode(array_values($clubRankData)); ?>;
        perfData.forEach(club => {
            const ctx = document.getElementById('club_chart_' + club.club_id);
            if(ctx) {
                new Chart(ctx.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Provided', 'Missed'],
                        datasets: [{
                            data: [club.achieved, club.missed],
                            backgroundColor: ['#3b82f6', '#ef4444'],
                            borderWidth: 0, hoverOffset: 4
                        }]
                    },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { color: '#e2e8f0'} } } }
                });
            }
        });
    </script>
</body>
</html>