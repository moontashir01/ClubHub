<?php
    include 'conn.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubHub Volunteer</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="background"></div>

<div class="container">

    <div class="card">
        <h1>ClubHub</h1>
        <p class="subtitle">Volunteer Registration</p>

        <form id="volunteerForm">

            <div class="input-group">
                <label>Full Name</label>
                <input type="text" id="name" placeholder="Enter your name" required>
            </div>

            <div class="input-group">
                <label>Student ID</label>
                <input type="text" id="studentId" placeholder="Enter your ID" required>
            </div>

            <div class="input-group">
                <label>Email</label>
                <input type="email" id="email" placeholder="example@email.com" required>
            </div>

            <div class="input-group">
                <label>Live Events</label>
                <select id="event">
                    <?php
                    $catagories = mysqli_query($conn,'Select * from events
                                                where event_availablity = 1');
                    while($c = mysqli_fetch_array($catagories)){
                    ?>
                    <option id="opt" value="<?php echo $c['event_id'] ?>"><?php echo $c['event_name'] ?></option>
                    <?php } ?>
                </select>
            </div>

            <div class="skills-section">
                <label>Skills</label>
                <div class="skills">
                    <button type="button" class="skill">Management</button>
                    <button type="button" class="skill">Photography</button>
                    <button type="button" class="skill">Technical</button>
                    <button type="button" class="skill">Design</button>
                </div>
            </div>

            <div class="input-group">
                <label>Availability</label>
                <input type="date" id="date">
            </div>

            <div class="input-group">
                <label>Why volunteer?</label>
                <textarea id="reason" placeholder="Tell us why..."></textarea>
            </div>

            <button type="submit" class="submit-btn">
                Register as Volunteer
            </button>

        </form>

    </div>

</div>

<div id="popup" class="popup">
    Registration Successful!
</div>

<script src="script.js"></script>
</body>
</html>
