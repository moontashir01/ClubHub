<?php
session_start();
include 'connection.php';



$email = $_SESSION['AdminEmail'] ?? "guest@guest.com";
$adminRole = $_SESSION['AdminRole'] ?? "Admin"; 

if ($adminRole === 'Student Affairs') {
    $name = 'Office of Student Affairs';
} elseif ($adminRole === 'Security') {
    $name = 'Campus Security Head';
} elseif ($adminRole === 'Registrar') {
    $name = 'University Registrar';
} else {
    $name = 'System Admin';
}

$initial = strtoupper(substr($name, 0, 1));

$totalClubs = 0;
$queryClubs = "SELECT COUNT(*) as count FROM clubs"; 
$resultClubs = @mysqli_query($con, $queryClubs);
if ($resultClubs) {
    $row = mysqli_fetch_assoc($resultClubs);
    $totalClubs = $row['count'];
}

$totalBookings = 0;
$queryBookings = "SELECT COUNT(*) as count FROM room_bookings";
$resultBookings = @mysqli_query($con, $queryBookings);
if ($resultBookings) {
    $row = mysqli_fetch_assoc($resultBookings);
    $totalBookings = $row['count'];
}

$totalVolunteers = 0;
$queryVolunteers = "SELECT COUNT(*) as count FROM volunteer_requests"; 
$resultVolunteers = @mysqli_query($con, $queryVolunteers);
if ($resultVolunteers) {
    $row = mysqli_fetch_assoc($resultVolunteers);
    $totalVolunteers = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Command Center | ClubHub</title>
    <style>
        :root {
            --bg: #090a0f;
            --surface: #13141f;
            --surface-hover: #1c1d2b;
            --primary: #ff477e;
            --primary-glow: rgba(255, 71, 126, 0.3);
            --text: #f1f5f9;
            --muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
        }
        
        body { margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; }

        .sidebar { width: 280px; background: var(--surface); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 25px; box-sizing: border-box; z-index: 10; }
        .logo { font-size: 1.8rem; font-weight: 800; color: var(--text); margin-bottom: 40px; display: flex; align-items: center; gap: 10px; letter-spacing: 1px; }
        .logo span { color: var(--primary); }

        .admin-profile { background: rgba(255,255,255,0.03); padding: 15px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 30px; display: flex; align-items: center; gap: 15px; transition: 0.3s; }
        .admin-profile:hover { border-color: var(--primary); background: rgba(255, 71, 126, 0.05); }
        .avatar { width: 45px; height: 45px; border-radius: 12px; background: linear-gradient(135deg, var(--primary), #ff9a9e); display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 1.2rem; }
        .admin-info h4 { margin: 0; font-size: 1rem; color: var(--text); }
        .admin-info p { margin: 0; font-size: 0.75rem; color: var(--primary); text-transform: uppercase; letter-spacing: 1px; font-weight: bold; margin-top: 4px; }

        .nav-label { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; font-weight: bold; }
        .logout-btn { margin-top: auto; padding: 15px; text-align: center; background: rgba(248, 113, 113, 0.1); color: #f87171; border-radius: 12px; text-decoration: none; font-weight: bold; transition: 0.3s; border: 1px solid transparent; }
        .logout-btn:hover { background: #f87171; color: white; box-shadow: 0 5px 15px rgba(248, 113, 113, 0.4); }

        .main-content { flex: 1; padding: 40px 50px; overflow-y: auto; position: relative; }
        
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 40px; }
        .date-display { color: var(--muted); font-size: 0.9rem; background: var(--surface); padding: 10px 20px; border-radius: 20px; border: 1px solid var(--border); }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 40px; }
        .stat-card { background: var(--surface); border: 1px solid var(--border); padding: 25px; border-radius: 20px; position: relative; overflow: hidden; display: flex; align-items: center; gap: 20px; }
        .stat-card::before { content: ''; position: absolute; left: 0; top: 0; height: 100%; width: 4px; background: var(--primary); border-radius: 4px 0 0 4px; }
        .stat-icon { font-size: 2.5rem; opacity: 0.8; }
        .stat-details h3 { margin: 0; font-size: 2rem; font-weight: 800; color: var(--text); }
        .stat-details p { margin: 0; font-size: 0.85rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-top: 5px; }

        .action-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
        .action-card { background: rgba(19, 20, 31, 0.6); backdrop-filter: blur(12px); border: 1px solid var(--border); border-radius: 24px; padding: 35px 30px; cursor: pointer; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative; overflow: hidden; display: flex; flex-direction: column; align-items: flex-start; }
        .action-card:hover { transform: translateY(-8px); border-color: var(--primary); box-shadow: 0 15px 35px var(--primary-glow); background: var(--surface-hover); }
        .card-icon { font-size: 2.5rem; margin-bottom: 20px; padding: 15px; background: rgba(255,255,255,0.03); border-radius: 16px; border: 1px solid var(--border); transition: 0.3s; }
        .action-card:hover .card-icon { background: var(--primary); color: white; border-color: var(--primary); transform: scale(1.1); }
        .action-card h3 { margin: 0 0 10px 0; font-size: 1.3rem; font-weight: 700; }
        .action-card p { margin: 0; color: var(--muted); font-size: 0.95rem; line-height: 1.5; }

        .action-card.restricted { cursor: not-allowed; border-color: rgba(255,255,255,0.02); }
        .action-card.restricted:hover { transform: none; box-shadow: none; border-color: rgba(255,255,255,0.05); }
        .lock-overlay { position: absolute; inset: 0; background: rgba(9, 10, 15, 0.8); backdrop-filter: blur(4px); display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: 0.3s ease; border-radius: 24px; }
        .action-card.restricted:hover .lock-overlay { opacity: 1; }
        .lock-overlay span { font-size: 3rem; margin-bottom: 10px; }
        .lock-overlay p { color: #f87171; font-weight: bold; font-size: 0.9rem; text-align: center; padding: 0 20px; }

        .modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.85); backdrop-filter: blur(8px); z-index: 1000; align-items: center; justify-content: center; }
        .modal-content { background: var(--surface); border: 1px solid var(--border); border-radius: 24px; padding: 40px; width: 100%; max-width: 500px; position: relative; box-shadow: 0 25px 50px rgba(0,0,0,0.5); }
        .modal-close { position: absolute; top: 20px; right: 25px; background: none; border: none; color: var(--muted); font-size: 1.8rem; cursor: pointer; transition: 0.2s; }
        .modal-close:hover { color: var(--primary); }
        .modal-title { margin-top: 0; color: white; font-size: 1.5rem; margin-bottom: 25px; border-bottom: 1px solid var(--border); padding-bottom: 15px; }
        
        .admin-form-group { margin-bottom: 20px; text-align: left; }
        .admin-form-group label { display: block; font-size: 0.8rem; color: var(--muted); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px; font-weight: bold;}
        .admin-input { width: 100%; padding: 14px; background: var(--bg); color: var(--text); border: 1px solid var(--border); border-radius: 10px; box-sizing: border-box; font-family: inherit; font-size: 1rem; transition: 0.3s; }
        .admin-input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(255, 71, 126, 0.2); }
        .submit-btn { width: 100%; background: var(--primary); border: none; padding: 15px; border-radius: 10px; color: white; font-weight: bold; font-size: 1rem; cursor: pointer; transition: 0.3s; margin-top: 10px; }
        .submit-btn:hover { background: #e63e70; box-shadow: 0 5px 15px var(--primary-glow); transform: translateY(-2px); }

        /* === NOTIFICATION BELL === */
        .notif-wrapper { position: relative; }
        .notif-bell {
            background: var(--surface); border: 1px solid var(--border); border-radius: 14px;
            width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;
            cursor: pointer; position: relative; transition: all 0.3s ease; font-size: 1.3rem;
        }
        .notif-bell:hover { border-color: var(--primary); background: var(--surface-hover); transform: scale(1.08); }
        .notif-badge {
            position: absolute; top: -5px; right: -5px;
            background: var(--primary); color: white; font-size: 0.65rem; font-weight: 800;
            min-width: 18px; height: 18px; border-radius: 99px;
            display: none; align-items: center; justify-content: center;
            padding: 0 4px; box-shadow: 0 2px 8px var(--primary-glow);
            animation: badgePulse 2s ease-in-out infinite;
        }
        .notif-badge.visible { display: flex; }
        @keyframes badgePulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }
        .notif-dropdown {
            position: absolute; top: calc(100% + 12px); right: 0;
            width: 380px; max-height: 440px; overflow-y: auto;
            background: rgba(19, 20, 31, 0.95); backdrop-filter: blur(20px);
            border: 1px solid var(--border); border-radius: 18px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
            display: none; z-index: 500;
        }
        .notif-dropdown.open { display: block; animation: dropIn 0.25s ease-out; }
        @keyframes dropIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .notif-header {
            padding: 16px 20px; border-bottom: 1px solid var(--border);
            display: flex; justify-content: space-between; align-items: center;
        }
        .notif-header h4 { margin: 0; font-size: 0.95rem; color: var(--text); }
        .notif-mark-read {
            background: none; border: none; color: var(--primary); font-size: 0.78rem;
            cursor: pointer; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .notif-mark-read:hover { text-decoration: underline; }
        .notif-list { padding: 6px 0; }
        .notif-item {
            padding: 14px 20px; display: flex; gap: 12px; align-items: flex-start;
            transition: background 0.2s; cursor: pointer; border-left: 3px solid transparent;
        }
        .notif-item:hover { background: rgba(255,255,255,0.03); }
        .notif-item.unread { border-left-color: var(--primary); background: rgba(255, 71, 126, 0.04); }
        .notif-dot { width: 8px; height: 8px; border-radius: 50%; background: var(--primary); flex-shrink: 0; margin-top: 6px; }
        .notif-item.read .notif-dot { background: var(--border); }
        .notif-body { flex: 1; min-width: 0; }
        .notif-msg { font-size: 0.88rem; color: var(--text); line-height: 1.45; margin: 0; }
        .notif-time { font-size: 0.72rem; color: var(--muted); margin-top: 4px; }
        .notif-empty { padding: 30px 20px; text-align: center; color: var(--muted); font-size: 0.88rem; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo">ClubHub <span>Admin</span></div>
        
        <div class="admin-profile">
            <div class="avatar"><?php echo $initial; ?></div>
            <div class="admin-info">
                <h4><?php echo htmlspecialchars($name); ?></h4>
                <p><?php echo htmlspecialchars($adminRole); ?></p>
            </div>
        </div>

        <div class="nav-label">Main Menu</div>
        <div style="color: var(--primary); font-weight: bold; margin-bottom: 15px; padding: 10px; background: rgba(255, 71, 126, 0.1); border-radius: 8px;">Dashboard</div>
        <div style="color: var(--muted); margin-bottom: 15px; padding: 10px; cursor: pointer;">System Logs</div>
        <div style="color: var(--muted); margin-bottom: 15px; padding: 10px; cursor: pointer;">Settings</div>

        <a href="logout.php" class="logout-btn">Secure Logout</a>
    </aside>

    <main class="main-content">
        <div class="header">
            <div>
                <h1 style="margin: 0; font-size: 2.2rem; font-weight: 700; background: linear-gradient(to right, #fff, #aaa); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">Command Center</h1>
                <p style="margin: 5px 0 0 0; color: var(--primary); font-weight: bold; letter-spacing: 1px; text-transform: uppercase; font-size: 0.85rem;">
                    🟢 Logged in as: <?php echo htmlspecialchars($name); ?>
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 14px;">
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
                <div class="date-display" id="current-date"></div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📚</div>
                <div class="stat-details">
                    <h3><?php echo $totalClubs; ?></h3>
                    <p>Active Clubs</p>
                </div>
            </div>
            <div class="stat-card" style="--primary: #4ade80;">
                <div class="stat-icon">📅</div>
                <div class="stat-details">
                    <h3><?php echo $totalBookings; ?></h3>
                    <p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card" style="--primary: #60a5fa;">
                <div class="stat-icon">🙋‍♂️</div>
                <div class="stat-details">
                    <h3><?php echo $totalVolunteers; ?></h3>
                    <p>Volunteer Requests</p>
                </div>
            </div>
        </div>

        <h2 style="font-size: 1.2rem; color: var(--muted); margin-bottom: 20px; border-bottom: 1px solid var(--border); padding-bottom: 10px;">Administrative Modules</h2>
        
        <div class="action-grid" id="dashboard-grid">
            <div class="action-card" onclick="window.location.href='reqVolunteer.php'">
                <div class="card-icon">🙋‍♂️</div>
                <h3>Request Volunteers</h3>
                <p>Ask clubs to send volunteers for an event.</p>
            </div>
        </div>
    </main>

    <div class="modal-overlay" id="modal-overlay">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()">&times;</button>
            <h2 class="modal-title" id="modal-title">Action</h2>
            <div id="modal-body"></div>
        </div>
    </div>

    <script>
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').innerText = new Date().toLocaleDateString('en-US', options);

        const currentAdminRole = "<?php echo addslashes($adminRole); ?>";

        const dashboardActions = [
            { 
                title: 'Club Approvals', 
                icon: '✅', 
                desc: 'Review and approve new club registration requests.', 
                allowedRoles: ['Student Affairs'] 
            },
            { 
                title: 'Room Bookings', 
                icon: '🏢', 
                desc: 'Review, approve, or reject club room requests across campus.', 
                allowedRoles: ['Student Affairs', 'Registrar'] 
            },
            { 
                title: 'Security Clearance', 
                icon: '🛡️', 
                desc: 'Review event details and issue security clearance protocols.', 
                allowedRoles: ['Student Affairs', 'Registrar', 'Security'],
                link: 'securityapproval.php' 
            },
            { 
                title: 'Academic Records', 
                icon: '🎓', 
                desc: 'Access student academic standing for club executive eligibility.', 
                allowedRoles: ['Registrar'] 
            }
        ];

        const dashGrid = document.getElementById('dashboard-grid');

        dashboardActions.forEach(action => {
            const isPermitted = action.title === 'Security Clearance' || action.allowedRoles.includes(currentAdminRole);
            const card = document.createElement('div');
            
            card.className = `action-card ${isPermitted ? '' : 'restricted'}`;
            
            card.onclick = () => {
                if (!isPermitted) return;
                if (action.link) {
                    window.location.href = action.link;
                    return;
                }
                openModal(action.title);
            };

            let cardHTML = `
                <div class="card-icon">${action.icon}</div>
                <h3>${action.title}</h3>
                <p>${action.desc}</p>
            `;
            
            if(!isPermitted) {
                cardHTML += `
                    <div class="lock-overlay">
                        <span>&#128274;</span>
                        <p>Restricted to:<br>${action.allowedRoles.join(" & ")}</p>
                    </div>
                `;
            }

            card.innerHTML = cardHTML;
            dashGrid.appendChild(card);
        });

        function openModal(title) {
            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-overlay').style.display = 'flex';
            const modalBody = document.getElementById('modal-body');

            if (title === "Club Approvals") {
                modalBody.innerHTML = `
                    <form id="volunteerForm">
                        <div class="admin-form-group">
                            <label>Target Club</label>
                            <select class="admin-input" required>
                                <option value="">-- Select a Club --</option>
                                <option value="Computer Club">Computer Club</option>
                                <option value="Debate Club">Debate Club</option>
                            </select>
                        </div>
                        <div class="admin-form-group">
                            <label>Event Name</label>
                            <input type="text" class="admin-input" placeholder="e.g., Fall Orientation 2026" required>
                        </div>
                        <div style="display:flex; gap:15px;">
                            <div class="admin-form-group" style="flex:1;">
                                <label>Date</label>
                                <input type="date" class="admin-input" required>
                            </div>
                            <div class="admin-form-group" style="flex:1;">
                                <label>Volunteers</label>
                                <input type="number" class="admin-input" placeholder="Count" min="1" required>
                            </div>
                        </div>
                        <div class="admin-form-group">
                            <label>Job Description</label>
                            <textarea class="admin-input" rows="3" placeholder="What will they be doing?" required></textarea>
                        </div>
                        <button type="submit" class="submit-btn">Send Request</button>
                    </form>
                `;
            } else {
                modalBody.innerHTML = `<p style="color:var(--muted); text-align:center; padding: 20px 0;">System module for <b>${title}</b> is currently initializing...</p>`;
            }
        }

        function closeModal(){
            document.getElementById('modal-overlay').style.display = 'none';
        }

        window.onclick = function(event) {
            const overlay = document.getElementById('modal-overlay');
            if (event.target == overlay) {
                closeModal();
            }
        }

        // === NOTIFICATION SYSTEM ===
        const notifBell = document.getElementById('notif-bell');
        const notifDropdown = document.getElementById('notif-dropdown');
        const notifBadge = document.getElementById('notif-badge');
        const notifList = document.getElementById('notif-list');
        const notifMarkRead = document.getElementById('notif-mark-read');

        notifBell.addEventListener('click', function(e) {
            e.stopPropagation();
            notifDropdown.classList.toggle('open');
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
                        notifList.innerHTML = '<div class="notif-empty">No notifications yet.</div>';
                        return;
                    }

                    notifList.innerHTML = data.notifications.map(n => `
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
