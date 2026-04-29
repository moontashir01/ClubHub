<?php
session_start();
include 'connection.php';



$email = $_SESSION['Email'] ?? '';
$name = $_SESSION['Name'] ?? 'Guest';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubHub | Dashboard</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    <style>
        /* === NOTIFICATION BELL (Club Dashboard) === */
        .notif-wrapper { position: relative; margin-right: 10px; }
        .notif-bell {
            background: var(--card); border: 1px solid #2a2a38; border-radius: 14px;
            width: 44px; height: 44px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative; transition: all 0.3s ease; font-size: 1.2rem;
        }
        .notif-bell:hover { border-color: var(--pink); transform: scale(1.08); }
        .notif-badge {
            position: absolute; top: -5px; right: -5px;
            background: var(--pink); color: white; font-size: 0.62rem; font-weight: 800;
            min-width: 18px; height: 18px; border-radius: 99px;
            display: none; align-items: center; justify-content: center;
            padding: 0 4px; box-shadow: 0 2px 8px rgba(255, 77, 141, 0.4);
            animation: badgePulse 2s ease-in-out infinite;
        }
        .notif-badge.visible { display: flex; }
        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        .notif-dropdown {
            position: absolute; top: calc(100% + 10px); right: 0;
            width: 360px; max-height: 420px; overflow-y: auto;
            background: var(--card); backdrop-filter: blur(20px);
            border: 1px solid #2a2a38; border-radius: 16px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            display: none; z-index: 2000;
        }
        .notif-dropdown.open { display: block; animation: dropIn 0.25s ease-out; }
        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            padding: 14px 18px; border-bottom: 1px solid #2a2a38;
            display: flex; justify-content: space-between; align-items: center;
        }
        .notif-header h4 { margin: 0; font-size: 0.95rem; color: var(--text); }
        .notif-mark-read {
            background: none; border: none; color: var(--pink); font-size: 0.75rem;
            cursor: pointer; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .notif-mark-read:hover { text-decoration: underline; }
        .notif-list { padding: 6px 0; }
        .notif-item {
            padding: 12px 18px; display: flex; gap: 10px; align-items: flex-start;
            transition: background 0.2s; cursor: pointer; border-left: 3px solid transparent;
        }
        .notif-item:hover { background: rgba(255,255,255,0.03); }
        .notif-item.unread { border-left-color: var(--pink); background: rgba(255, 77, 141, 0.04); }
        .notif-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--pink); flex-shrink: 0; margin-top: 6px; }
        .notif-item.read .notif-dot { background: #2a2a38; }
        .notif-body { flex: 1; min-width: 0; }
        .notif-msg { font-size: 0.86rem; color: var(--text); line-height: 1.45; margin: 0; }
        .notif-time { font-size: 0.7rem; color: var(--muted); margin-top: 4px; }
        .notif-empty { padding: 28px 18px; text-align: center; color: var(--muted); font-size: 0.86rem; }

        /* Light theme overrides for notifications */
        body.light-theme .notif-bell { border-color: #cfd6e4; background: var(--card); }
        body.light-theme .notif-dropdown { border-color: #cfd6e4; background: var(--card); }
        body.light-theme .notif-header { border-bottom-color: #cfd6e4; }
        body.light-theme .notif-item.read .notif-dot { background: #cfd6e4; }
        body.light-theme .notif-item:hover { background: rgba(15, 23, 42, 0.04); }
        body.light-theme .notif-item.unread { background: rgba(255, 77, 141, 0.06); }
    </style>
<body>

    <nav>
        <div class="logo">ClubHub</div>
        <?php
            $displayName = isset($_SESSION['Name']) ? $_SESSION['Name'] : null;
            if (!$displayName && isset($_SESSION['Email'])) {
                $displayName = explode('@', $_SESSION['Email'])[0];
            }
            if (!$displayName) {
                $displayName = "Guest";
            }
        ?>
        <div style="display: flex; align-items: center;">
            <div class="notif-wrapper" id="notif-wrapper">
                <div class="notif-bell" id="notif-bell" title="Notifications">
                    🔔
                    <span class="notif-badge" id="notif-badge">0</span>
                </div>
                <div class="notif-dropdown" id="notif-dropdown">
                    <div class="notif-header">
                        <h4>Notifications</h4>
                        <button class="notif-mark-read" id="notif-mark-read">Mark all read</button>
                    </div>
                    <div class="notif-list" id="notif-list">
                        <div class="notif-empty">No notifications yet.</div>
                    </div>
                </div>
            </div>
            <div class="profile-menu" id="profile-menu">
                <button class="profile-trigger" id="profile-trigger" type="button" aria-expanded="false"><?php echo htmlspecialchars($displayName); ?></button>
                <div class="profile-dropdown">
                    <label class="theme-toggle-item" for="theme-switch">
                        <span>Change Theme</span>
                        <span class="theme-switch">
                            <input type="checkbox" id="theme-switch" aria-label="Toggle theme">
                            <span class="theme-slider"></span>
                        </span>
                    </label>
                    <a class="logout-link" href="logout.php">Log out</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="hero-section" id="hero-slider"></div>

    <section class="dashboard-section" id="dashboard">
        <div class="header">Club Management Dashboard</div>
        <div class="portal-grid" id="dashboard-grid"></div>
    </section>

    <div id="modal-overlay">
        <div class="modal-content">
            <h2 id="modal-title">Action</h2>
            <!-- <input type="text" placeholder="Enter details here..." style="width:100%; padding:10px; margin:15px 0; background:#0b0b13; border:1px solid #444; color:white; border-radius:4px; box-sizing:border-box;"> -->
            <div id="modal-body"></div>
            <br>
            <button onclick="closeModal()" style="background:var(--pink); border:none; padding:10px 20px; border-radius:4px; color:white; cursor:pointer;">Confirm</button>
            <button onclick="closeModal()" style="background:transparent; border:none; color:var(--muted); cursor:pointer; margin-left:10px;">Cancel</button>
        </div>
    </div>

    <script>
        
    <?php
        isset($_SESSION['Email'])? $email = $_SESSION['Email'] : $email = "";
        $query = mysqli_query($con,"
            SELECT *
            FROM `user`
            INNER JOIN `students`
                ON `user`.email = students.student_email
            INNER JOIN `club_members`
                ON club_members.student_id = students.student_id
            INNER JOIN `clubs`
                ON club_members.club_id = clubs.club_id
            WHERE `user`.email = '$email'
        ");

        if(!$query){
            die(mysqli_error($con)); // shows SQL errors
        }

    $row = mysqli_fetch_assoc($query);
    $clubName = $row ? $row['club_name'] : "No Club Found";
    $clubId = $row ? intval($row['club_id']) : 0;
    $_SESSION['club_id'] = $clubId;
    $pendingCount = 0;
    if ($clubId) {
        $pendingQuery = mysqli_query($con, "
            SELECT COALESCE(SUM(GREATEST(r.requested_count - IFNULL(a.assigned_count, 0), 0)), 0) AS pending
            FROM volunteer_request_club r
            INNER JOIN events e ON e.event_id = r.event_id
            LEFT JOIN (
                SELECT vr.club_id, vr.event_id, COUNT(*) AS assigned_count
                FROM volunteer_requests vr
                WHERE vr.club_id = $clubId
                GROUP BY vr.club_id, vr.event_id
            ) a ON a.event_id = r.event_id AND a.club_id = r.club_id
            WHERE r.club_id = $clubId AND e.event_creator = 'admin'
        ");
        if ($pendingQuery) {
            $pendingRow = mysqli_fetch_assoc($pendingQuery);
            $pendingCount = $pendingRow ? intval($pendingRow['pending']) : 0;
        }
    }
    ?>
        const contentData = [
            { title: 'Welcome to <?php echo htmlspecialchars($clubName); ?> ', desc: 'Manage your club operations effectively.', img: 'images/club_dashboard_cover.jpg' }
        ];

        const dashboardActions = [
            { title: 'Forms', icon: '📋', desc: 'View form responses.', link: 'view_responses.php' },
            { title: 'Send Volunteers', icon: '🚀', desc: 'Dispatch members for events.', link: 'sendVolunteer.php', badge: <?php echo $pendingCount; ?> },
            { title: 'Add Members', icon: '➕', desc: 'Register new members.', link: 'manage_members.php' },
            { title: 'Event Logs', icon: '📝', desc: 'Review past and upcoming activities.', link: 'eventlogs.php' },
            { title: 'Space Booking', icon: '🏢', desc: 'Reserve room/venue.', link: 'space_booking.php' },
            { title: 'Event Slider', icon: '🎨', desc: 'Customize event slider.', link: 'event_studio.php' },
            { title: 'Application', icon: '📜', desc: 'Send official application to Admin and Check your application status.', link: 'application.php' },
            { title: 'Create ID card', icon: '🪪', desc: 'Create an ID card for your upcomming event.', link: 'generate_id.php' },
            { title: 'Room Bookings', icon: '🏢', desc: 'Book a room for your event and View status of room booking.', link: 'room.php' },
            { title: 'System Analytics', icon: '📊', desc: 'View deep-dive club analytics.', link: 'Canalysis.php' } 
        ];

        
        const hero = document.getElementById('hero-slider');
        contentData.forEach(item => {
            const slide = document.createElement('div');
            slide.className = 'slide active';
            slide.style.backgroundImage = `url('${item.img}')`;
            slide.innerHTML = `<div class="content"><h1>${item.title}</h1><p class="description">${item.desc}</p></div>`;
            hero.appendChild(slide);
        });

        
        const dashGrid = document.getElementById('dashboard-grid');
        dashboardActions.forEach(action => {
            const card = document.createElement('div');
            card.className = 'portal-card';
            card.onclick = () => {
                if (action.link) {
                    window.location.href = action.link;
                    return;
                }
                openModal(action.title);
            };
            const badgeHtml = action.badge && action.badge > 0
                ? `<div style="margin-top:10px; font-size:0.75rem; color: var(--pink); font-weight:700;">${action.badge} pending</div>`
                : '';

            card.innerHTML = `
                <div style="font-size: 3rem; margin-bottom: 15px;">${action.icon}</div>
                <div style="font-weight:bold; font-size: 1.2rem;">${action.title}</div>
                <p style="color: var(--muted); font-size: 0.9rem;">${action.desc}</p>
                ${badgeHtml}
            `;
            dashGrid.appendChild(card);
        });

        // Obsolete members query removed

        
        function openModal(title) {
            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-overlay').style.display = 'flex';
            document.getElementById('modal-body').innerHTML =
                "<input type='text' placeholder='Enter details' style='width:100%; padding:10px; background:var(--card); color:var(--text); border:1px solid var(--table-border); border-radius:6px;'>";
        }


    function closeModal(){
        document.getElementById('modal-overlay').style.display = 'none';
    }

    
    const profileMenu = document.getElementById('profile-menu');
    const profileTrigger = document.getElementById('profile-trigger');
    profileTrigger.addEventListener('click', function (event) {
        event.stopPropagation();
        profileMenu.classList.toggle('open');
        profileTrigger.setAttribute('aria-expanded', profileMenu.classList.contains('open') ? 'true' : 'false');
    });

    document.addEventListener('click', function (event) {
        if (!profileMenu.contains(event.target)) {
            profileMenu.classList.remove('open');
            profileTrigger.setAttribute('aria-expanded', 'false');
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            profileMenu.classList.remove('open');
            profileTrigger.setAttribute('aria-expanded', 'false');
        }
    });

    
    const themeSwitch = document.getElementById('theme-switch');
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'light') {
        document.body.classList.add('light-theme');
        themeSwitch.checked = true;
    }

    themeSwitch.addEventListener('change', function () {
        if (this.checked) {
            document.body.classList.add('light-theme');
            localStorage.setItem('theme', 'light');
        } else {
            document.body.classList.remove('light-theme');
            localStorage.setItem('theme', 'dark');
        }
    });

    // === NOTIFICATION SYSTEM ===
    const notifBell = document.getElementById('notif-bell');
    const notifDropdown = document.getElementById('notif-dropdown');
    const notifBadge = document.getElementById('notif-badge');
    const notifListEl = document.getElementById('notif-list');
    const notifMarkRead = document.getElementById('notif-mark-read');

    notifBell.addEventListener('click', function(e) {
        e.stopPropagation();
        notifDropdown.classList.toggle('open');
        // Close profile menu if open
        profileMenu.classList.remove('open');
        profileTrigger.setAttribute('aria-expanded', 'false');
    });

    document.addEventListener('click', function(e) {
        if (!document.getElementById('notif-wrapper').contains(e.target)) {
            notifDropdown.classList.remove('open');
        }
    });

    notifMarkRead.addEventListener('click', function() {
        fetch('fetch_notifications.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=mark_read'
        }).then(() => fetchNotifications());
    });

    function fetchNotifications() {
        fetch('fetch_notifications.php')
            .then(r => r.json())
            .then(data => {
                if (data.unread_count > 0) {
                    notifBadge.textContent = data.unread_count > 99 ? '99+' : data.unread_count;
                    notifBadge.classList.add('visible');
                } else {
                    notifBadge.classList.remove('visible');
                }

                if (!data.notifications || data.notifications.length === 0) {
                    notifListEl.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
                    return;
                }

                notifListEl.innerHTML = data.notifications.map(n => `
                    <div class="notif-item ${n.is_read ? 'read' : 'unread'}">
                        <div class="notif-dot"></div>
                        <div class="notif-body">
                            <p class="notif-msg">${escapeHtml(n.message)}</p>
                            <div class="notif-time">${n.time_ago}</div>
                        </div>
                    </div>
                `).join('');
            })
            .catch(() => {});
    }

    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }

    // Poll every 30 seconds
    fetchNotifications();
    setInterval(fetchNotifications, 30000);
    </script>
</body>
</html>
