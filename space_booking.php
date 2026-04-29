<?php
session_start();
include 'connection.php';

if (!isset($_SESSION['club_id'])) {
    header("Location: homepage.php");
    exit(); 
}

$club_id = $_SESSION['club_id'];
$today = date('Y-m-d');

// --- DATE LOGIC ---
$booking_date = isset($_GET['date']) ? $_GET['date'] : $today;
if ($booking_date < $today) { $booking_date = $today; }

// --- DATABASE LOGIC: TOGGLE PER CLUB & DATE ---
if(isset($_GET['slot'])){
    $slot_id = intval($_GET['slot']);
    if ($booking_date >= $today) {
        $check_exists = $con->prepare("SELECT status FROM space_bookings WHERE club_id = ? AND booking_date = ? AND slot = ?");
        $check_exists->bind_param("isi", $club_id, $booking_date, $slot_id);
        $check_exists->execute();
        $result = $check_exists->get_result();
        
        if($row = $result->fetch_assoc()){
            if($row['status'] == 'Pending'){
                $delete = $con->prepare("DELETE FROM space_bookings WHERE club_id = ? AND booking_date = ? AND slot = ?");
                $delete->bind_param("isi", $club_id, $booking_date, $slot_id);
                $delete->execute();
            }
        } else {
            $status = 'Pending';
            $applied_time = date('Y-m-d H:i:s'); // <-- ADDED: Capture exact current time
            $insert = $con->prepare("INSERT INTO space_bookings (club_id, booking_date, slot, status, created_at) VALUES (?, ?, ?, ?, ?)"); // <-- ADDED: created_at column
            $insert->bind_param("isiss", $club_id, $booking_date, $slot_id, $status, $applied_time); // <-- ADDED: Bind applied_time parameter
            $insert->execute();
        }
    }
    header("Location: " . $_SERVER['PHP_SELF'] . "?date=" . $booking_date);
    exit();
}

// --- FETCH SLOTS WITH JOIN TO GET CLUB NAMES ---
$my_pending = [];
$others_pending = []; 
$confirmed_slots = [];

$query = "SELECT sb.slot, sb.status, sb.club_id, c.club_name 
          FROM space_bookings sb 
          JOIN clubs c ON sb.club_id = c.club_id 
          WHERE sb.booking_date = ?";

$fetch = $con->prepare($query);
$fetch->bind_param("s", $booking_date);
$fetch->execute();
$res = $fetch->get_result();

while($row = $res->fetch_assoc()){
    if($row['status'] == 'Confirmed') {
        $confirmed_slots[$row['slot']] = $row['club_name'];
    } elseif ($row['status'] == 'Pending') {
        if ($row['club_id'] == $club_id) {
            $my_pending[] = $row['slot'];
        } else {
            $others_pending[$row['slot']] = $row['club_name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Hub - Collaborative Booking</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #000; font-family: 'Segoe UI', sans-serif; }
        #panorama { width: 100%; height: 100vh; }
        
        /* --- BACK BUTTON --- */
        .back-container {
            position: absolute; top: 25px; left: 25px; z-index: 1000;
        }
        .back-btn {
            background: #111; padding: 12px 20px; border-radius: 12px;
            border: 2px solid #ff4d8d; box-shadow: 0 0 15px rgba(255, 77, 141, 0.3);
            color: #ff4d8d; font-size: 13px; font-weight: bold; text-decoration: none;
            text-transform: uppercase; transition: all 0.3s ease; display: inline-block;
        }
        .back-btn:hover { background: #ff4d8d; color: #fff; box-shadow: 0 0 20px rgba(255, 77, 141, 0.6); }

        /* --- PINK DATE PICKER --- */
        .date-container {
            position: absolute; top: 25px; right: 25px; z-index: 1000;
            background: #111; padding: 12px 20px; border-radius: 12px;
            border: 2px solid #ff4d8d; box-shadow: 0 0 15px rgba(255, 77, 141, 0.3);
            display: flex; align-items: center; gap: 15px;
        }
        .date-container label { color: #ff4d8d; font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .date-input { background: #000; border: 1px solid #444; color: #fff; padding: 8px 12px; border-radius: 6px; outline: none; cursor: pointer; }
        .date-input::-webkit-calendar-picker-indicator { filter: invert(47%) sepia(88%) saturate(2345%) hue-rotate(313deg); }

        /* --- 3D BUTTONS CONTAINER --- */
        .hotspot-container { width: 220px; height: 384px; perspective: 800px; }
        
        .tall-button {
            width: 100%; height: 100%;
            background: rgba(0, 255, 204, 0.1); border: 2px solid #00ffcc;
            cursor: pointer; display: flex; flex-direction: column; justify-content: center; align-items: center;
            color: white; font-weight: bold; transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
            backface-visibility: hidden; text-align: center; border-radius: 8px; padding: 10px; box-sizing: border-box;
        }
        .tall-button span { font-size: 11px; font-weight: normal; margin-top: 5px; display: block; opacity: 0.9; }
        .club-label { color: #ff8800; font-style: italic; font-size: 12px !important; margin-top: 8px !important; }

        /* --- SPECIFIC BUTTON ANGLES --- */
        .angle-1 { transform: rotateY(0deg); }
        .angle-2 { transform: rotateY(-25deg); }
        .angle-3 { transform: rotateY(0deg); }
        .angle-4 { transform: rotateY(15deg); }
        .angle-5 { transform: rotateY(-15deg); }
        .angle-6 { transform: rotateY(-5deg); }

        .tall-button:hover:not(.is-confirmed) { 
            transform: rotateY(0deg) scale(1.05) translateZ(30px); 
            box-shadow: 0 0 20px rgba(0, 255, 204, 0.4); 
        }

        .is-my-pending { 
            background: rgba(255, 255, 0, 0.4) !important; 
            border-color: #ffff00 !important; 
            color: #fff !important; 
        }
        .is-others-pending { 
            background: rgba(255, 136, 0, 0.3) !important; 
            border-color: #ff8800 !important; 
            color: #fff !important;
        }
        .is-confirmed { 
            background: rgba(255, 0, 0, 0.5) !important; 
            border-color: #ff0000 !important; 
            color: #fff !important;
            cursor: not-allowed; 
        }
    </style>
</head>
<body>

<div class="back-container">
    <a href="club_dashboard.php" class="back-btn">← Back to Dashboard</a>
</div>

<div class="date-container">
    <label>Date</label>
    <input type="date" id="datePicker" class="date-input" min="<?php echo $today; ?>" value="<?php echo $booking_date; ?>" onchange="window.location.href='?date=' + this.value">
</div>

<div id="panorama"></div>

<script>
    const myPending = <?php echo json_encode($my_pending); ?>;
    const othersPending = <?php echo json_encode($others_pending); ?>;
    const confirmedList = <?php echo json_encode($confirmed_slots); ?>;
    const currentSelectedDate = "<?php echo $booking_date; ?>";

    const viewer = pannellum.viewer('panorama', {
        "type": "equirectangular",
        "panorama": "images/IMG_8848.jpeg",
        "mouseZoom": false,
        "doubleClickZoom": false,
        "autoLoad": true, "showControls": false,
        "hfov": 110, "minPitch": 0, "maxPitch": 0,
        "hotSpots": [
            { "pitch": 0, "yaw": 50, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 1", "id": 1, "className": "angle-1" } },
            { "pitch": 0, "yaw": 12, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 2", "id": 2, "className": "angle-2" } },
            { "pitch": 0, "yaw": -130, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 3", "id": 3, "className": "angle-3" } },
            { "pitch": 0, "yaw": 100, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 4", "id": 4, "className": "angle-4" } },
            { "pitch": 0, "yaw": -170, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 5", "id": 5, "className": "angle-5" } },
            { "pitch": 0, "yaw": -90, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 6", "id": 6, "className": "angle-6" } }
        ]
    });

    function hotspotWrapper(hotSpotDiv, args) {
        hotSpotDiv.classList.add('hotspot-container');
        
        const btn = document.createElement('div');
        btn.classList.add('tall-button');
        btn.classList.add(args.className); 
        btn.innerHTML = "<strong>" + args.label + "</strong>";

        const sid = args.id;

        if (confirmedList[sid]) {
            btn.classList.add('is-confirmed');
            btn.innerHTML += "<span>BOOKED BY:</span><span class='club-label' style='color:#fff'>" + confirmedList[sid] + "</span>";
        } 
        else if (myPending.includes(sid) || myPending.includes(sid.toString())) {
            btn.classList.add('is-my-pending');
            btn.innerHTML += "<span>YOUR SELECTION<br>Pending</span><span>Click again to cancel</span>";
        } 
        else if (othersPending[sid]) {
            btn.classList.add('is-others-pending');
            btn.innerHTML += "<span>PENDING BY:</span><span class='club-label'>" + othersPending[sid] + "</span>";
        } 
        else {
            btn.innerHTML += "<span>AVAILABLE</span>";
        }

        btn.onclick = function() {
            if(!this.classList.contains('is-confirmed')) {
                window.location.href = "?slot=" + args.id + "&date=" + currentSelectedDate;
            }
        };
        hotSpotDiv.appendChild(btn);
    }
</script>

</body>
</html>