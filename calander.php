<?php
include 'connection.php';

mysqli_report(MYSQLI_REPORT_OFF);
$conn = new mysqli($db_server, $db_user, $db_pass, $db_name, $port);

if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

$calendar_data = [];


$sql_events = "SELECT event_name, event_date, event_creator FROM events";
$result_events = $conn->query($sql_events);

if ($result_events && $result_events->num_rows > 0) {
    while($row = $result_events->fetch_assoc()) {
        $calendar_data[] = [
            'title' => $row['event_name'],
            'start' => $row['event_date'], 
            'color' => '#3b82f6',
            'extendedProps' => [
                'type' => 'Event',
                'details' => "Creator: " . $row['event_creator']
            ]
        ];
    }
}


$sql_bookings = "SELECT room_id, club_name, booking_date, start_time, end_time, purpose FROM room_bookings";
$result_bookings = $conn->query($sql_bookings);

if ($result_bookings && $result_bookings->num_rows > 0) {
    while($row = $result_bookings->fetch_assoc()) {
       
        $start_datetime = $row['booking_date'] . 'T' . $row['start_time'];
        $end_datetime = $row['booking_date'] . 'T' . $row['end_time'];

        $calendar_data[] = [
            'title' => 'Room ' . $row['room_id'] . ' (' . $row['club_name'] . ')',
            'start' => $start_datetime,
            'end' => $end_datetime,    
            'color' => '#10b981', 
            'extendedProps' => [
                'type' => 'Room Booking',
                'details' => "Purpose: " . $row['purpose'] . "\nTime: " . $row['start_time'] . " - " . $row['end_time']
            ]
        ];
    }
}

$conn->close();


$events_json = json_encode($calendar_data, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Calendar - Club Hub</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js"></script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a; 
            color: #f8fafc;
            margin: 0;
            padding: 30px 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        
        .dashboard-btn-container {
            margin-bottom: 20px;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            background-color: #334155;
            color: #f8fafc;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 6px;
            font-weight: 500;
            font-size: 14px;
            border: 1px solid #475569;
            transition: all 0.2s ease;
        }
        .back-btn:hover {
            background-color: #475569;
            color: #ffffff;
        }
        .back-btn span {
            margin-right: 8px;
            font-size: 18px;
        }

        /* Calendar Card Styling */
        .calendar-wrapper {
            background: #1e293b;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.4);
        }

        h2 { text-align: center; margin-bottom: 20px; color: #f1f5f9; }

        
        #calendar { background: #1e293b; color: #f8fafc; }
        .fc-toolbar-title { color: #f8fafc !important; }
        .fc-button-primary { background-color: #3b82f6 !important; border: none !important; }
        .fc-button-primary:hover { background-color: #2563eb !important; }
        .fc-col-header-cell-cushion, .fc-daygrid-day-number { color: #cbd5e1 !important; text-decoration: none; }
        .fc-event { cursor: pointer; padding: 4px; font-size: 12px; border-radius: 4px; border: none; }

    
        .legend {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px solid #334155;
            font-size: 14px;
            color: #cbd5e1;
        }
        .legend-item { display: flex; align-items: center; gap: 8px; }
        .box { width: 14px; height: 14px; border-radius: 3px; }
    </style>
</head>
<body>

    <div class="container">
        
        <div class="calendar-wrapper">
            <h2>Activity Calendar</h2>
            
            <div id="calendar"></div>

            <div class="legend">
                <div class="legend-item"><div class="box" style="background-color: #3b82f6;"></div> Official Events</div>
                <div class="legend-item"><div class="box" style="background-color: #10b981;"></div> Room Bookings</div>
            </div>
        </div>

    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            
            // PHP theke asha Dynamic JSON Data ekhane catch kora hocche
            var eventsData = <?php echo $events_json; ?>;

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,listMonth'
                },
                events: eventsData, 

                
                eventDidMount: function(info) {
                    var title = info.event.title;
                    var props = info.event.extendedProps;
                    
                    var hoverText = "[" + props.type + "]\n" + 
                                    "Title: " + title + "\n\n" + 
                                    props.details;
                    
                    info.el.setAttribute('title', hoverText);
                }
            });

            calendar.render();
        });
    </script>

</body>
</html>