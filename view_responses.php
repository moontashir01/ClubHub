<?php
session_start();
include 'connection.php';
mysqli_set_charset($con, "utf8mb4");

// 1. Authenticate Club
if (!isset($_SESSION['club_id'])) {
    header("Location: homepage.php");
    exit(); 
}

$club_id = $_SESSION['club_id'];

// 2. Fetch all events for this specific club
$events = [];
$event_stmt = $con->prepare("SELECT event_id, event_name FROM events WHERE club_id = ? ORDER BY event_id DESC");
$event_stmt->bind_param("i", $club_id);
$event_stmt->execute();
$event_res = $event_stmt->get_result();
while($row = $event_res->fetch_assoc()) {
    $events[] = $row;
}
$event_stmt->close();

// 3. Handle Selected Event & Fetch Responses
$selected_event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
$responses = [];
$columns = ['User Email', 'Form Type']; // Base columns we always want to show
$event_title = "Select an event to view responses";

if ($selected_event_id) {
    // Security Check: Make sure the requested event actually belongs to this club
    $check_stmt = $con->prepare("SELECT event_name FROM events WHERE event_id = ? AND club_id = ?");
    $check_stmt->bind_param("ii", $selected_event_id, $club_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    
    if ($check_res->num_rows > 0) {
        $event_title = "Responses for: " . $check_res->fetch_assoc()['event_name'];
        
        // Fetch Responses
        $resp_stmt = $con->prepare("SELECT user_email, response_data FROM forms_responses WHERE event_id = ?");
        
        if (!$resp_stmt) {
            die("<div style='color:white; padding:20px;'><strong>Database Query Failed:</strong> " . $con->error . "</div>");
        }

        $resp_stmt->bind_param("i", $selected_event_id);
        $resp_stmt->execute();
        $resp_result = $resp_stmt->get_result();
        
        while($r = $resp_result->fetch_assoc()) {
            $data = json_decode($r['response_data'], true);
            
            // Extract the form type (ticket vs register) if it exists
            $form_type = isset($data['_form_type']) ? ucfirst($data['_form_type']) : 'General';
            unset($data['_form_type']); // Remove it so it doesn't duplicate in dynamic columns
            
            // Build the row array
            $row_data = [
                'User Email' => $r['user_email'],
                'Form Type' => $form_type
            ];
            
            // Add dynamic JSON fields to row and global column list
            if (is_array($data)) {
                foreach($data as $key => $value) {
                    if (!in_array($key, $columns)) {
                        $columns[] = $key;
                    }
                    $row_data[$key] = $value;
                }
            }
            $responses[] = $row_data;
        }
        $resp_stmt->close();
    } else {
        $event_title = "Unauthorized or Event Not Found.";
    }
    $check_stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Responses - Club Hub</title>
    <style>
        :root { --pink: #ff4d8d; --dark-bg: #0b0b13; --card: #161621; }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', sans-serif; }
        body { background: var(--dark-bg); color: white; display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar Styles */
        .sidebar { width: 300px; background: #111; border-right: 1px solid rgba(255, 77, 141, 0.2); display: flex; flex-direction: column; }
        .sidebar-header { padding: 25px; border-bottom: 1px solid rgba(255, 255, 255, 0.1); }
        .sidebar-header h2 { color: var(--pink); font-size: 20px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; }
        
        .back-btn { background: var(--card); color: white; border: 1px solid rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; transition: 0.3s; }
        .back-btn:hover { background: var(--pink); border-color: var(--pink); color: white; }

        .event-list { flex-grow: 1; overflow-y: auto; padding: 15px; }
        .event-item { display: block; padding: 15px; background: var(--card); margin-bottom: 10px; border-radius: 8px; text-decoration: none; color: white; border-left: 3px solid transparent; transition: 0.3s; }
        .event-item:hover { background: #222230; transform: translateX(5px); }
        .event-item.active { border-left-color: var(--pink); background: rgba(255, 77, 141, 0.1); font-weight: bold; }

        /* Main Content Styles */
        .main-content { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; background: radial-gradient(circle at top right, #1a1a2e, #0b0b13); }
        .content-header { padding: 30px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); }
        .content-header h1 { font-size: 24px; font-weight: 800; }
        
        /* Search Box Styles */
        .search-container { margin-top: 15px; }
        .search-input { width: 100%; max-width: 400px; padding: 12px 20px; border-radius: 30px; border: 1px solid rgba(255, 77, 141, 0.4); background: rgba(0,0,0,0.5); color: white; font-size: 14px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--pink); box-shadow: 0 0 15px rgba(255, 77, 141, 0.3); background: rgba(0,0,0,0.8); }
        .highlight { background-color: var(--pink); color: white; border-radius: 3px; padding: 0 2px; font-weight: bold; }

        .table-container { flex-grow: 1; padding: 30px; overflow: auto; }
        
        table { width: 100%; border-collapse: collapse; background: var(--card); border-radius: 10px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        thead { background: rgba(255, 77, 141, 0.15); }
        th { padding: 15px; text-align: left; color: var(--pink); font-size: 13px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid var(--pink); white-space: nowrap; }
        td { padding: 15px; border-bottom: 1px solid rgba(255, 255, 255, 0.05); font-size: 14px; color: #ccc; }
        tr:hover td { background: rgba(255, 255, 255, 0.02); color: white; }
        
        .badge { background: #222230; padding: 5px 10px; border-radius: 20px; font-size: 11px; font-weight: bold; border: 1px solid rgba(255,255,255,0.2); }
        .badge.ticket { color: #00d2d3; border-color: rgba(0, 210, 211, 0.3); background: rgba(0, 210, 211, 0.1); }
        .badge.register { color: #ff9f43; border-color: rgba(255, 159, 67, 0.3); background: rgba(255, 159, 67, 0.1); }

        .empty-state { text-align: center; padding: 50px; color: #666; font-style: italic; }
        
        /* Scrollbar styling */
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: var(--dark-bg); }
        ::-webkit-scrollbar-thumb { background: rgba(255, 77, 141, 0.5); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--pink); }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <a href="club_dashboard.php" class="back-btn">← Back to Dashboard</a>
            <h2 style="margin-top: 20px;">Your Events</h2>
        </div>
        <div class="event-list">
            <?php if (empty($events)): ?>
                <div class="empty-state" style="padding: 20px;">No events found.</div>
            <?php else: ?>
                <?php foreach ($events as $ev): ?>
                    <a href="?event_id=<?= $ev['event_id'] ?>" class="event-item <?= ($selected_event_id == $ev['event_id']) ? 'active' : '' ?>">
                        <?= htmlspecialchars($ev['event_name']) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div class="main-content">
        <div class="content-header">
            <h1><?= htmlspecialchars($event_title) ?></h1>
            <?php if ($selected_event_id && !empty($responses)): ?>
                <p style="color: #888; margin-top: 5px; font-size: 14px;">Total Responses: <?= count($responses) ?></p>
                <div class="search-container">
                    <input type="text" id="searchInput" class="search-input" placeholder="Search keywords">
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <?php if (!$selected_event_id): ?>
                <div class="empty-state">
                    <h2 style="color: var(--pink); margin-bottom: 10px;">Select an event from the sidebar</h2>
                    <p>Click on any of your events on the left to load the form and ticket responses.</p>
                </div>
            <?php elseif (empty($responses)): ?>
                <div class="empty-state">
                    <h2>No responses yet.</h2>
                    <p>Once users register or buy tickets, their data will appear here.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <?php foreach ($columns as $col): ?>
                                <th><?= htmlspecialchars($col) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($responses as $row): ?>
                            <tr>
                                <?php foreach ($columns as $col): ?>
                                    <td>
                                        <?php 
                                            if ($col === 'Form Type') {
                                                $type = strtolower($row[$col] ?? 'general');
                                                echo "<span class='badge {$type}'>" . htmlspecialchars($row[$col] ?? 'N/A') . "</span>";
                                            } else {
                                                echo htmlspecialchars($row[$col] ?? '-'); 
                                            }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                const rows = document.querySelectorAll('tbody tr');

                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    
                    rows.forEach(row => {
                        let rowMatches = false;
                        const cells = row.querySelectorAll('td');
                        
                        cells.forEach(cell => {
                            // First, remove any existing highlight spans from previous searches
                            const highlightedSpans = cell.querySelectorAll('span.highlight');
                            highlightedSpans.forEach(span => {
                                const parent = span.parentNode;
                                parent.replaceChild(document.createTextNode(span.textContent), span);
                                parent.normalize(); // Merge text nodes back together
                            });
                            
                            // If search is empty, we are done cleaning up this cell
                            if (searchTerm === '') return;
                            
                            // A recursive function to safely find and highlight text nodes ONLY
                            // This ensures we do not accidentally break HTML tags (like your form type badges)
                            const walkAndHighlight = (node) => {
                                if (node.nodeType === 3) { // Text node
                                    const text = node.nodeValue;
                                    const lowerText = text.toLowerCase();
                                    const index = lowerText.indexOf(searchTerm);
                                    
                                    if (index !== -1) {
                                        rowMatches = true;
                                        
                                        const span = document.createElement('span');
                                        span.className = 'highlight';
                                        span.textContent = text.substring(index, index + searchTerm.length);
                                        
                                        const beforeText = document.createTextNode(text.substring(0, index));
                                        const afterText = document.createTextNode(text.substring(index + searchTerm.length));
                                        
                                        const parent = node.parentNode;
                                        parent.insertBefore(beforeText, node);
                                        parent.insertBefore(span, node);
                                        parent.insertBefore(afterText, node);
                                        parent.removeChild(node);
                                    }
                                } else if (node.nodeType === 1 && node.className !== 'highlight') {
                                    // If it's an element node (like a span/div), recurse inside it
                                    Array.from(node.childNodes).forEach(walkAndHighlight);
                                }
                            };
                            
                            Array.from(cell.childNodes).forEach(walkAndHighlight);
                        });
                        
                        // Show the row if there was a match, hide it if there wasn't
                        if (searchTerm === '' || rowMatches || row.textContent.toLowerCase().includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>