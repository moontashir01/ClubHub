<?php
session_start();
include 'connection.php';
$email=$_SESSION['Email'];
$name = $_SESSION['Name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubHub | Dashboard</title>
    <link rel="stylesheet" href="styles.css?v=<?php echo filemtime('styles.css'); ?>">
    
    <style>
        nav {
            position: sticky;
            top: 0;
            background-color: #0b0f19;
            z-index: 1000;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 30px;
        }
        .dashboard-section {
            padding-top: 40px;
            position: relative;
            z-index: 1;
        }
        .portal-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .portal-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.4);
        }
        .badge{display:inline-block;padding:4px 10px;border-radius:12px;font-size:0.75rem;font-weight:600;}
        .badge-pending{background:#eab30820;color:#eab308;border:1px solid #eab30850;}
        .badge-approved{background:#22c55e20;color:#22c55e;border:1px solid #22c55e50;}
        .badge-rejected{background:#ef444420;color:#ef4444;border:1px solid #ef444450;}
    </style>
</head>
<body>
    <nav>
        <div class="logo" style="font-weight: bold; font-size: 1.5rem;">ClubHub</div>
        <?php
            $displayName = isset($_SESSION['Name']) ? $_SESSION['Name'] : null;
            if (!$displayName && isset($_SESSION['Email'])) {
                $displayName = explode('@', $_SESSION['Email'])[0];
            }
            if (!$displayName) {
                $displayName = "Guest";
            }
        ?>
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
    </nav>
    <div class="hero-section" id="hero-slider"></div>
    <section class="dashboard-section" id="dashboard">
        <div class="header">Club Management Dashboard</div>
        <div class="portal-grid" id="dashboard-grid"></div>
    </section>
    <div id="modal-overlay">
        <div class="modal-content">
            <h2 id="modal-title">Action</h2>
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
            die(mysqli_error($con));
        }
        $row = mysqli_fetch_assoc($query);
        $clubName = $row ? $row['club_name'] : "No Club Found";

        // Fetch VC Applications (kept for future use)
        $vcApps = mysqli_query($con, "SELECT id, subject, status, created_at FROM vc_applications WHERE club_name = '".mysqli_real_escape_string($con,$clubName)."' ORDER BY id DESC");
        $appData = [];
        while($a = mysqli_fetch_assoc($vcApps)) {
            $appData[] = ['id'=>$a['id'],'subject'=>htmlspecialchars($a['subject']),'status'=>htmlspecialchars($a['status']),'date'=>$a['created_at']??''];
        }
    ?>
        const contentData = [
            { title: 'Welcome to <?php echo htmlspecialchars($clubName); ?> ', desc: 'Manage your club operations effectively.', img: 'images/club_dashboard_cover.jpg' }
        ];
        
       
        const dashboardActions = [
            { title: 'View Members', icon: '👥', desc: 'View Club Members' },
            { title: 'Send Volunteers', icon: '🚀', desc: 'Dispatch members for events.' },
            { title: 'Add Members', icon: '➕', desc: 'Register new members.' },
            { title: 'Room Booking', icon: '🏢', desc: 'Book a room for your event.', link: 'room_booking.php' },
            { title: 'Room Booking Status', icon: '⏳', desc: 'View status of room booking.', link: 'approved_rooms.php' }, 
            { title: 'Application Status', icon: '📄', desc: 'Check your application status.', link: 'application_status.php' },
            { title: 'VC Approval Desk', icon: '📜', desc: 'Draft and send official requests to VC.', link: 'vc_application.php' },
            { title: 'Create ID card', icon: '🪪', desc: 'Create an ID card for your upcomming event.', link: 'generate_id.php' },
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
            
            if(action.link) {
                card.onclick = () => window.location.href = action.link;
            } else {
                card.onclick = () => openModal(action.title);
            }
            card.innerHTML = `
                <div style="font-size: 3rem; margin-bottom: 15px;">${action.icon}</div>
                <div style="font-weight:bold; font-size: 1.2rem;">${action.title}</div>
                <p style="color: var(--muted); font-size: 0.9rem;">${action.desc}</p>
            `;
            dashGrid.appendChild(card);
        });

        <?php
            $clubMembers = mysqli_query($con,"
            SELECT club_members.student_id as 'SID',
                    students.full_name as 'name',
                    club_members.Role as 'role'
            FROM `club_members` INNER JOIN `clubs`
            ON club_members.club_id = clubs.club_id
            INNER JOIN students
            ON club_members.student_id = students.student_id
            WHERE clubs.club_name = '$clubName' AND club_members.active = 1
            ");
        ?>

        function openModal(title) {
            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-overlay').style.display = 'flex';
            
            if (title === "View Members") {
                document.getElementById('modal-body').innerHTML = `
                    <style>
                        .table-container { max-height: 300px; overflow-y: auto; margin-top: 15px; border: 1px solid var(--table-border); }
                        .member-table { width: 100%; border-collapse: collapse; color: var(--text); font-size: 0.9rem; }
                        .member-table th { position: sticky; top: 0; background: var(--card); padding: 12px; border-bottom: 2px solid var(--pink); color: var(--pink); text-align: left; }
                        .member-table td { padding: 10px; border-bottom: 1px solid var(--table-row-border); }
                        .table-container::-webkit-scrollbar { width: 6px; }
                        .table-container::-webkit-scrollbar-thumb { background: var(--pink); border-radius: 10px; }
                    </style>
                    <div class="table-container">
                        <table class="member-table">
                            <thead><tr><th>ID</th><th>Name</th><th>Role</th></tr></thead>
                            <tbody>
                                <?php
                                mysqli_data_seek($clubMembers, 0);
                                while($row = mysqli_fetch_assoc($clubMembers)) {
                                    echo "<tr>";
                                    echo "<td>".htmlspecialchars($row['SID'])."</td>";
                                    echo "<td>".htmlspecialchars($row['name'])."</td>";
                                    echo "<td>".htmlspecialchars($row['role'])."</td>";
                                    echo "</tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                document.getElementById('modal-body').innerHTML = "<input type='text' placeholder='Enter details' style='width:100%; padding:10px; background:var(--card); color:var(--text); border:1px solid var(--table-border); border-radius:6px;'>";
            }
        }
        function closeModal(){
            document.getElementById('modal-overlay').style.display = 'none';
        }

        // Profile & Theme code (unchanged)
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
    </script>
</body>
</html>