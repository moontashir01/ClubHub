<?php
session_start();
include 'connection.php';

if(!isset($_SESSION['Club_id'])){
    $_SESSION['Club_id'] = '1'; 
}

$club_id = $_SESSION['Club_id'];
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
            $insert = $con->prepare("INSERT INTO space_bookings (club_id, booking_date, slot, status) VALUES (?, ?, ?, ?)");
            $insert->bind_param("isis", $club_id, $booking_date, $slot_id, $status);
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

        /* --- 3D BUTTONS --- */
        .hotspot-container { width: 220px; height: 384px; perspective: 800px; }
        .tall-button {
            width: 100%; height: 100%;
            background: rgba(0, 255, 204, 0.1); border: 2px solid #00ffcc;
            cursor: pointer; display: flex; flex-direction: column; justify-content: center; align-items: center;
            color: white; font-weight: bold; transition: all 0.3s ease;
            backface-visibility: hidden; text-align: center; border-radius: 8px; padding: 10px; box-sizing: border-box;
        }
        .tall-button span { font-size: 11px; font-weight: normal; margin-top: 5px; display: block; opacity: 0.9; }
        .club-label { color: #ff8800; font-style: italic; font-size: 12px !important; margin-top: 8px !important; }

        .left-rotation { transform: rotateY(0deg); }
        .right-rotation { transform: rotateY(-45deg); }

        /* --- STATUS COLORS (FIXED TEXT COLORS) --- */
        .is-my-pending { 
            background: rgba(255, 255, 0, 0.4) !important; 
            border-color: #ffff00 !important; 
            color: #fff !important; /* Changed from black to white */
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

        .tall-button:hover:not(.is-confirmed) { 
            transform: scale(1.05) translateZ(30px); 
            box-shadow: 0 0 20px rgba(0, 255, 204, 0.4); 
        }
    </style>
</head>
<body>

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
            { "pitch": 0, "yaw": 90, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 1", "id": 1, "className": "left-rotation" } },
            { "pitch": 0, "yaw": 12, "cssClass": "hotspot-container", "createTooltipFunc": hotspotWrapper, "createTooltipArgs": { "label": "Slot 2", "id": 2, "className": "right-rotation" } }
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