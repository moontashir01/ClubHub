<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Volunteer Form | ClubHub</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="container">
    <h1>ClubHub Volunteer Registration</h1>
    <p class="subtitle">Sign up to volunteer for campus events</p>

    <form action="volunteer.php" method="POST">

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" name="name" required>
        </div>

        <div class="form-group">
            <label>Student ID</label>
            <input type="text" name="student_id" required>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>

        <div class="form-group">
            <label>Phone Number</label>
            <input type="tel" name="phone" required>
        </div>

        <div class="form-group">
            <label>Select Event</label>
            <select name="event">
                <option value="">-- Choose Event --</option>
                <option>Tech Fest</option>
                <option>Cultural Night</option>
                <option>Science Fair</option>
                <option>Orientation Program</option>
            </select>
        </div>

        <label>Skills</label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="skills[]" value="Management"> Management</label>
            <label><input type="checkbox" name="skills[]" value="Photography"> Photography</label>
            <label><input type="checkbox" name="skills[]" value="Technical"> Technical</label>
            <label><input type="checkbox" name="skills[]" value="Design"> Design</label>
        </div>
        <br><br>
        <div class="form-group">
            <label>Why do you want to volunteer?</label>
            <textarea name="reason" rows="4"></textarea>
        </div>

        <button type="submit">Register as Volunteer</button>

    </form>
</div>

</body>
</html>