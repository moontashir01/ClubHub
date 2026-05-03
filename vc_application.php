<?php
session_start();
include 'connection.php'; 

$club_name = "No Club Found";
$president_name = "";
$club_id = "";

if(isset($_SESSION['Email'])) {
    $email = $_SESSION['Email'];
    
    $query = mysqli_query($con,"
        SELECT clubs.club_id, clubs.club_name, students.full_name
        FROM `user`
        INNER JOIN `students` ON `user`.email = students.student_email
        INNER JOIN `club_members` ON club_members.student_id = students.student_id
        INNER JOIN `clubs` ON club_members.club_id = clubs.club_id
        WHERE `user`.email = '$email'
    ");

    if($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $club_id = $row['club_id'];
        $club_name = $row['club_name'];
        $president_name = $row['full_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title> Application | Professional Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            margin: 0; 
            font-family: 'Poppins', sans-serif; 
            background: linear-gradient(rgba(11, 15, 25, 0.85), rgba(11, 15, 25, 0.85)), 
                        url('https://images.unsplash.com/photo-1541339907198-e08756dedf3f?q=80&w=1920&auto=format&fit=crop') center/cover no-repeat fixed; 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            color: #f1f5f9; 
            padding: 20px;
            box-sizing: border-box;
        }

        .main-wrapper {
            width: 100%;
            max-width: 800px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .tooltip-container {
            --background-light: #ff5555;
            --background-dark: #000000;
            --text-color-light: #ffffff;
            --text-color-dark: #ffffff;
            --bubble-size: 12px;
            --glow-color: rgba(255, 255, 255, 0.5);

            position: relative;
            background: var(--background-light);
            cursor: pointer;
            transition: all 0.2s;
            font-size: 15px;
            padding: 0.7em 1.8em;
            color: var(--text-color-light);
            border-radius: 8px;
            text-decoration: none;
            display: inline-flex;
            align-self: flex-start;
            font-weight: 600;
        }

        .form-container { 
            background: rgba(30, 41, 59, 0.85); 
            backdrop-filter: blur(12px); 
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px; 
            padding: 40px 50px; 
            width: 100%; 
            box-sizing: border-box;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4); 
        }

        .header-text { 
            margin-bottom: 35px; 
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 20px;
        }
        .header-text h2 { 
            margin: 0 0 5px 0; 
            font-size: 1.8rem; 
            font-weight: 600; 
            color: #ffffff;
        }
        .header-text p {
            margin: 0;
            color: #94a3b8;
            font-size: 0.95rem;
        }

        /* Professional Segmented Control for App Type */
        .app-type-selector {
            display: flex;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            padding: 6px;
            margin-bottom: 30px;
            position: relative;
        }

        .app-type-selector label {
            flex: 1;
            text-align: center;
            padding: 12px 15px;
            color: #94a3b8;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.95rem;
            z-index: 1;
        }

        .app-type-selector input[type="radio"] {
            display: none;
        }

        .app-type-selector input[type="radio"]:checked + label {
            background: #ff477e;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(255, 71, 126, 0.4);
        }
        
        .input-group { 
            position: relative; 
            margin-bottom: 25px; 
        }
        
        .input-group input, .input-group textarea, .input-group select { 
            width: 100%; 
            padding: 16px 15px; 
            font-size: 1rem; 
            color: #f8fafc; 
            background: rgba(15, 23, 42, 0.7); 
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            outline: none; 
            transition: border-color 0.2s; 
            font-family: 'Poppins', sans-serif; 
            box-sizing: border-box; 
        }

        ::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
        }

        .input-group textarea {
            resize: vertical;
            min-height: 150px;
        }

        .input-group input:focus, .input-group textarea:focus, .input-group select:focus {
            border-color: #ff477e;
            background: rgba(15, 23, 42, 0.9);
        }
        
        .input-group label { 
            position: absolute; 
            top: 17px; 
            left: 15px; 
            font-size: 1rem; 
            color: #94a3b8; 
            pointer-events: none; 
            transition: 0.2s ease; 
        }
        
        /* OVERLAP FIX: Using :not(:placeholder-shown) for bulletproof floating labels */
        .input-group input:focus ~ label, 
        .input-group input:not(:placeholder-shown) ~ label, 
        .input-group textarea:focus ~ label, 
        .input-group textarea:not(:placeholder-shown) ~ label,
        .input-group select:focus ~ label,
        .input-group select:valid ~ label,
        .input-group input[type="datetime-local"] ~ label { 
            top: -10px; 
            left: 10px; 
            font-size: 0.8rem; 
            color: #ff477e; 
            font-weight: 500; 
            background: #1e293b;
            padding: 0 5px;
            border-radius: 4px;
        }

        .row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
        }

        #eventExtraFields {
            display: none; /* Hidden by default */
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 600px) {
            .row { grid-template-columns: 1fr; }
            .form-container { padding: 30px 20px; }
        }
        
        .submit-btn { 
            width: 100%; 
            padding: 16px; 
            background: #ff477e; 
            border: none; 
            border-radius: 8px; 
            color: #fff; 
            font-size: 1.1rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: background 0.2s, transform 0.1s; 
            margin-top: 10px;
        }
        
        .submit-btn:hover { 
            background: #e11d48; 
        }
        .submit-btn:active {
            transform: scale(0.98);
        }
    </style>
</head>
<body>

    <div class="main-wrapper">
        <a href="application.php" class="tooltip-container">
            <span>&#8592; Back </span>
        </a>

        <div class="form-container">
            <div class="header-text">
                <h2> Approval Desk</h2>
                <p>Generate formal letters for official approvals</p>
            </div>

            <form action="preview_application.php" method="POST">
                
                <div class="app-type-selector">
                    <input type="radio" id="typeActivity" name="appType" value="Activity" checked onchange="toggleFields()">
                    <label for="typeActivity">General Event</label>
                    
                    <input type="radio" id="typeEvent" name="appType" value="Event" onchange="toggleFields()">
                    <label for="typeEvent">Major Event</label>
                </div>

                <input type="hidden" name="club_id" value="<?php echo htmlspecialchars($club_id); ?>">
                <input type="hidden" name="club_name_hidden" value="<?php echo htmlspecialchars($club_name); ?>">

                <div class="row">
                    <div class="input-group">
                        <input type="text" id="clubName" name="clubName" value="<?php echo htmlspecialchars($club_name); ?>" readonly placeholder=" ">
                        <label>Organization Name</label>
                    </div>
                    <div class="input-group">
                        <input type="text" name="presidentName" value="<?php echo htmlspecialchars($president_name); ?>" required placeholder=" ">
                        <label>Applicant Name (President)</label>
                    </div>
                </div>

                <div class="row">
                    <div class="input-group">
                        <input type="text" id="eventName" name="eventName" required placeholder=" ">
                        <label id="eventNameLabel">General Event Name</label>
                    </div>
                    <div class="input-group">
                        <input type="datetime-local" name="eventDate" id="eventDate" required>
                        <label>Date & Time</label>
                    </div>
                </div>

                <div class="row" id="eventExtraFields">
                    <div class="input-group">
                        <input type="number" step="0.1" name="eventDuration" id="eventDuration" placeholder=" ">
                        <label>Event Duration (Hours)</label>
                    </div>
                    <div class="input-group">
                        <select name="eventAvailability" id="eventAvailability">
                            <option value="" disabled selected></option>
                            <option value="1">Available (Visible to all)</option>
                            <option value="0">Not Available (Internal)</option>
                        </select>
                        <label>Event Availability</label>
                    </div>
                </div>

                <div class="input-group">
                    <select name="subject" id="subjectDropdown" required>
                        <option value="" disabled selected></option>
                        <option value="Room Booking Permission for Event">Room Booking Permission</option>
                        <option value="Budget Approval for Upcoming Event">Budget Approval</option>
                        <option value="Permission to Host External Guests">Guest Permission</option>
                        <option value="General Event Permission">General Event Permission</option>
                    </select>
                    <label>Select Subject</label>
                </div>

                <div class="input-group">
                    <textarea name="appBody" id="appBody" required placeholder=" "></textarea>
                    <label>Application Body (Editable)</label>
                </div>

                <button type="submit" class="submit-btn">
                    Generate Application Letter
                </button>
            </form>
        </div>
    </div>

    <script>
        function toggleFields() {
            const isEvent = document.getElementById('typeEvent').checked;
            const extraFields = document.getElementById('eventExtraFields');
            const durationInput = document.getElementById('eventDuration');
            const availabilityInput = document.getElementById('eventAvailability');
            const eventNameLabel = document.getElementById('eventNameLabel'); // Dynamic Label Select

            if (isEvent) {
                extraFields.style.display = 'grid'; 
                durationInput.setAttribute('required', 'true');
                availabilityInput.setAttribute('required', 'true');
                eventNameLabel.innerText = 'Major Event Name'; // Changed dynamically
            } else {
                extraFields.style.display = 'none';
                durationInput.removeAttribute('required');
                availabilityInput.removeAttribute('required');
                durationInput.value = '';
                availabilityInput.value = '';
                eventNameLabel.innerText = 'General Event Name'; // Changed dynamically
            }
            updateApplicationText();
        }

        function updateApplicationText() {
            const subject = document.getElementById('subjectDropdown').value;
            const clubName = document.getElementById('clubName').value;
            const eventDateValue = document.getElementById('eventDate').value;
            const eventNameValue = document.getElementById('eventName').value;
            const appTypeWord = document.getElementById('typeEvent').checked ? 'major event' : 'event';
            const bodyArea = document.getElementById('appBody');

            let displayDate = '[Date]';
            if(eventDateValue) {
                const dateObj = new Date(eventDateValue);
                displayDate = dateObj.toLocaleDateString('en-GB') + ' at ' + dateObj.toLocaleTimeString('en-GB', {hour: '2-digit', minute:'2-digit'});
            }
            
            const displayEvent = eventNameValue ? eventNameValue : `[Event Name]`;

            if(subject === 'Room Booking Permission for Event') {
                bodyArea.value = `We are planning to organize a seminar/workshop titled "${displayEvent}" on ${displayDate}. To ensure the smooth execution of this ${appTypeWord}, we kindly request your permission to book [Room Name/Number, e.g., AUDI 801] from [Start Time] to [End Time]. We assure you that all university regulations will be strictly maintained.`;
            } 
            else if(subject === 'Budget Approval for Upcoming Event') {
                bodyArea.value = `We are excited to inform you that ${clubName} is organizing "${displayEvent}" on ${displayDate}. We have prepared a detailed budget proposal for this ${appTypeWord} amounting to [Total Amount BDT]. We kindly request your review and approval of the attached budget so we can proceed with the necessary arrangements.`;
            }
            else if(subject === 'Permission to Host External Guests') {
                bodyArea.value = `We are hosting a special session on ${displayDate} as part of our "${displayEvent}" where we have invited [Guest Name/Designation] as our honorable speaker(s). We kindly request your official permission to allow our guests to enter the university premises and participate in the ${appTypeWord}.`;
            }
            else if(subject === 'General Event Permission') {
                bodyArea.value = `As part of our regular club activities, ${clubName} intends to organize an internal event named "${displayEvent}" on ${displayDate}. The primary objective of this event is to [Briefly state purpose]. We seek your kind approval to proceed with this event.`;
            }
        }

        window.onload = toggleFields;

        document.getElementById('subjectDropdown').addEventListener('change', updateApplicationText);
        document.getElementById('eventDate').addEventListener('change', updateApplicationText);
        document.getElementById('eventName').addEventListener('input', updateApplicationText);
    </script>
</body>
</html>