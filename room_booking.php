<?php
session_start(); 
include 'connection.php'; 

$message = ""; 

if(!isset($_SESSION['club_name'])) {
    $_SESSION['club_name'] = "NSU YES"; 
}
$logged_in_club = $_SESSION['club_name'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_room'])) {
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $booking_date = mysqli_real_escape_string($con, $_POST['booking_date']);
    $start_time = mysqli_real_escape_string($con, $_POST['start_time']);
    

    $duration_minutes = isset($_POST['duration']) ? (int)$_POST['duration'] : 60; 
    
    $purpose = mysqli_real_escape_string($con, $_POST['purpose']);

   
    $end_time = date('H:i:s', strtotime("+$duration_minutes minutes", strtotime($start_time)));

   
    $sql = "INSERT INTO room_bookings (room_id, club_name, booking_date, start_time, end_time, purpose) 
            VALUES ('$room_id', '$logged_in_club', '$booking_date', '$start_time', '$end_time', '$purpose')";

    if (mysqli_query($con, $sql)) {
        $message = "<div style='color: #4ade80; background: rgba(74, 222, 128, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: bold;'>✔ Booking Request Sent Successfully!</div>";
    } else {
        $message = "<div style='color: #f87171; background: rgba(248, 113, 113, 0.1); padding: 10px; border-radius: 8px; margin-bottom: 15px; font-weight: bold;'>Error: " . mysqli_error($con) . "</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Booking - ClubHub</title>
    <style>
        body { background-color: #0b0f19; color: #ffffff; font-family: 'Segoe UI', Tahoma, sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
        .main-container { display: flex; max-width: 1000px; width: 100%; background-color: #0b0f19; gap: 50px; align-items: center; }
        .gallery-side { flex: 1.2; position: relative; min-height: 550px; width: 100%; }

        .pic1 { position: absolute; top: 20px; left: 0; width: 70%; height: 250px; background-image: url('https://images.unsplash.com/photo-1544531586-fde5298cdd40?q=80&w=800&auto=format&fit=crop'); background-size: cover; background-position: center; border-radius: 16px; z-index: 1; opacity: 0.8; box-shadow: 0 10px 20px rgba(0,0,0,0.5); }
        .pic2 { position: absolute; bottom: 20px; left: 10%; width: 75%; height: 280px; background-image: url('https://images.unsplash.com/photo-1522071820081-009f0129c71c?q=80&w=800&auto=format&fit=crop'); background-size: cover; background-position: center; border-radius: 16px; z-index: 2; opacity: 0.9; box-shadow: 0 15px 25px rgba(0,0,0,0.6); }
        
        .pic3 { 
            position: absolute; top: 25%; right: -10px; width: 80%; height: 320px; 
            background-image: url('images/NSU.jpg'); 
            background-size: cover; background-position: center; border-radius: 16px; z-index: 3; 
            box-shadow: -10px 15px 40px rgba(0,0,0,0.8); border: 2px solid rgba(255,255,255,0.05);
            transition: background-image 0.4s ease-in-out; 
        }

        .form-side { flex: 1; padding: 10px; z-index: 5; }
        h2 { font-size: 34px; margin-bottom: 5px; color: #ffffff; font-weight: bold; }
        p.subtitle { color: #888; font-size: 14px; margin-bottom: 20px; }
        
        .club-badge { display: inline-block; background: rgba(255, 71, 126, 0.15); color: #ff477e; padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: bold; margin-bottom: 25px; border: 1px solid rgba(255, 71, 126, 0.3); }

        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 11px; text-transform: uppercase; color: #a0aec0; margin-bottom: 6px; letter-spacing: 1px; font-weight: bold; }
        
        input, select { 
            width: 100%; padding: 12px 15px; border: 1px solid #2d3748; 
            border-radius: 8px; background-color: #1a202c; color: #ffffff; 
            font-size: 14px; outline: none; box-sizing: border-box; 
            transition: 0.3s;
        }
        input:focus, select:focus { border-color: #ff477e; box-shadow: 0 0 0 2px rgba(255, 71, 126, 0.2); }
        ::-webkit-calendar-picker-indicator { filter: invert(1); opacity: 0.6; cursor: pointer; }

        .btn { width: 100%; padding: 14px; background-color: #ff477e; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 15px; transition: 0.2s; box-shadow: 0 4px 15px rgba(255, 71, 126, 0.3); }
        .btn:hover { background-color: #e63e70; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(255, 71, 126, 0.4); }
        .back-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: #a0aec0; text-decoration: none; transition: 0.2s; }
        .back-link:hover { color: #ffffff; }
        .back-link span { color: #ff477e; font-weight: bold; }
        .flex-row { display: flex; gap: 15px; }

        @media (max-width: 768px) {
            .main-container { flex-direction: column; }
            .gallery-side { min-height: 400px; width: 100%; overflow: hidden; }
            .pic1, .pic2, .pic3 { position: relative; width: 100%; height: 200px; inset: 0; margin-bottom: -100px; box-shadow: none; border: none; }
            .pic3 { margin-bottom: 0; }
        }
    </style>
</head>
<body>
 <a href="room.php" class="back-btn">← Back</a>
 
<div class="main-container">
    <div class="gallery-side">
        <div class="pic1"></div>
        <div class="pic2"></div>
        <div class="pic3" id="mainRoomImage">
            <div id="capacityBadge" style="position: absolute; bottom: 15px; right: 15px; background: rgba(0,0,0,0.8); color: #4ade80; padding: 6px 12px; border-radius: 6px; font-size: 14px; font-weight: bold; display: none; border: 1px solid rgba(74, 222, 128, 0.3);"></div>
        </div> 
    </div>

    <div class="form-side">
        <h2>Book a Room</h2>
        <p class="subtitle">Secure a space for your next club event.</p>
        
        <div class="club-badge">Booking as: <?php echo htmlspecialchars($logged_in_club); ?></div>
        
        <?php echo $message; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Select Room</label>
                <select name="room_id" id="roomSelect" required>
                    <option value="">-- Choose a Space --</option>
                    <?php
                    $rooms = mysqli_query($con, "SELECT * FROM rooms");
                    if($rooms) {
                        while($row = mysqli_fetch_assoc($rooms)) {
                            $capacity = isset($row['capacity']) && !empty($row['capacity']) ? $row['capacity'] : 'N/A';
                            
                            $room_display = isset($row['room_name']) ? $row['room_name'] : (isset($row['room_number']) ? $row['room_number'] : 'Unknown Room');
                            
                            echo "<option value='".$row['room_id']."' data-capacity='".$capacity."'>".$room_display."</option>";
                        }
                    } else {
                        echo "<option value=''>Error loading rooms</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="booking_date" required min="<?php echo date('Y-m-d'); ?>">
            </div>

            <div class="flex-row">
                <div class="form-group" style="flex: 1;">
                    <label>Start Time</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>Duration</label>
                    <select name="duration" required>
                        <option value="" disabled selected>-- Select --</option>
                        <option value="30">30 Minutes</option>
                        <option value="60">1 Hour</option>
                        <option value="90">1.5 Hours</option>
                        <option value="120">2 Hours</option>
                        <option value="180">3 Hours</option>
                        <option value="240">4 Hours</option>
                        <option value="300">5 Hours</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Purpose</label>
                <input type="text" name="purpose" placeholder="e.g. Executive Meeting, Workshop" required>
            </div>

            <button type="submit" name="book_room" class="btn">Submit Request</button>
        </form>
    </div>
</div>

<script>
    document.getElementById('roomSelect').addEventListener('change', function() {
        var selectedOption = this.options[this.selectedIndex];
        var selectedText = selectedOption.text;
        var imageDiv = document.getElementById('mainRoomImage');
        var capacityBadge = document.getElementById('capacityBadge'); 
        var defaultImage = 'images/NSU.jpg';

        if (this.value !== "") {
            var formattedName = selectedText.replace(/\s+/g, '').toLowerCase();
            var newImagePath = 'images/' + formattedName + '.jpg';
            
            var img = new Image();
            img.onload = function() { imageDiv.style.backgroundImage = "url('" + newImagePath + "')"; };
            img.onerror = function() { imageDiv.style.backgroundImage = "url('" + defaultImage + "')"; };
            img.src = newImagePath;
            
            capacityBadge.innerHTML = "<span style='color: #fff;'>Capacity:</span> " + selectedOption.getAttribute('data-capacity');
            capacityBadge.style.display = "block";
        } else {
            imageDiv.style.backgroundImage = "url('" + defaultImage + "')";
            capacityBadge.style.display = "none"; 
        }
    });
</script>

</body>
</html>