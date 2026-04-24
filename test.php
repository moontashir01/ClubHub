<?php
session_start();
include 'connection.php';
mysqli_set_charset($con, "utf8mb4");

// Add PHPMailer namespaces at the top
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

$activeUserEmail = $_SESSION['Email'] ?? ('guest+' . session_id() . '@clubhub.local');

// --- UNIFIED SUBMISSION & MAILING HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_response'])) {
    
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    $event_id = (int)$_POST['event_id']; 
    $user_email = $activeUserEmail;
    $form_type = $_POST['form_type']; 
    
    $response_array = json_decode($_POST['response_json'], true);
    $response_array['_form_type'] = $form_type; 
    $final_json = json_encode($response_array); 

    $check = $con->prepare("SELECT response_data FROM forms_responses WHERE event_id = ? AND user_email = ?");
    $check->bind_param("is", $event_id, $user_email);
    $check->execute();
    $res = $check->get_result();
    
    $already_submitted = false;
    while($row = $res->fetch_assoc()) {
        $data = json_decode($row['response_data'], true);
        if(isset($data['_form_type']) && $data['_form_type'] === $form_type) {
            $already_submitted = true;
            break;
        }
    }
    $check->close();

    if ($already_submitted) {
        echo "already"; 
        exit;
    } 

    $cfg_query = mysqli_query($con, "SELECT config_name, config_data FROM event_configs WHERE event_id = $event_id");
    $cfg_row = mysqli_fetch_assoc($cfg_query);
    $cfg_data = json_decode($cfg_row['config_data'], true);
    $club_name = $cfg_row['config_name'] ?? 'The Club';

    if ($form_type === 'ticket') {
        $total_qty = (int)($cfg_data['ticketData']['qty'] ?? 0);
        $sold_query = mysqli_query($con, "SELECT COUNT(*) as c FROM forms_responses WHERE event_id = $event_id AND response_data LIKE '%\"_form_type\":\"ticket\"%'");
        $sold_row = mysqli_fetch_assoc($sold_query);
        $sold = (int)$sold_row['c'];
        
        if ($sold >= $total_qty) {
            echo "full"; 
            exit;
        }

        if (!file_exists(__DIR__ . '/PHPMailer/PHPMailer.php')) {
            echo "MISSING_PHPMAILER_FOLDER: I cannot find the 'PHPMailer' folder.";
            exit;
        }

        require __DIR__ . '/PHPMailer/Exception.php';
        require __DIR__ . '/PHPMailer/PHPMailer.php';
        require __DIR__ . '/PHPMailer/SMTP.php';

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'sagorsrijoy123@gmail.com'; 
            $mail->Password   = 'wfuy ibks mwjq muge'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom('sagorsrijoy123@gmail.com', $club_name);
            $mail->addAddress($user_email);

            $attendee_name = "";
            foreach ($response_array as $key => $value) {
                if (stripos($key, 'name') !== false) {
                    $attendee_name = htmlspecialchars($value);
                    break;
                }
            }

            $ticket_image_name = $cfg_data['ticket_image_path'] ?? '';
            if (!empty($ticket_image_name)) {
                $imagePath = __DIR__ . '/images/' . $ticket_image_name;
                if (file_exists($imagePath)) {
                    $mail->addAttachment($imagePath, 'Event_Ticket.jpg');
                }
            }

            $mail->isHTML(true);
            $mail->Subject = "Your Ticket for " . $club_name;
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                    <p>Hello $attendee_name,</p>
                    <p>Your ticket is attached below.Please show the ticket at entrance</p>
                    <br>
                    <p>Thanks,</p>
                </div>";

            $mail->send(); 

            $stmt = $con->prepare("INSERT INTO forms_responses (event_id, user_email, response_data) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $event_id, $user_email, $final_json);
            if ($stmt->execute()) {
                echo "success";
            } else {
                echo "DB_INSERT_ERROR: " . mysqli_error($con);
            }
            $stmt->close();

        } catch (Exception $e) {
            echo "MAIL_SMTP_ERROR: " . $e->getMessage(); 
            exit; 
        }

    } else {
        $stmt = $con->prepare("INSERT INTO forms_responses (event_id, user_email, response_data) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $event_id, $user_email, $final_json);
        
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "DB_INSERT_ERROR: " . mysqli_error($con);
        }
        $stmt->close();
    }
    exit;
}

$sales_query = mysqli_query($con, "SELECT event_id, COUNT(*) as sold FROM forms_responses WHERE response_data LIKE '%\"_form_type\":\"ticket\"%' GROUP BY event_id");
$tickets_sold = [];
if ($sales_query) {
    while ($s_row = mysqli_fetch_assoc($sales_query)) {
        $tickets_sold[$s_row['event_id']] = (int)$s_row['sold'];
    }
}

$current_time = date('Y-m-d H:i:s');
$query = "SELECT * FROM event_configs WHERE slider_endtime >= '$current_time' ORDER BY slider_endtime ASC"; 
$result = mysqli_query($con, $query);

$events_for_js = [];
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        $config = json_decode($row['config_data'], true);
        $total_tickets = isset($config['ticketData']['qty']) ? (int)$config['ticketData']['qty'] : 0;
        $sold = $tickets_sold[$row['event_id']] ?? 0;
        $remaining = max(0, $total_tickets - $sold);

        $events_for_js[] = [
            'id'         => $row['event_id'], 
            'club'       => $row['config_name'],
            'title'      => $row['config_name'],
            'img'        => 'images/' . ($config['image_path'] ?? 'default.jpg'),
            'desc'       => $config['description'] ?? 'No description available.',
            'longDetail' => $config['longDetail'] ?? '',
            'hSize'      => $config['hSize'] ?? 22,
            'form'       => $config['fields'] ?? [],
            'formColors' => $config['formColors'] ?? null,
            'showReg'    => $config['regToggle'] ?? false, 
            'showTkt'    => $config['tktToggle'] ?? false,
            'ticketData' => $config['ticketData'] ?? null,
            'ticketImg'  => isset($config['ticket_image_path']) && $config['ticket_image_path'] !== "" ? 'images/' . $config['ticket_image_path'] : null,
            'remainingTickets' => $remaining,
            'titleColor' => $config['color'] ?? '#ffffff',
            'yPos'       => ($config['yPos'] ?? 50) . '%',
            'endTime'    => $row['slider_endtime'] 
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Hub</title>
    <style>
        :root { --pink: #ff4d8d; --dark-bg: #0b0b13; --card: #161621; --transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
        html { scroll-snap-type: y mandatory; scroll-behavior: smooth; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--dark-bg); color: white; overflow-x: hidden; }
        * { text-transform: none !important; }

        #status-toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); color: white; padding: 12px 30px; border-radius: 30px; font-weight: bold; z-index: 10000; display: none; box-shadow: 0 4px 15px rgba(0,0,0,0.3); }
        nav { display: flex; justify-content: space-between; padding: 20px 5%; background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent); position: fixed; width: 90%; z-index: 1000; align-items: center; }
        .logo { font-size: 26px; font-weight: 900; letter-spacing: -1px; }

        .hero-section { position: relative; height: 100vh; width: 100%; scroll-snap-align: start; overflow: hidden; }
        .slide { position: absolute; inset: 0; opacity: 0; transition: var(--transition); background-size: cover; display: flex; align-items: center; padding: 0 5%; }
        .slide.active { opacity: 1; z-index: 1; }
        .slide::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, var(--dark-bg) 5%, transparent 70%), linear-gradient(0deg, var(--dark-bg) 0%, transparent 60%); z-index: 2; }
        
        .content { position: relative; z-index: 10; max-width: 650px; }
        h1 { font-size: 4rem; margin: 0 0 5px 0; font-weight: 800; }
        .description { font-size: 1.1rem; color: #ccc; line-height: 1.6; margin-bottom: 20px; }
        .timer-container { margin-bottom: 20px; font-family: monospace; font-size: 1.2rem; background: rgba(255, 77, 141, 0.15); display: inline-block; padding: 8px 15px; border-radius: 5px; border-left: 3px solid var(--pink); color: var(--pink); font-weight: bold; }

        .scroll-hint { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 100; display: flex; flex-direction: column; align-items: center; opacity: 0.5; color: white; text-decoration: none; font-size: 10px; }
        .mouse { width: 20px; height: 32px; border: 2px solid white; border-radius: 10px; position: relative; margin-bottom: 5px; }
        .mouse::before { content: ''; width: 3px; height: 6px; background: white; position: absolute; top: 6px; left: 50%; transform: translateX(-50%); border-radius: 2px; animation: scroll-anim 1.5s infinite; }
        @keyframes scroll-anim { 0% { opacity: 1; transform: translate(-50%, 0); } 100% { opacity: 0; transform: translate(-50%, 10px); } }

        .modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.9); z-index: 2000; display: none; align-items: center; justify-content: center; padding: 20px; backdrop-filter: blur(15px); }
        .modal-content { width: 100%; max-width: 800px; max-height: 85vh; overflow-y: auto; border-radius: 20px; position: relative; padding: 50px; border: 1px solid rgba(255,255,255,0.1); }
        .close-modal { position: absolute; top: 25px; right: 25px; background: var(--pink); border: none; color: white; padding: 8px 18px; border-radius: 30px; cursor: pointer; font-weight: bold; font-size: 10px; z-index: 10; }
        
        .modal-header-centered { text-align: center; width: 100%; margin-bottom: 30px; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; }
        .detail-h { color: var(--pink); font-weight: 800; display: block; margin-top: 25px; margin-bottom: 10px; border-bottom: 1px solid rgba(255, 71, 188, 0.3); padding-bottom: 5px; }

        .clubs-section { position: relative; min-height: 100vh; padding: 120px 5% 50px 5%; background: var(--dark-bg); scroll-snap-align: start; }
        .section-title { font-size: 2rem; margin-bottom: 30px; font-weight: 800; border-left: 4px solid var(--pink); padding-left: 15px; }
        .club-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 25px; }
        .club-card { background: var(--card); border: none; border-radius: 8px; overflow: hidden; cursor: pointer; transition: 0.4s; padding: 0; width: 100%; color: white; }
        .club-card:hover { transform: translateY(-15px); outline: 1px solid var(--pink); }
        .club-card img { width: 100%; height: 260px; object-fit: cover; display: block; }
        .club-card .label { padding: 15px; font-size: 1rem; font-weight: bold; text-align: center; }

        .slider-portals { position: absolute; bottom: 80px; left: 5%; z-index: 100; display: flex; gap: 15px; align-items: flex-end; }
        .mini-card { width: 140px; height: 80px; border-radius: 6px; overflow: hidden; cursor: pointer; position: relative; border: 2px solid transparent; transition: 0.3s; opacity: 0.6; }
        .mini-card img { width: 100%; height: 100%; object-fit: cover; }
        .mini-card.active { opacity: 1; border-color: var(--pink); transform: translateY(-10px) scale(1.1); box-shadow: 0 5px 15px rgba(255, 77, 141, 0.4); }
        .mini-label { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.7); font-size: 10px; padding: 4px; text-align: center; font-weight: bold; }

        button { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important; will-change: transform, box-shadow; }
        button:not([disabled]):hover { transform: translateY(-4px) scale(1.03) !important; box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5) !important; filter: brightness(1.15) !important; }
        button:not([disabled]):active { transform: translateY(1px) scale(0.98) !important; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3) !important; }
        
        nav a[href="logout.php"] { transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important; display: inline-block; }
        nav a[href="logout.php"]:hover { transform: translateY(-3px) scale(1.05) !important; box-shadow: 0 8px 20px rgba(255, 77, 141, 0.5) !important; filter: brightness(1.15) !important; }

        /* --- CHATBOT STYLES --- */
        .chat-widget { position: fixed; bottom: 30px; right: 30px; z-index: 9999; }
        .chat-btn { width: 60px; height: 60px; border-radius: 50%; background: var(--pink); border: none; color: white; font-size: 24px; cursor: pointer; box-shadow: 0 5px 15px rgba(255, 77, 141, 0.4); display: flex; align-items: center; justify-content: center; }
        .chat-btn:hover { transform: scale(1.1); }
        .chat-window { position: absolute; bottom: 80px; right: 0; width: 350px; height: 450px; background: #161621; border-radius: 10px; border: 1px solid rgba(255, 77, 141, 0.3); display: none; flex-direction: column; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.5); }
        .chat-header { background: var(--pink); color: white; padding: 15px; font-weight: bold; display: flex; justify-content: space-between; align-items: center; }
        .close-chat { background: none; border: none; color: white; cursor: pointer; font-size: 16px; box-shadow: none !important; transform: none !important; }
        .chat-body { flex: 1; padding: 15px; overflow-y: auto; display: flex; flex-direction: column; gap: 10px; scrollbar-width: thin; scrollbar-color: var(--pink) #0b0b13; }
        .chat-msg { max-width: 85%; padding: 10px 15px; border-radius: 15px; font-size: 14px; line-height: 1.4; word-wrap: break-word; }
        .chat-msg.user { align-self: flex-end; background: var(--pink); color: white; border-bottom-right-radius: 2px; }
        .chat-msg.bot { align-self: flex-start; background: #2a2a35; color: white; border-bottom-left-radius: 2px; }
        .chat-input { display: flex; padding: 10px; background: #0b0b13; border-top: 1px solid rgba(255,255,255,0.1); }
        .chat-input input { flex: 1; background: transparent; border: none; color: white; padding: 10px; outline: none; font-size: 14px; }
        .chat-input button { background: var(--pink); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; box-shadow: none !important; transform: none !important; }
        .typing-indicator { color: #888; font-size: 12px; font-style: italic; align-self: flex-start; display: none; padding: 0 10px; }
    </style>
</head>
<body>

    <div id="status-toast"></div>

    <nav>
        <div class="logo">Club Hub</div>
        <div> <a href="logout.php" style="background:var(--pink); padding:6px 15px; border-radius:20px; font-weight:bold; color:white; text-decoration:none;">Log Out</a></div>
    </nav>

    <div class="modal-overlay" id="detailsModal">
        <div class="modal-content" style="background:#000; box-shadow: 0 0 50px rgba(255,77,141,0.2);">
            <button class="close-modal" onclick="closeModals()">CLOSE</button>
            <h2 class="modal-header-centered" style="color:var(--pink); font-size: 2rem;">Event Details</h2>
            <div id="modal-detail-body" style="line-height:1.8; color:#eee; font-size:1.1rem; white-space: pre-wrap;"></div>
        </div>
    </div>

    <div class="modal-overlay" id="regModal">
        <div class="modal-content" id="form-container">
            <button class="close-modal" onclick="closeModals()">CLOSE</button>
            <h2 id="form-display-title" class="modal-header-centered" style="font-size: 2rem;"></h2>
            <input type="hidden" id="current-event-id">
            <form id="submission-form" onsubmit="handleRegistration(event, 'register')">
                <div style="display: flex; flex-wrap: wrap; gap: 20px;" id="dynamic-form-grid"></div>
                <button type="submit" id="form-submit-btn" style="width:100%; margin-top:30px; padding:18px; border:none; color:white; font-weight:bold; cursor:pointer; border-radius:10px;">SUBMIT REGISTRATION</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="tktModal">
        <div class="modal-content" id="tkt-container">
            <button class="close-modal" onclick="closeModals()">CLOSE</button>
            <div id="tkt-img-preview" style="width:100%; height:150px; background-size:cover; background-position:center; border-radius:10px; margin-bottom:20px; display:none;"></div>
            <h2 id="tkt-display-title" class="modal-header-centered" style="font-size: 2rem; margin-bottom: 5px;"></h2>
            <p id="tkt-remaining-display" style="text-align:center; font-weight:bold; font-size:14px; margin-top:0; margin-bottom:30px;"></p>
            <input type="hidden" id="current-tkt-event-id">
            <form id="tkt-submission-form" onsubmit="handleRegistration(event, 'ticket')">
                <div style="display: flex; flex-wrap: wrap; gap: 20px;" id="dynamic-tkt-grid"></div>
                <button type="submit" id="tkt-submit-btn" style="width:100%; margin-top:30px; padding:18px; border:none; color:white; font-weight:bold; cursor:pointer; border-radius:10px;">PURCHASE TICKET</button>
            </form>
        </div>
    </div>

    <div class="hero-section" id="hero-slider">
        <div class="slider-portals" id="mini-portal-container"></div>
        <a href="#clubs" class="scroll-hint">
            <div class="mouse"></div>
            <span>SCROLL</span>
        </a>
    </div>

    <section class="clubs-section" id="clubs">
        <div class="section-title">Explore Clubs</div>
        <div class="club-grid">
            
            <button class="club-card" onclick="window.location.href='club_info_display.php?id=1'">
                <img src="images/debate.png">
                <div class="label">Debate Society (NSUDC)</div>
            </button>

            <button class="club-card" onclick="window.location.href='club_info_display.php?id=3'">
                <img src="images/CDC logo.png">
                <div class="label">Cine and Drama Club (NSUCDC)</div>
            </button>

            <button class="club-card" onclick="window.location.href='club_info_display.php?id=1'">
                <img src="images/nsuss-logo.png">
                <div class="label">Shangskritik Shangathan (NSUSS)</div>
            </button>

            <button class="club-card" onclick="window.location.href='club_info_display.php?id=4'">
                <img src="images/nsuac-logo.png">
                <div class="label">Athletics Club (NSUAC)</div>
            </button>

            <button class="club-card" onclick="window.location.href='club_info_display.php?id=5'">
                <img src="images/nsussc-logo.jpg">
                <div class="label">Social Services Club (NSUSSC)</div>
            </button>

        </div>
    </section>

    <div class="chat-widget">
        <div class="chat-window" id="chatWindow">
            <div class="chat-header">
                <span>ClubHub Assistant 🤖</span>
                <button class="close-chat" onclick="toggleChat()">✖</button>
            </div>
            <div class="chat-body" id="chatBody">
                <div class="chat-msg bot">Hello! 😊 I'm your NSU Club Hub assistant. How can I help you today?</div>
                <div class="typing-indicator" id="typingIndicator">AI is thinking...</div>
            </div>
            <form class="chat-input" onsubmit="handleChatSubmit(event)">
                <input type="text" id="chatInput" placeholder="Type a message..." required autocomplete="off">
                <button type="submit">➤</button>
            </form>
        </div>
        <button class="chat-btn" onclick="toggleChat()">💬</button>
    </div>

    <script>
        const contentData = <?php echo json_encode($events_for_js, JSON_UNESCAPED_UNICODE); ?>;
        const hero = document.getElementById('hero-slider');
        const miniContainer = document.getElementById('mini-portal-container');

        contentData.forEach((item, i) => {
            const slide = document.createElement('div');
            slide.className = `slide ${i === 0 ? 'active' : ''}`;
            slide.style.backgroundImage = `url('${item.img}')`;
            slide.style.backgroundPosition = `center ${item.yPos}`;
            
            let regBtn = item.showReg ? `<button onclick="openRegForm(${i})" style="background:var(--pink); border:none; color:white; padding:14px 35px; border-radius:30px; font-weight:bold; cursor:pointer; margin-right:10px;">Register</button>` : '';
            let tktBtn = '';
            if (item.showTkt) {
                if (item.remainingTickets <= 0) {
                    tktBtn = `<button disabled style="background:#555; border:none; color:#ccc; padding:14px 35px; border-radius:30px; font-weight:bold; margin-right:10px; cursor:not-allowed;">House Full</button>`;
                } else {
                    tktBtn = `<button onclick="openTicketForm(${i})" style="background:transparent; border:1px solid white; color:white; padding:14px 35px; border-radius:30px; font-weight:bold; cursor:pointer; margin-right:10px;">Get Tickets</button>`;
                }
            }

            slide.innerHTML = `
                <div class="content">
                    <h1 style="color:${item.titleColor}">${item.title}</h1>
                    <div class="timer-container" id="timer-${i}">Ends in: --h --m --s</div>
                    <p class="description">${item.desc}</p>
                    <div style="display:flex;">
                        ${regBtn} ${tktBtn}
                        <button onclick="openDetails(${i})" style="background:rgba(255,255,255,0.1); border:1px solid white; color:white; padding:14px 35px; border-radius:30px; cursor:pointer; font-weight:bold;">View Details</button>
                    </div>
                </div>`;
            hero.appendChild(slide);

            const mini = document.createElement('div');
            mini.className = `mini-card ${i === 0 ? 'active' : ''}`;
            mini.innerHTML = `<img src="${item.img}"><div class="mini-label">${item.club}</div>`;
            mini.onclick = () => jumpToSlide(i);
            miniContainer.appendChild(mini);
        });

        function updateTimers() {
            contentData.forEach((item, i) => {
                const target = new Date(item.endTime).getTime();
                const now = new Date().getTime();
                const diff = target - now;
                const timerEl = document.getElementById(`timer-${i}`);
                if (diff <= 0) { timerEl.innerText = "Event Ended"; return; }
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);
                let display = `Ends in: `;
                if(days > 0) display += `${days}d `;
                display += `${hours}h ${mins}m ${secs}s`;
                timerEl.innerText = display;
            });
        }
        setInterval(updateTimers, 1000);
        updateTimers();

        function openDetails(idx) {
            const item = contentData[idx];
            let formatted = item.longDetail.replace(/\[\+H\](.*?)\[H\+\]/g, `<span class="detail-h" style="font-size:${item.hSize/16}rem;">$1</span>`);
            document.getElementById('modal-detail-body').innerHTML = formatted;
            document.getElementById('detailsModal').style.display = 'flex';
        }

        function openRegForm(idx) {
            const item = contentData[idx];
            const colors = item.formColors;
            const container = document.getElementById('form-container');
            const grid = document.getElementById('dynamic-form-grid');
            document.getElementById('current-event-id').value = item.id;
            container.style.backgroundColor = colors.bg;
            document.getElementById('form-display-title').innerText = colors.formTitleText;
            document.getElementById('form-display-title').style.color = colors.title;
            document.getElementById('form-submit-btn').style.backgroundColor = colors.btn;
            grid.innerHTML = '';
            item.form.forEach((f) => {
                const div = document.createElement('div');
                div.style.flex = f.isFull ? "0 0 100%" : "0 0 calc(50% - 10px)";
                let input = f.type === 'dropdown' ? 
                    `<select data-label="${f.label}" required style="background:${colors.fieldBg}; color:${colors.fieldTxt}; padding:12px; width:100%; border:1px solid rgba(0,0,0,0.1); border-radius:8px;">${f.options.split('\n').map(o => `<option>${o.trim()}</option>`).join('')}</select>` : 
                    `<input type="text" data-label="${f.label}" required style="background:${colors.fieldBg}; color:${colors.fieldTxt}; padding:12px; width:100%; border:1px solid rgba(0,0,0,0.1); border-radius:8px;">`;
                div.innerHTML = `<label style="color:${colors.label}; display:block; font-size:11px; font-weight:bold; margin-bottom:5px; text-transform:uppercase;">${f.label}</label>${input}`;
                grid.appendChild(div);
            });
            document.getElementById('regModal').style.display = 'flex';
        }

        function openTicketForm(idx) {
            const item = contentData[idx];
            const colors = item.ticketData.colors;
            const container = document.getElementById('tkt-container');
            const grid = document.getElementById('dynamic-tkt-grid');
            const imgPreview = document.getElementById('tkt-img-preview');
            document.getElementById('current-tkt-event-id').value = item.id;
            container.style.backgroundColor = colors.bg;
            if (item.ticketImg) { imgPreview.style.backgroundImage = `url('${item.ticketImg}')`; imgPreview.style.display = 'block'; } else { imgPreview.style.display = 'none'; }
            document.getElementById('tkt-display-title').innerText = colors.formTitleText;
            document.getElementById('tkt-display-title').style.color = colors.title;
            const remainingLabel = document.getElementById('tkt-remaining-display');
            remainingLabel.innerText = `${item.remainingTickets} Tickets Available`;
            remainingLabel.style.color = colors.label;
            document.getElementById('tkt-submit-btn').style.backgroundColor = colors.btn;
            grid.innerHTML = '';
            item.ticketData.fields.forEach((f) => {
                const div = document.createElement('div');
                div.style.flex = f.isFull ? "0 0 100%" : "0 0 calc(50% - 10px)";
                let input = f.type === 'dropdown' ? 
                    `<select data-label="${f.label}" required style="background:${colors.fieldBg}; color:${colors.fieldTxt}; padding:12px; width:100%; border:1px solid rgba(0,0,0,0.1); border-radius:8px;">${f.options.split('\n').map(o => `<option>${o.trim()}</option>`).join('')}</select>` : 
                    `<input type="text" data-label="${f.label}" required style="background:${colors.fieldBg}; color:${colors.fieldTxt}; padding:12px; width:100%; border:1px solid rgba(0,0,0,0.1); border-radius:8px;">`;
                div.innerHTML = `<label style="color:${colors.label}; display:block; font-size:11px; font-weight:bold; margin-bottom:5px; text-transform:uppercase;">${f.label}</label>${input}`;
                grid.appendChild(div);
            });
            document.getElementById('tktModal').style.display = 'flex';
        }

        function handleRegistration(e, formType) {
            e.preventDefault();
            const eventId = formType === 'ticket' ? document.getElementById('current-tkt-event-id').value : document.getElementById('current-event-id').value;
            const gridId = formType === 'ticket' ? '#dynamic-tkt-grid' : '#dynamic-form-grid';
            const submitBtnId = formType === 'ticket' ? 'tkt-submit-btn' : 'form-submit-btn';
            const submitBtn = document.getElementById(submitBtnId);
            const originalBtnText = submitBtn.innerText;
            submitBtn.innerText = formType === 'ticket' ? "Processing & Emailing..." : "Sending...";
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.7';
            submitBtn.style.cursor = 'wait';
            const inputs = document.querySelectorAll(gridId + ' input, ' + gridId + ' select');
            let data = {};
            inputs.forEach(el => { data[el.getAttribute('data-label')] = el.value; });
            const fd = new FormData();
            fd.append('submit_response', 'true');
            fd.append('event_id', eventId);
            fd.append('form_type', formType); 
            fd.append('response_json', JSON.stringify(data));
            fetch(window.location.href, { method: 'POST', body: fd })
            .then(r => r.text())
            .then(res => {
                const toast = document.getElementById('status-toast');
                const cleanRes = res.trim();
                if (cleanRes !== 'success' && cleanRes !== 'already' && cleanRes !== 'full') { alert("⚠️ PHP ERROR:\n\n" + cleanRes); }
                submitBtn.innerText = originalBtnText; submitBtn.disabled = false; submitBtn.style.opacity = '1'; submitBtn.style.cursor = 'pointer';
                if(cleanRes === 'success') {
                    toast.innerText = formType === 'ticket' ? "Ticket Sent! ✓" : "Registered! ✓";
                    toast.style.background = "#2ecc71"; toast.style.display = "block"; closeModals();
                    setTimeout(() => window.location.reload(), 2000); 
                } else {
                    toast.innerText = cleanRes; toast.style.background = "#e74c3c"; toast.style.display = "block";
                    setTimeout(() => { toast.style.display = 'none'; }, 5000);
                }
            });
        }

        function closeModals() {
            document.getElementById('detailsModal').style.display = 'none';
            document.getElementById('regModal').style.display = 'none';
            document.getElementById('tktModal').style.display = 'none';
        }

        let currentIdx = 0;
        const slides = document.querySelectorAll('.slide');
        const minis = document.querySelectorAll('.mini-card');
        function jumpToSlide(n) {
            slides[currentIdx].classList.remove('active'); minis[currentIdx].classList.remove('active');
            currentIdx = n; slides[currentIdx].classList.add('active'); minis[currentIdx].classList.add('active');
        }
        setInterval(() => jumpToSlide((currentIdx + 1) % slides.length), 5000);

        // --- CHATBOT SESSIONS & CACHE ---
        const tabSessionId = "tab_" + Date.now() + "_" + Math.random().toString(36).substr(2, 5);

        function toggleChat() {
            const chatWin = document.getElementById('chatWindow');
            chatWin.style.display = chatWin.style.display === 'flex' ? 'none' : 'flex';
        }

        function appendMessage(text, sender) {
            const chatBody = document.getElementById('chatBody');
            const typingInd = document.getElementById('typingIndicator');
            const msgDiv = document.createElement('div');
            msgDiv.className = `chat-msg ${sender}`;
            msgDiv.innerText = text;
            chatBody.insertBefore(msgDiv, typingInd);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        async function handleChatSubmit(e) {
            e.preventDefault();
            const inputField = document.getElementById('chatInput');
            const message = inputField.value.trim();
            if (!message) return;

            appendMessage(message, 'user');
            inputField.value = '';
            document.getElementById('typingIndicator').style.display = 'block';
            document.getElementById('chatBody').scrollTop = document.getElementById('chatBody').scrollHeight;

            try {
                const response = await fetch('http://127.0.0.1:5000/chat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ 
                        message: message,
                        session_id: tabSessionId 
                    })
                });
                const data = await response.json();
                document.getElementById('typingIndicator').style.display = 'none';
                appendMessage(data.answer || "Sorry, I'm having trouble thinking.", 'bot');
            } catch (err) {
                document.getElementById('typingIndicator').style.display = 'none';
                appendMessage("AI server is offline. Run 'python app.py'.", 'bot');
            }
        }
    </script>
</body>
</html>