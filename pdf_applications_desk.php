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

// ==========================================
// Approve/Reject Logic (with rejection note + PDF stamp)
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['app_id'])) {
    $app_id = intval($_POST['app_id']);
    $action = mysqli_real_escape_string($con, $_POST['action']);
    $rejection_note = isset($_POST['rejection_note']) ? mysqli_real_escape_string($con, $_POST['rejection_note']) : '';

    $file_query = @mysqli_query($con, "SELECT letter_content FROM vc_applications WHERE id=$app_id");
    $file_row = mysqli_fetch_assoc($file_query);
    $original_file = $file_row['letter_content'];
    $stamped_path = $original_file; 

    if (!empty($original_file) && file_exists($original_file)) {
        try {
            $pdf = new \setasign\Fpdi\Fpdi();
            $pageCount = $pdf->setSourceFile($original_file);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                
                if ($pageNo == 1) {
                    $pdf->SetFont('Arial', 'B', 24);
                    if ($action === 'Approved') {
                        $pdf->SetTextColor(16, 185, 129); // Green
                        $pdf->SetXY(20, 20); 
                        $pdf->Cell(0, 10, 'APPROVED', 0, 1, 'R');
                    } else if ($action === 'Rejected') {
                        $pdf->SetTextColor(239, 68, 68); // Red
                        $pdf->SetXY(20, 20);
                        $pdf->Cell(0, 10, 'REJECTED', 0, 1, 'R');
                        
                        if ($rejection_note !== '') {
                            $pdf->SetFont('Arial', 'I', 12);
                            $pdf->SetXY(20, 32);
                            $pdf->SetLeftMargin(100); 
                            $pdf->MultiCell(0, 6, "Note: " . $rejection_note, 0, 'R');
                            $pdf->SetLeftMargin(10); 
                        }
                    }
                }
            }
            
            $path_parts = pathinfo($original_file);
            $stamped_path = $path_parts['dirname'] . '/' . $path_parts['filename'] . '_stamped_' . time() . '.' . $path_parts['extension'];
            $pdf->Output('F', $stamped_path);

        } catch (Exception $e) {
            error_log("PDF Stamping Error: " . $e->getMessage());
        }
    }

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

// Permission mapping
$approvalPermissions = [
    'Room Booking Permission' => ['Registrar', 'Student Affairs', 'System Admin'],
    'Budget Approval' => ['Student Affairs', 'System Admin'],
    'Permission to Host External Guests' => ['Security', 'System Admin'],
    'Guest Permission' => ['Security', 'System Admin'],
    'General Event Permission' => ['Student Affairs', 'Registrar', 'System Admin'],
    'General Club Activity Permission' => ['Student Affairs', 'Registrar', 'System Admin'],
];

$query_apps = "SELECT * FROM vc_applications ORDER BY id DESC";
$result_apps = @mysqli_query($con, $query_apps);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Applications Desk | ClubHub</title>
    <style>
        :root {
            --bg: #090a0f; --surface: #13141f; --surface-hover: #1c1d2b;
            --primary: #ff477e; --text: #f1f5f9; --muted: #94a3b8; --border: rgba(255, 255, 255, 0.08);
        }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); color: var(--text); padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 1px solid var(--border); padding-bottom: 20px; }
        .back-btn { background: var(--surface); color: var(--text); padding: 10px 20px; text-decoration: none; border-radius: 8px; border: 1px solid var(--border); transition: 0.3s; }
        .back-btn:hover { border-color: var(--primary); color: var(--primary); }
        .admin-table-container { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; overflow: hidden; }
        .admin-table { width: 100%; border-collapse: collapse; text-align: left; }
        .admin-table th, .admin-table td { padding: 18px 20px; border-bottom: 1px solid var(--border); }
        .admin-table th { background: rgba(0,0,0,0.2); color: var(--muted); font-size: 0.85rem; text-transform: uppercase; }
        .badge { padding: 6px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; display: inline-block;}
        .badge-pending { background: rgba(251, 191, 36, 0.15); color: #fbbf24; border: 1px solid rgba(251, 191, 36, 0.3); }
        .badge-approved { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .badge-rejected { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .btn-sm { padding: 8px 14px; border-radius: 8px; border: none; cursor: pointer; font-weight: 600; font-size: 0.8rem; transition: 0.3s; margin-right: 5px; display: inline-flex; align-items: center; text-decoration: none; }
        .btn-view { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
        .btn-approve { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
        .btn-reject { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
        .inline-form { display: inline-block; margin: 0; }
    </style>
</head>
<body>

    <div class="header">
        <h1 style="margin: 0; font-size: 2rem;">📄 PDF Applications Desk</h1>
        <a href="admin_dashboard.php" class="back-btn">⬅ Back to Dashboard</a>
    </div>

    <div class="admin-table-container">
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
                            if ($row['status'] === 'Rejected' && !empty($row['rejection_note'])) {
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
                                        <button type="submit" name="action" value="Approved" class="btn-sm btn-approve" onclick="return confirm('Approve this application? Green seal will be punched on the PDF.');">✔</button>
                                    </form>
                                    <button type="button" onclick="rejectApp(<?php echo $row['id']; ?>)" class="btn-sm btn-reject">✖</button>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.8rem; font-style:italic;">View Only</span>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align: center; padding: 40px; color: var(--muted);">No applications submitted yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script>
        function rejectApp(id) {
            let reason = prompt("Please enter a reason for rejection:");
            if (reason !== null && reason.trim() !== "") {
                let form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="app_id" value="${id}">
                    <input type="hidden" name="action" value="Rejected">
                    <input type="hidden" name="rejection_note" value="${reason}">
                `;
                document.body.appendChild(form);
                form.submit();
            } else if (reason !== null) {
                alert("Rejection note cannot be empty!");
            }
        }
    </script>
</body>
</html>