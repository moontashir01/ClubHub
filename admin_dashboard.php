<?php
session_start();
include 'connection.php';

// Include PDF Libraries
require_once('fpdf/fpdf.php');
require_once('fpdi/src/autoload.php');

// Check if admin is logged in
if (!isset($_SESSION['AdminRole'])) {
    header("Location: login.php");
    exit();
}
$email = $_SESSION['AdminEmail'] ?? '';
$adminRole = $_SESSION['AdminRole'];

if ($adminRole === 'Student Affairs') {
    $name = 'Office of Student Affairs';
} elseif ($adminRole === 'Security') {
    $name = 'Campus Security Head';
} elseif ($adminRole === 'Registrar') {
    $name = 'University Registrar';
} else {
    $name = 'System Admin';
}
$initial = strtoupper(substr($name, 0, 1));

// ==========================================
// 🔴 NEW SETUP: Approve/Reject Logic Here (with rejection note + PDF stamp)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['app_id'])) {
    $app_id = intval($_POST['app_id']);
    // Security Fix: Prevent SQL Injection
    $action = mysqli_real_escape_string($con, $_POST['action']);
    $rejection_note = isset($_POST['rejection_note']) ? mysqli_real_escape_string($con, $_POST['rejection_note']) : '';

    // 🔴 PDF STAMP LOGIC 
    // Fetch the original PDF path
    $file_query = @mysqli_query($con, "SELECT letter_content FROM vc_applications WHERE id=$app_id");
    $file_row = mysqli_fetch_assoc($file_query);
    $original_file = $file_row['letter_content'];
    $stamped_path = $original_file; // Default to original if stamping fails

    if (!empty($original_file) && file_exists($original_file)) {
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($original_file);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                
                // Stamp only on the First Page
                if ($pageNo == 1) {
                    $pdf->SetFont('Arial', 'B', 24);
                    
                    if ($action === 'Approved') {
                        $pdf->SetTextColor(16, 185, 129); // Green Seal
                        $pdf->SetXY(20, 20); // Top Right corner roughly
                        $pdf->Cell(0, 10, 'APPROVED', 0, 1, 'R');
                    } else if ($action === 'Rejected') {
                        $pdf->SetTextColor(239, 68, 68); // Red Seal
                        $pdf->SetXY(20, 20);
                        $pdf->Cell(0, 10, 'REJECTED', 0, 1, 'R');
                        
                        // Add Rejection Note beneath it
                        if ($rejection_note !== '') {
                            $pdf->SetFont('Arial', 'I', 12);
                            $pdf->SetXY(20, 32);
                            $pdf->SetLeftMargin(100); // Push text to the right
                            $pdf->MultiCell(0, 6, "Note: " . $rejection_note, 0, 'R');
                            $pdf->SetLeftMargin(10); // Reset margin
                        }
                    }
                }
            }
            
            // Create a new filename so we don't overwrite the original blindly (good for backups)
            $path_parts = pathinfo($original_file);
            $stamped_path = $path_parts['dirname'] . '/' . $path_parts['filename'] . '_stamped_' . time() . '.' . $path_parts['extension'];
            
            // Save the newly stamped PDF
            $pdf->Output('F', $stamped_path);

        } catch (Exception $e) {
            // If the PDF is encrypted or invalid, it gracefully falls back to the original file
            error_log("PDF Stamping Error: " . $e->getMessage());
        }
    }

    // UPDATE DATABASE WITH STATUS AND NEW PDF PATH
    $update_query = "UPDATE vc_applications SET status='$action', letter_content='$stamped_path'";
    if ($action === 'Rejected' && $rejection_note !== '') {
        $update_query .= ", rejection_note = '$rejection_note'";
    }
    $update_query .= " WHERE id=$app_id";

    if (mysqli_query($con, $update_query)) {
        echo "<script>alert('Application successfully $action!'); window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
    } else {
        echo "<script>alert('Error updating status!');</script>";
    }
}

// Permission mapping for 4 types of applications (all admins SEE everything, but only specific roles can APPROVE)
$approvalPermissions = [
    'Room Booking Permission' => ['Registrar', 'Student Affairs', 'System Admin'],
    'Budget Approval' => ['Student Affairs', 'System Admin'],
    'Permission to Host External Guests' => ['Security', 'System Admin'],
    'Guest Permission' => ['Security', 'System Admin'],
    'General Event Permission' => ['Student Affairs', 'Registrar', 'System Admin'],
    'General Club Activity Permission' => ['Student Affairs', 'Registrar', 'System Admin'],
];

// Fetch all applications for the new table
$query_apps = "SELECT * FROM vc_applications ORDER BY id DESC";
$result_apps = @mysqli_query($con, $query_apps);

// ==========================================
// FETCH BASIC STATS
// ==========================================
$totalClubs = 0;
$queryClubs = "SELECT COUNT(*) as count FROM clubs";
$resultClubs = @mysqli_query($con, $queryClubs);
if ($resultClubs) {
    $row = mysqli_fetch_assoc($resultClubs);
    $totalClubs = $row['count'] ?? 0;
}
$totalBookings = 0;
$queryBookings = "SELECT COUNT(*) as count FROM room_bookings";
$resultBookings = @mysqli_query($con, $queryBookings);
if ($resultBookings) {
    $row = mysqli_fetch_assoc($resultBookings);
    $totalBookings = $row['count'] ?? 0;
}
$totalVolunteers = 0;
$queryVolunteers = "SELECT COUNT(*) as count FROM volunteer_requests";
$resultVolunteers = @mysqli_query($con, $queryVolunteers);
if ($resultVolunteers) {
    $row = mysqli_fetch_assoc($resultVolunteers);
    $totalVolunteers = $row['count'] ?? 0;
}


$clubNames = [];
$clubEvents = [];

$analysisQuery = "SELECT c.club_name, COUNT(e.club_id) as event_count
                  FROM clubs c
                  LEFT JOIN events e ON c.club_id = e.club_id
                  GROUP BY c.club_id, c.club_name
                  ORDER BY event_count DESC
                  LIMIT 5";
$analysisResult = @mysqli_query($con, $analysisQuery);
if ($analysisResult) {
    while($row = mysqli_fetch_assoc($analysisResult)) {
        $clubNames[] = $row['club_name'];
        $clubEvents[] = $row['event_count'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center | ClubHub</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        }
        
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; }
        .sidebar { width: 280px; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; box-sizing: border-box; z-index: 10; }
        .logo { font-size: 1.8rem; font-weight: 800; color: var(--text); margin-bottom: 40px; display: flex; align-items: center; gap: 10px; letter-spacing: 1px; }
        .logo span { color: var(--primary); }
        .admin-profile { background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 30px; display: flex; align-items: center; gap: 15px; transition: 0.3s; }
        .admin-profile:hover { border-color: var(--primary); background: rgba(255, 71, 126, 0.05); }
        .avatar { width: 45px; height: 45px; border-radius: 12px; background: linear-gradient(135deg, var(--primary), #ff9a9e); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
        .admin-info h4 { margin: 0; font-size: 1rem; color: var(--text); }
        .admin-info p { margin: 0; font-size: 0.75rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; font-weight: bold; margin-top: 4px; }
        .nav-label { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; font-weight: bold; }
        .main-content { flex: 1; padding: 40px 50px; overflow-y: auto; position: relative; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .date-display { color: var(--muted); font-size: 0.9rem; background: var(--surface); padding: 10px 20px; border-radius: 20px; border: 1px solid var(--border); }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); padding: 25px; border-radius: 20px; position: relative; overflow: hidden; display: flex; align-items: center; gap: 20px; }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: var(--primary); border-radius: 4px 0 0 4px; }
        .stat-icon { font-size: 2.5rem; opacity: 0.8; }
        .stat-details h3 { margin: 0; font-size: 2rem; font-weight: 800; color: var(--text); }
        .stat-details p { margin: 0; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }
        .quick-analysis-container { background: var(--surface); border: 1px solid var(--border); padding: 25px; border-radius: 20px; margin-bottom: 40px; }
        
        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; margin-bottom: 40px;}
        .action-card { background: rgba(19, 20, 31, 0.6); backdrop-filter: blur(12px); border: 1px solid var(--border); border-radius: 24px; padding: 35px 30px; cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: flex-start; }
        .action-card:hover { transform: translateY(-8px); border-color: var(--primary); box-shadow: 0 15px 35px var(--primary-glow); background: var(--surface-hover); }
        .card-icon { font-size: 2.5rem; margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px solid var(--border); transition: 0.3s; }
        .action-card:hover .card-icon { background: var(--primary); color: white; border-color: var(--primary); transform: scale(1.1); }
        .action-card h3 { margin: 0 0 10px 0; font-size: 1.3rem; font-weight: 700; }
        .action-card p { margin: 0; color: var(--muted); font-size: 0.95rem; line-height: 1.5; }
        .action-card.restricted { cursor: not-allowed; border-color: rgba(255,255,255,0.02); }
        .action-card.restricted:hover { transform: none; box-shadow: none; border-color: rgba(255,255,255,0.05); }
        .lock-overlay { position: absolute; inset: 0; background: rgba(9, 10, 15, 0.8); backdrop-filter: blur(4px); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: 0.3s ease; border-radius: 24px; }
        .action-card.restricted:hover .lock-overlay { opacity: 1; }
        .lock-overlay span { font-size: 3rem; margin-bottom: 10px; }
        .lock-overlay p { color: #f87171; font-weight: bold; font-size: 0.9rem; text-align: center; padding: 0 20px; }
        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 40px; width: 100%; max-width: 500px; position: relative; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .modal-close { position: absolute; top: 20px; right: 25px; background: none; border: none; color: var(--muted); font-size: 1.8rem; cursor: pointer; transition: 0.2s; }
        .modal-close:hover { color: var(--primary); }
        .modal-title { margin-top: 0; color: white; font-size: 1.5rem; margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        
        /* Sidebar Tooltips CSS */
        .tooltip-container { background: rgb(3, 169, 244); background: linear-gradient(138deg, rgba(3, 169, 244, 1) 15%, rgba(63, 180, 233, 1) 65%); position: relative; cursor: pointer; font-size: 15px; padding: 0.7em 0.7em; border-radius: 50px; box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1); margin-bottom: 15px; text-align: center; font-weight: bold; }
        .tooltip-container:hover { background: #fff; transition: all 0.6s; }
        .tooltip-container .text { display: flex; align-items: center; justify-content: center; color: #fff; fill: #fff; transition: all 0.2s; }
        .tooltip-container:hover .text { color: rgb(3, 169, 244); fill: rgb(3, 169, 244); transition: all 0.6s; }
        .tooltip1, .tooltip2, .tooltip3 { position: absolute; top: 100%; left: 50%; transform: translateX(-50%); opacity: 0; visibility: hidden; background: #fff; padding: 10px; border-radius: 50px; transition: opacity 0.3s, visibility 0.3s, top 0.3s, background 0.3s; z-index: 100; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2); font-size: 12px; white-space: nowrap; }
        .tooltip1 { fill: #03a9f4; color: #03a9f4; }
        .tooltip2 { background: #03a9f4; fill: #00001a; color: #00001a; left: 50%; }
        .tooltip3 { fill: #1db954; color: #1db954; left: 50%; }
        .tooltip-container:hover .tooltip1 { top: 150%; opacity: 1; visibility: visible; background: #fff; transform: translate(-50%, -5px); display: flex; align-items: center; justify-content: center; }
        .tooltip-container:hover .tooltip1:hover { background: #03a9f4; fill: #fff; color: #fff;}
        .tooltip-container:hover .tooltip2 { top: -120%; opacity: 1; visibility: visible; background: #fff; transform: translate(-50%, -5px); display: flex; align-items: center; justify-content: center; }
        .tooltip-container:hover .tooltip2:hover { background: #1a1a1a; fill: #fff; color: #fff;}
        .tooltip-container:hover .tooltip3 { top: 150%; opacity: 1; visibility: visible; background: #fff; transform: translate(-50%, -5px); display: flex; align-items: center; justify-content: center; }
        .tooltip-container:hover .tooltip3:hover { background: #1db954; fill: #fff; color: #fff;}
        
        /* Logout Section - Enhanced & Attractive */
        .logout-wrapper { margin-top: auto; display: flex; justify-content: center; width: 100%; padding: 20px 0; border-top: 1px solid rgba(255, 255, 255, 0.05); }
        .tooltip { --color-background: 239, 68, 68; --size-diameter: 50px; border: none; border-radius: 14px; cursor: pointer; display: flex; height: var(--size-diameter); width: var(--size-diameter); justify-content: center; align-items: center; position: relative; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .tooltip:hover { background: var(--color-background); transform: translateY(-3px); box-shadow: 0 10px 20px rgba(239, 68, 68, 0.3); border-color: var(--color-background); }
        .tooltip__content { width: 22px; height: 22px; color: #ef4444; z-index: 3; transition: all 0.3s; }
        .tooltip:hover .tooltip__content { color: white; transform: scale(1.1); }
        .tooltip__label { position: absolute; bottom: 130%; left: 50%; transform: translateX(-50%) translateY(10px); background: #fff; color: #000; padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; font-weight: 700; white-space: nowrap; box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3); opacity: 0; visibility: hidden; transition: all 0.3s ease; pointer-events: none; }
        .tooltip__label::after { content: ""; position: absolute; top: 100%; left: 50%; transform: translateX(-50%); border-width: 6px; border-style: solid; border-color: #fff transparent transparent transparent; }
        .tooltip:hover .tooltip__label { opacity: 1; visibility: visible; transform: translateX(-50%) translateY(0); }
        
        /* Application Table */
        .admin-table-container { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; padding: 1px; }
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
        .admin-table th, .admin-table td { padding: 18px 20px; border-bottom: 1px solid var(--border); }
        .admin-table th { background: rgba(0,0,0,0.2); color: var(--muted); font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; }
        .admin-table tr:last-child td { border-bottom: none; }
        .admin-table tr:hover { background: var(--surface-hover); }
        
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display: inline-block;}
        .badge-pending { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .badge-approved { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-rejected { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-sm { padding: 8px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.8rem; transition: 0.3s; margin-right: 5px; display: inline-flex; align-items: center; text-decoration: none; }
        .btn-view { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .btn-view:hover { background: #3b82f6; color: white; }
        .btn-approve { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .btn-approve:hover { background: #10b981; color: white; }
        .btn-reject { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-reject:hover { background: #ef4444; color: white; }
        .inline-form { display: inline-block; margin: 0; }
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
        <div class="nav-label">Main Menu</div>
        
        <div class="tooltip-container" onclick="window.location.href='admin_dashboard.php'">
            <span class="text">Dashboard</span>
            <div class="tooltip1">Main Panel</div>
        </div>
        <div class="tooltip-container" onclick="window.location.href='system_logs.php'">
            <span class="text">System Logs</span>
            <div class="tooltip2">View Activity</div>
        </div>
        <div class="tooltip-container" onclick="window.location.href='settings.php'">
            <span class="text">Settings</span>
            <div class="tooltip3">Preferences</div>
        </div>
        <div class="logout-wrapper">
            <div class="tooltip" onclick="window.location.href='logout.php'">
                <svg class="tooltip__content" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                <svg class="tooltip__label" viewBox="0 0 100 100">
                    <text x="50" y="50">Logout</text>
                </svg>
            </div>
        </div>
    </aside>
    <main class="main-content">
        <div class="header">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 700; background: linear-gradient(to right, #fff, #aaa); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Command Center</h1>
                <p style="margin: 5px 0 0 0; color: var(--primary); font-weight: bold; letter-spacing: 1px; text-transform: uppercase; font-size: 0.85rem;">
                    🟢 Logged in as: <?php echo htmlspecialchars($name); ?>
                </p>
            </div>
            <div class="date-display" id="current-date"></div>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-details">
                    <h3><?php echo $totalClubs; ?></h3>
                    <p>Active Clubs</p>
                </div>
            </div>
            <div class="stat-card" style="--primary: #4ade80;">
                <div class="stat-icon">📅</div>
                <div class="stat-details">
                    <h3><?php echo $totalBookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card" style="--primary: #60a5fa;">
                <div class="stat-icon">🙋‍♂️</div>
                <div class="stat-details">
                    <h3><?php echo $totalVolunteers; ?></h3>
                    <p>Volunteer Requests</p>
                </div>
            </div>
        </div>
        <h2 style="font-size: 1.2rem; color: var(--muted); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Quick Analysis: Top Clubs by Events</h2>
        <div class="quick-analysis-container">
            <canvas id="quickAnalyticsChart" height="70"></canvas>
        </div>
        <h2 style="font-size: 1.2rem; color: var(--muted); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Administrative Modules</h2>
        <div class="action-grid" id="dashboard-grid"></div>

        <div id="applications-desk" style="display: none;">
            <h2 style="font-size: 1.2rem; color: var(--muted); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Club PDF Applications Desk</h2>
            <div class="admin-table-container" style="margin-bottom: 60px;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Club Name</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Rejection Note</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($result_apps && mysqli_num_rows($result_apps) > 0): ?>
                            <?php while($row = mysqli_fetch_assoc($result_apps)): ?>
                            <?php
                                // Check if current admin can approve THIS specific application type
                                $canApproveThis = false;
                                foreach($approvalPermissions as $key => $roles) {
                                    if (stripos($row['subject'], $key) !== false) {
                                        $canApproveThis = in_array($adminRole, $roles) || $adminRole === 'System Admin';
                                        break;
                                    }
                                }
                            ?>
                            <tr>
                                <td style="color: var(--muted);">#<?php echo $row['id']; ?></td>
                                <td style="font-weight: 600;"><?php echo htmlspecialchars($row['club_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                <td>
                                    <?php
                                        if($row['status'] == 'Pending') echo "<span class='badge badge-pending'>⏳ Pending</span>";
                                        else if($row['status'] == 'Approved') echo "<span class='badge badge-approved'>✅ Approved</span>";
                                        else echo "<span class='badge badge-rejected'>❌ Rejected</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($row['status'] === 'Rejected' && !empty($row['rejection_note'] ?? '')) {
                                        echo htmlspecialchars($row['rejection_note']);
                                    } else {
                                        echo '<span style="color:var(--muted);">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo htmlspecialchars($row['letter_content'] ?? '#'); ?>" target="_blank" class="btn-sm btn-view">📄 View</a>
                                    
                                    <?php if($row['status'] == 'Pending'): ?>
                                        <?php if($canApproveThis): ?>
                                            <form method="POST" action="" class="inline-form">
                                                <input type="hidden" name="app_id" value="<?php echo $row['id']; ?>">
                                                <button type="submit" name="action" value="Approved" class="btn-sm btn-approve" onclick="return confirm('Approve this application? Green seal will be punched on the PDF and sent to the club.');">✔</button>
                                            </form>
                                            <button type="button" onclick="rejectApplication(<?php echo $row['id']; ?>)" class="btn-sm btn-reject">✖</button>
                                        <?php else: ?>
                                            <span style="color:#94a3b8; font-size:0.8rem; font-style:italic;">View Only</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 40px; color: var(--muted);">
                                    No applications submitted yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h2 class="modal-title" id="modal-title">Action</h2>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        // Set Current Date
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').innerText = new Date().toLocaleDateString('en-US', options);

        // Chart.js Quick Analysis Initialization
        const ctx = document.getElementById('quickAnalyticsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(!empty($clubNames) ? $clubNames : ['No Data Found']); ?>,
                datasets: [{
                    label: 'Total Events Hosted',
                    data: <?php echo json_encode(!empty($clubEvents) ? $clubEvents : [0]); ?>,
                    backgroundColor: 'rgba(255, 71, 126, 0.8)',
                    borderColor: '#ff477e',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, color: '#94a3b8' },
                        grid: { color: 'rgba(255, 255, 255, 0.05)' }
                    },
                    x: {
                        ticks: { color: '#94a3b8' },
                        grid: { display: false }
                    }
                }
            }
        });

       
        const currentAdminRole = "<?php echo addslashes($adminRole); ?>";
        const dashboardActions = [
            {
                title: 'Club Approvals',
                icon: '✅',
                desc: 'Review and approve new club registration requests.',
                allowedRoles: ['Student Affairs', 'System Admin'],
                link: 'approve_clubs.php'
            },
            {
                title: 'Room Bookings',
                icon: '🏢',
                desc: 'Review, approve, or reject club room requests across campus.',
                allowedRoles: ['Student Affairs', 'Registrar', 'System Admin'],
                link: 'booked_room.php'
            },
            {
                title: 'Security Clearance',
                icon: '🛡️',
                desc: 'Review event details and issue security clearance protocols.',
                allowedRoles: ['Security', 'System Admin'],
                link: 'security_clearance.php'
            },
            {
                title: 'PDF Applications Desk',
                icon: '📄',
                desc: 'View, approve, or reject official club letters and PDFs.',
                allowedRoles: ['Student Affairs', 'Registrar', 'Security', 'System Admin'],
                action: 'toggleApplicationsDesk'
            },
            {
                title: 'Club Analysis',
                icon: '📊',
                desc: 'View comprehensive analytics and performance metrics for all clubs.',
                allowedRoles: ['Student Affairs', 'Registrar', 'System Admin'],
                link: 'club_analysis.php'
            },
             {
                title: 'Club Creation',
                icon: '✅',
                desc: 'Create a new club.',
                allowedRoles: ['Student Affairs', 'Registrar', 'System Admin'],
                link: 'create_club.php'
            }
           
        ];

        const grid = document.getElementById('dashboard-grid');

        // Render Dashboard Cards Dynamically
        dashboardActions.forEach(action => {
            const isAllowed = action.allowedRoles.includes(currentAdminRole) || currentAdminRole === 'System Admin';
            const card = document.createElement('div');
            card.className = `action-card ${isAllowed ? '' : 'restricted'}`;
            
            card.innerHTML = `
                <div class="card-icon">${action.icon}</div>
                <h3>${action.title}</h3>
                <p>${action.desc}</p>
                ${!isAllowed ? `
                <div class="lock-overlay">
                    <span>🔒</span>
                    <p>Access Restricted to:<br>${action.allowedRoles.join(', ')}</p>
                </div>` : ''}
            `;

            // Adding Click Action for the dynamically created cards
            if (isAllowed) {
                card.onclick = () => {
                    if (action.link) {
                        window.location.href = action.link;
                    } else if (action.action === 'toggleApplicationsDesk') {
                        const desk = document.getElementById('applications-desk');
                        desk.style.display = desk.style.display === 'none' || desk.style.display === '' ? 'block' : 'none';
                        if (desk.style.display === 'block') {
                            desk.scrollIntoView({ behavior: 'smooth' });
                        }
                    }
                };
            }
            grid.appendChild(card);
        });

        // Application Rejection Modal Function
        function rejectApplication(appId) {
            document.getElementById('modal-title').innerText = 'Reject Application';
            document.getElementById('modal-body').innerHTML = `
                <form method="POST" action="">
                    <input type="hidden" name="app_id" value="${appId}">
                    <textarea name="rejection_note" rows="4" style="width:100%; box-sizing:border-box; border-radius:12px; padding:15px; background:var(--bg); color:var(--text); border:1px solid var(--border); margin-bottom:20px; font-family:inherit;" placeholder="Reason for rejection (will be stamped on PDF)..." required></textarea>
                    <button type="submit" name="action" value="Rejected" style="background:#ef4444; color:white; border:none; padding:12px 25px; border-radius:12px; font-weight:bold; cursor:pointer; width:100%; transition: 0.3s;">Confirm Rejection</button>
                </form>
            `;
            document.getElementById('modal-overlay').style.display = 'flex';
        }

        // Close Modal Function
        function closeModal() {
            document.getElementById('modal-overlay').style.display = 'none';
        }
    </script>
</body>
</html>