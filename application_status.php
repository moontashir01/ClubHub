<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['Email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['Email'];


$query = mysqli_query($con, "
    SELECT c.club_name 
    FROM user u
    INNER JOIN students s ON u.email = s.student_email
    INNER JOIN club_members cm ON cm.student_id = s.student_id
    INNER JOIN clubs c ON cm.club_id = c.club_id
    WHERE u.email = '" . mysqli_real_escape_string($con, $email) . "' 
    LIMIT 1
");
$clubRow = mysqli_fetch_assoc($query);
$clubName = $clubRow ? $clubRow['club_name'] : "No Club Found";


if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    mysqli_query($con, "DELETE FROM vc_applications WHERE id = $id AND club_name = '" . mysqli_real_escape_string($con, $clubName) . "'");
    echo "<script>alert('Application deleted successfully!'); window.location.href='application_status.php';</script>";
}


$apps = mysqli_query($con, "
    SELECT id, subject, status, created_at, letter_content, rejection_note 
    FROM vc_applications 
    WHERE club_name = '" . mysqli_real_escape_string($con, $clubName) . "' 
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status | ClubHub</title>
    <style>
        :root {
            --bg: #0b0f19;
            --card: #161b2b;
            --text: #f1f5f9;
            --muted: #94a3b8;
            --primary: #ff477e;
        }
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }
        nav {
            background: #0b0f19;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 15px rgba(0,0,0,0.4);
        }
        .logo { font-size: 1.6rem; font-weight: bold; }
        .main-content { padding: 40px 30px; }
        h1 { font-size: 2.2rem; margin: 0 0 30px 0; text-align: center; }
        
        .filter-tabs {
            display: flex;
            gap: 10px;
            justify-content: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .tab {
            padding: 10px 25px;
            border-radius: 50px;
            background: var(--card);
            color: var(--muted);
            cursor: pointer;
            transition: all 0.3s;
        }
        .tab.active {
            background: var(--primary);
            color: white;
            transform: scale(1.05);
        }

        .app-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 25px;
        }
        .app-card {
            background: var(--card);
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s backwards;
        }
        .app-card:hover {
            transform: translateY(-10px) scale(1.03);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .stamp {
            position: absolute;
            top: 20px;
            right: -25px;
            transform: rotate(35deg);
            font-size: 2.8rem;
            font-weight: 900;
            padding: 8px 60px;
            border: 8px solid;
            border-radius: 10px;
            opacity: 0.9;
            pointer-events: none;
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        .stamp.approved {
            color: #22c55e;
            border-color: #22c55e;
            background: rgba(34, 197, 94, 0.1);
        }
        .stamp.rejected {
            color: #ef4444;
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .status-badge {
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 1rem;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-print { background: #22c55e; color: white; }
        .btn-delete { background: #ef4444; color: white; }
        .btn-view { background: #3b82f6; color: white; }

        .rejection-box {
            background: rgba(239, 68, 68, 0.15);
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            color: #f87171;
        }
    </style>
</head>
<body>
    <nav>
        <div class="logo">ClubHub</div>
        <div style="font-size:1.1rem; color:var(--primary);"><?php echo htmlspecialchars($clubName); ?></div>
        <a href="application.php" style="color:#ff477e; text-decoration:none; font-weight:600;"> 🔙 Back </a>
    </nav>

    <div class="main-content">
        <h1>📋 My VC Application Status</h1>

        <div class="filter-tabs">
            <div class="tab active" onclick="filterStatus('all')">All</div>
            <div class="tab" onclick="filterStatus('Pending')">Pending</div>
            <div class="tab" onclick="filterStatus('Approved')">Approved</div>
            <div class="tab" onclick="filterStatus('Rejected')">Rejected</div>
        </div>

        <div class="app-grid" id="appGrid">
            <?php while($app = mysqli_fetch_assoc($apps)): ?>
                <div class="app-card" data-status="<?php echo strtolower($app['status']); ?>">
                    <div style="display:flex; justify-content:space-between; align-items:start;">
                        <div>
                            <h3>#<?php echo $app['id']; ?> - <?php echo htmlspecialchars($app['subject']); ?></h3>
                            <p style="color:var(--muted); margin:5px 0;">
                                <?php echo date('d M Y • h:i A', strtotime($app['created_at'])); ?>
                            </p>
                        </div>
                        <?php if($app['status'] === 'Approved'): ?>
                            <div class="stamp approved">APPROVED</div>
                        <?php elseif($app['status'] === 'Rejected'): ?>
                            <div class="stamp rejected">REJECTED</div>
                        <?php endif; ?>
                    </div>

                    <span class="status-badge 
                        <?php echo $app['status'] === 'Approved' ? 'badge-approved' : ($app['status'] === 'Rejected' ? 'badge-rejected' : 'badge-pending'); ?>">
                        <?php echo strtoupper($app['status']); ?>
                    </span>

                    <?php if($app['status'] === 'Rejected' && !empty($app['rejection_note'])): ?>
                        <div class="rejection-box">
                            <strong>Reason:</strong><br>
                            <?php echo nl2br(htmlspecialchars($app['rejection_note'])); ?>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:25px; display:flex; gap:10px; flex-wrap:wrap;">
                        <?php if(!empty($app['letter_content'])): ?>
                            <a href="<?php echo htmlspecialchars($app['letter_content']); ?>" target="_blank" class="btn btn-view">📄 View PDF</a>
                            <button type="button" onclick="printPDF('<?php echo htmlspecialchars($app['letter_content']); ?>')" class="btn btn-print">🖨️ Print PDF</button>
                        <?php endif; ?>
                        
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this application permanently?');">
                            <input type="hidden" name="delete_id" value="<?php echo $app['id']; ?>">
                            <button type="submit" class="btn btn-delete">🗑 Delete</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>

            <?php if(mysqli_num_rows($apps) == 0): ?>
                <p style="text-align:center; grid-column:1/-1; color:var(--muted); font-size:1.2rem; padding:60px;">
                    No applications yet. Start by submitting one from VC Approval Desk!
                </p>
            <?php endif; ?>
        </div>
    </div>

    <iframe id="print-iframe" style="display:none;"></iframe>

    <script>
        
        function printPDF(url) {
            const iframe = document.getElementById('print-iframe');
            
         
            iframe.src = url;
            
         
            iframe.onload = function() {
         
                setTimeout(function() {
                    iframe.contentWindow.focus();
                    iframe.contentWindow.print();
                }, 500); 
            };
        }

        function filterStatus(status) {
            const cards = document.querySelectorAll('.app-card');
            const tabs = document.querySelectorAll('.tab');
            
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');

            cards.forEach(card => {
                if (status === 'all') {
                    card.style.display = 'block';
                } else {
                    card.style.display = card.dataset.status === status.toLowerCase() ? 'block' : 'none';
                }
            });
        }

       
        document.querySelectorAll('.app-card').forEach((card, i) => {
            card.style.animationDelay = (i * 80) + 'ms';
        });
    </script>
</body>
</html>