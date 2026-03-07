<?php
include 'connection.php'; 
$message = ""; 

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['book_room'])) {
    $room_id = mysqli_real_escape_string($con, $_POST['room_id']);
    $club_name = mysqli_real_escape_string($con, $_POST['club_name']);
    $booking_date = mysqli_real_escape_string($con, $_POST['booking_date']);
    $start_time = mysqli_real_escape_string($con, $_POST['start_time']);
    $end_time = mysqli_real_escape_string($con, $_POST['end_time']);
    $purpose = mysqli_real_escape_string($con, $_POST['purpose']);

    $sql = "INSERT INTO room_bookings (room_id, club_name, booking_date, start_time, end_time, purpose) 
            VALUES ('$room_id', '$club_name', '$booking_date', '$start_time', '$end_time', '$purpose')";

    if (mysqli_query($con, $sql)) {
        $message = "<div style='color: #4ade80; text-align: left; margin-bottom: 15px; font-weight: bold;'>✔ Booking Request Sent!</div>";
    } else {
        $message = "<div style='color: #f87171; text-align: left; margin-bottom: 15px; font-weight: bold;'>Error: " . mysqli_error($con) . "</div>";
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
        p.subtitle { color: #888; font-size: 14px; margin-bottom: 25px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-size: 11px; text-transform: uppercase; color: #888; margin-bottom: 5px; letter-spacing: 1px; font-weight: bold; }
        input, select { width: 100%; padding: 12px; border: none; border-radius: 8px; background-color: #fdf5c9; color: #000; font-size: 14px; font-weight: bold; outline: none; box-sizing: border-box; }
        .btn { width: 100%; padding: 14px; background-color: #ff477e; color: white; border: none; border-radius: 8px; font-size: 16px; font-weight: bold; cursor: pointer; margin-top: 15px; transition: 0.2s; }
        .btn:hover { background-color: #e63e70; }
        .back-link { display: block; text-align: center; margin-top: 20px; font-size: 13px; color: #888; text-decoration: none; }
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

<div class="main-container">
    <div class="gallery-side">
        <div class="pic1"></div>
        <div class="pic2"></div>
        <div class="pic3" id="mainRoomImage"></div> 
    </div>

    <div class="form-side">
        <h2>Book a Room</h2>
        <p class="subtitle">Fill details to manage your club events.</p>
        
        <?php echo $message; ?>

        <form action="" method="POST">
            <div class="form-group">
                <label>Select Room</label>
                <select name="room_id" id="roomSelect" required>
                    <option value="">-- Choose --</option>
                    <?php
                    $rooms = mysqli_query($con, "SELECT * FROM rooms");
                    while($row = mysqli_fetch_assoc($rooms)) {
                        echo "<option value='".$row['room_id']."'>".$row['room_number']."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Club Name</label>
                <select name="club_name" required>
                    <option value="">-- Choose --</option>
                    <?php
                    $clubs = mysqli_query($con, "SELECT * FROM clubs");
                    while($row = mysqli_fetch_assoc($clubs)) {
                        echo "<option value='".$row['club_name']."'>".$row['club_name']."</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="form-group">
                <label>Date</label>
                <input type="date" name="booking_date" required>
            </div>

            <div class="flex-row">
                <div class="form-group" style="flex: 1;">
                    <label>Start Time</label>
                    <input type="time" name="start_time" required>
                </div>
                <div class="form-group" style="flex: 1;">
                    <label>End Time</label>
                    <input type="time" name="end_time" required>
                </div>
            </div>

            <div class="form-group">
                <label>Purpose</label>
                <input type="text" name="purpose" placeholder="e.g. Executive Meeting" required>
            </div>

            <button type="submit" name="book_room" class="btn">Submit Request</button>
        </form>

        <a href="User_dashboard.php" class="back-link">Need to go back? <span>Dashboard</span></a>
    </div>
</div>

<script>
    document.getElementById('roomSelect').addEventListener('change', function() {
        var selectedText = this.options[this.selectedIndex].text;
        var imageDiv = document.getElementById('mainRoomImage');
        var defaultImage = 'images/NSUaudi.jpg';

        if (this.value !== "") {
            
            var formattedName = selectedText.replace(/\s+/g, '').toLowerCase();
            
            
            var newImagePath = 'images/' + formattedName + '.jpg';
            
            imageDiv.style.backgroundImage = "url('" + newImagePath + "')";
        } else {
            
            imageDiv.style.backgroundImage = "url('" + defaultImage + "')";
        }
    });
</script>

</body>
</html>