<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'connection.php';


$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}

// এখানে WHERE কন্ডিশন সরিয়ে দেওয়া হয়েছে যাতে সব স্ট্যাটাসের ডাটা আসে
$sql = "SELECT * FROM room_bookings ORDER BY booking_date DESC, start_time ASC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Bookings Schedule</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        body {
            background-color: #0b1120;
            color: #f8fafc;
            padding: 30px 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .top-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 40px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            color: #cbd5e1;
            padding: 10px 22px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.95rem;
            font-weight: 500;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: linear-gradient(to right, #38bdf8, #818cf8);
            color: white;
            border-color: transparent;
            box-shadow: 0 4px 15px rgba(56, 189, 248, 0.4);
            transform: translateX(-5px);
        }

        .live-status {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #94a3b8;
            background: rgba(0, 0, 0, 0.2);
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid #1e293b;
        }
        .pulse-dot {
            width: 10px;
            height: 10px;
            background-color: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7);
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(34, 197, 94, 0); }
            100% { box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }
        .header h1 {
            font-size: 2.8rem;
            font-weight: 700;
            background: linear-gradient(to right, #38bdf8, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }
        .header p { color: #94a3b8; font-size: 1rem; }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 25px;
        }

        .card {
            background: #1e293b;
            border-radius: 16px;
            padding: 25px;
            border: 1px solid #334155;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            opacity: 0;
            transform: translateY(30px);
            animation: fadeUpIn 0.6s ease forwards;
        }
        
        @keyframes fadeUpIn {
            to { opacity: 1; transform: translateY(0); }
        }

        .card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        
        /* Top border color based on status */
        .card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 4px;
        }
        .card.approved::before { background: linear-gradient(to right, #22c55e, #10b981); }
        .card.pending::before { background: linear-gradient(to right, #f59e0b, #d97706); }
        .card.rejected::before { background: linear-gradient(to right, #ef4444, #b91c1c); }
        .card.approved:hover { border-color: #22c55e; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 0 15px rgba(34, 197, 94, 0.2); }
        .card.pending:hover { border-color: #f59e0b; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 0 15px rgba(245, 158, 11, 0.2); }
        .card.rejected:hover { border-color: #ef4444; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 0 15px rgba(239, 68, 68, 0.2); }

        .badge {
            position: absolute;
            top: 20px;
            right: 20px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        /* Badge colors based on status */
        .badge.approved { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .badge.pending { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge.rejected { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }

        .club-name {
            font-size: 1.4rem;
            font-weight: 600;
            color: #f8fafc;
            margin-bottom: 5px;
            padding-right: 90px; 
        }
        .room-name {
            font-size: 0.95rem;
            color: #38bdf8;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
        }

        .details-box {
            background: rgba(15, 23, 42, 0.6);
            padding: 15px;
            border-radius: 12px;
            margin-bottom: 15px;
            border: 1px solid #334155;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .detail-row:last-child { margin-bottom: 0; }
        .detail-label { color: #64748b; display: flex; align-items: center; gap: 6px; }
        .detail-value { color: #e2e8f0; font-weight: 500; text-align: right; max-width: 60%; }

        .empty-state {
            grid-column: 1 / -1;
            text-align: center;
            padding: 60px 20px;
            background: #1e293b;
            border-radius: 16px;
            border: 1px dashed #475569;
        }
        .empty-state i { font-size: 3rem; color: #64748b; margin-bottom: 15px; }
        .empty-state h3 { color: #f8fafc; margin-bottom: 10px; }
        .empty-state p { color: #94a3b8; }
    </style>
</head>
<body>

<div class="container">
    <div class="top-nav">
        <a href="room.php" class="back-btn">
            <i class="fa-solid fa-arrow-left"></i> Back
        </a>
        
        <div class="live-status">
            <div class="pulse-dot"></div> Live Schedule Updates
        </div>
    </div>

    <div class="header">
        <h1>Room Bookings Status</h1>
        <p>List of all room bookings and their current approval status.</p>
    </div>

    <div class="grid-container">
        <?php
        if ($result && $result->num_rows > 0) {
            $delay = 0; 
            while ($row = $result->fetch_assoc()) {
                $clubName = htmlspecialchars($row['club_name'] ?? $row['club'] ?? 'Unknown Club');
                $roomName = htmlspecialchars($row['room_name'] ?? $row['room_no'] ?? $row['room'] ?? $row['select_room'] ?? 'Unknown Room'); 
                $date = htmlspecialchars($row['booking_date'] ?? $row['date'] ?? '');
                $time = htmlspecialchars($row['start_time'] ?? $row['time'] ?? '');
                $duration = htmlspecialchars($row['duration'] ?? $row['time_duration'] ?? $row['booking_duration'] ?? 'N/A');
                $purpose = htmlspecialchars($row['purpose'] ?? 'No purpose specified');
                
                // Status Handling
                $status = ucfirst(strtolower($row['status'] ?? 'Pending'));
                $statusClass = strtolower($status);
                
                // Icon selection based on status
                $icon = "fa-clock"; // Default for pending
                if ($statusClass == 'approved') $icon = "fa-check";
                if ($statusClass == 'rejected') $icon = "fa-xmark";

                $formattedDate = ($date) ? date('l, d M Y', strtotime($date)) : 'N/A';
                $formattedTime = ($time) ? date('h:i A', strtotime($time)) : 'N/A';
        ?>
        
        <div class="card <?php echo $statusClass; ?>" style="animation-delay: <?php echo $delay; ?>s;">
            <div class="badge <?php echo $statusClass; ?>">
                <i class="fa-solid <?php echo $icon; ?>"></i> <?php echo $status; ?>
            </div>
            
            <div class="club-name"><?php echo $clubName; ?></div>
            <div class="room-name"><i class="fa-solid fa-door-open"></i> <?php echo $roomName; ?></div>
            
            <div class="details-box">
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-regular fa-calendar"></i> Date</div>
                    <div class="detail-value"><?php echo $formattedDate; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-regular fa-clock"></i> Time</div>
                    <div class="detail-value"><?php echo $formattedTime; ?></div>
                </div>
                <div class="detail-row">
                    <div class="detail-label"><i class="fa-solid fa-hourglass-half"></i> Duration</div>
                    <div class="detail-value"><?php echo $duration; ?></div>
                </div>
            </div>
            
            <div style="font-size: 0.85rem; padding-top: 5px;">
                <span style="color: #64748b;"><i class="fa-solid fa-bullseye"></i> Purpose:</span> 
                <span style="color: #cbd5e1; font-weight: 500;"><?php echo $purpose; ?></span>
            </div>
        </div>

        <?php 
                $delay += 0.1; 
            }
        } else {
        ?>
        
        <div class="empty-state">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h3>No Bookings Found</h3>
            <p>There are currently no room booking requests available.</p>
        </div>
        
        <?php } ?>
    </div>
</div>

</body>
</html>