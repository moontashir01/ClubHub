<?php
session_start();
include 'connection.php';
$db_users = [
    ["name" => "Joy Saha", "id" => "2312466", "dept" => "ECE", "role" => "Executive"],
    ["name" => "Rahim Uddin", "id" => "CH-2026-894", "dept" => "Robotics", "role" => "Volunteer"]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional ID Studio | ClubHub</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-dark: #0d1117;
            --panel-dark: #161b22;
            --accent: #3b82f6;
            --text-main: #f0f6fc;
        }

        body { margin: 0; background: var(--bg-dark); font-family: 'Segoe UI', Roboto, sans-serif; color: var(--text-main); display: flex; height: 100vh; overflow: hidden; }

        /* Sidebar Editor */
        .sidebar { width: 350px; background: var(--panel-dark); padding: 20px; border-right: 1px solid #30363d; overflow-y: auto; }
        
        /* --- New: Back to Dashboard Button --- */
        .back-dashboard-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: #8b949e;
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 20px;
            padding: 8px 12px;
            border-radius: 6px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid #30363d;
            transition: 0.3s;
            width: fit-content;
        }
        .back-dashboard-btn:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(-3px);
            border-color: #8b949e;
        }

        .sidebar h2 { font-size: 1.2rem; margin-top: 0; margin-bottom: 20px; color: var(--accent); border-bottom: 1px solid #30363d; padding-bottom: 10px; }

        .control-group { margin-bottom: 15px; }
        .control-group label { display: block; font-size: 0.85rem; color: #8b949e; margin-bottom: 5px; }
        .control-group input, .control-group select { width: 100%; padding: 10px; background: #010409; border: 1px solid #30363d; border-radius: 6px; color: white; box-sizing: border-box; }

        /* Style Presets */
        .style-picker { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .style-option { 
            padding: 10px; border: 1px solid #30363d; border-radius: 6px; cursor: pointer; text-align: center; font-size: 0.8rem; transition: 0.3s;
        }
        .style-option:hover, .style-option.active { border-color: var(--accent); background: rgba(59, 130, 246, 0.1); }

        /* Preview Area */
        .main-preview { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #010409; }

        /* =============================
           PROFESSIONAL CARD STYLES
           ============================= */
        #id-card-wrap {
            width: 320px; height: 500px;
            background: #fff; border-radius: 12px;
            position: relative; overflow: hidden;
            box-shadow: 0 30px 60px rgba(0,0,0,0.5);
            color: #1a1a1a;
        }

        /* --- Style 1: Modern Corporate --- */
        .style-corporate .card-header { height: 150px; background: var(--accent); clip-path: polygon(0 0, 100% 0, 100% 80%, 0 100%); }
        .style-corporate .photo-border { width: 120px; height: 120px; background: white; border-radius: 50%; margin: -80px auto 0; position: relative; padding: 5px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

        /* --- Style 2: Tech Gradient --- */
        .style-tech .card-header { height: 180px; background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%); position: relative; }
        .style-tech .card-header::after { content: ''; position: absolute; bottom: 0; left: 0; right: 0; height: 50px; background: white; clip-path: polygon(0 100%, 100% 0, 100% 100%); }
        .style-tech .photo-border { width: 110px; height: 110px; border-radius: 15px; background: white; margin: -100px auto 0; position: relative; padding: 5px; transform: rotate(-3deg); }

        /* --- Style 3: Elegant Border --- */
        .style-elegant { border: 8px solid var(--accent); box-sizing: border-box; }
        .style-elegant .card-header { padding-top: 30px; text-align: center; background: transparent; color: #1a1a1a; }
        .style-elegant .photo-border { width: 130px; height: 130px; border-radius: 50%; border: 3px solid var(--accent); margin: 20px auto; padding: 3px; }

        /* Inner Content */
        .photo-inner { width: 100%; height: 100%; border-radius: inherit; overflow: hidden; background: #f0f0f0; }
        .photo-inner img { width: 100%; height: 100%; object-fit: cover; }
        
        .card-content { text-align: center; padding: 20px; }
        .card-content h1 { margin: 10px 0 5px; font-size: 1.5rem; color: #111; text-transform: uppercase; }
        .role-badge { display: inline-block; padding: 4px 15px; border-radius: 50px; background: var(--accent); color: white; font-size: 0.75rem; font-weight: bold; }

        .info-table { width: 85%; margin: 25px auto; border-top: 1px solid #eee; padding-top: 15px; }
        .row { display: flex; justify-content: space-between; font-size: 0.8rem; margin-bottom: 8px; }
        .label { color: #666; font-weight: 600; }
        .val { font-weight: 700; color: #111; }

        .footer-logo { position: absolute; bottom: 20px; width: 100%; text-align: center; font-size: 0.7rem; color: #999; letter-spacing: 2px; }

        /* Download Btn */
        .btn-download { margin-top: 30px; padding: 12px 40px; background: var(--accent); color: white; border: none; border-radius: 50px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .btn-download:hover { transform: scale(1.05); background: #2563eb; }
    </style>
</head>
<body class="style-corporate"> 
    <div class="sidebar">
        <a href="Club_dashboard.php" class="back-dashboard-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <h2>ID Card Studio</h2>
        
        <label style="font-size: 0.8rem; color: #8b949e;">Select Card Style</label>
        <div class="style-picker">
            <div class="style-option active" onclick="setStyle('corporate', this)">Corporate</div>
            <div class="style-option" onclick="setStyle('tech', this)">Tech Edge</div>
            <div class="style-option" onclick="setStyle('elegant', this)">Elegant</div>
        </div>

        <div class="control-group">
            <label>Theme Color</label>
            <input type="color" value="#3b82f6" oninput="updateColor(this.value)">
        </div>

        <div class="control-group">
            <label>Profile Picture</label>
            <input type="file" accept="image/*" onchange="previewImg(event)">
        </div>

        <div class="control-group">
            <label>Full Name</label>
            <input type="text" id="in-name" list="user-list" oninput="syncData()">
            <datalist id="user-list">
                <?php foreach($db_users as $u): ?>
                    <option value="<?= $u['name'] ?>" data-id="<?= $u['id'] ?>" data-dept="<?= $u['dept'] ?>" data-role="<?= $u['role'] ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="control-group">
            <label>ID Number</label>
            <input type="text" id="in-id" oninput="syncData()">
        </div>

        <div class="control-group">
            <label>Department</label>
            <input type="text" id="in-dept" oninput="syncData()">
        </div>

        <div class="control-group">
            <label>Designation</label>
            <input type="text" id="in-role" oninput="syncData()">
        </div>

        <div class="control-group">
            <label>Valid Till</label>
            <input type="date" id="in-valid" oninput="syncData()">
        </div>
    </div>

    <div class="main-preview">
        <div id="id-card-wrap">
            <div class="card-header" id="header-bg" style="background-color: var(--accent);">
                <div style="color:white; text-align:center; padding-top:20px; font-weight:800; font-size:1.1rem;">CLUBHUB SYSTEM</div>
                <div style="color:rgba(255,255,255,0.8); text-align:center; font-size:0.6rem; letter-spacing:2px;">OFFICIAL IDENTITY</div>
            </div>

            <div class="photo-border">
                <div class="photo-inner">
                    <img id="out-photo" src="https://ui-avatars.com/api/?name=User&background=random">
                </div>
            </div>

            <div class="card-content">
                <h1 id="out-name">MEMBER NAME</h1>
                <div class="role-badge" id="out-role">DESIGNATION</div>

                <div class="info-table">
                    <div class="row">
                        <span class="label">ID NO:</span>
                        <span class="val" id="out-id">CH-000-000</span>
                    </div>
                    <div class="row">
                        <span class="label">CLUB/DEPT:</span>
                        <span class="val" id="out-dept">DEPARTMENT</span>
                    </div>
                    <div class="row">
                        <span class="label">VALID UNTIL:</span>
                        <span class="val" id="out-valid">DEC 2026</span>
                    </div>
                </div>
            </div>

            <div class="footer-logo">||| || | || ||| | |||</div>
        </div>

        <button class="btn-download" onclick="exportPDF()">
            <i class="fas fa-download"></i> EXPORT HD PDF
        </button>
    </div>

    <script>
        // ১. স্টাইল পরিবর্তন
        function setStyle(styleName, el) {
            document.body.className = 'style-' + styleName;
            document.querySelectorAll('.style-option').forEach(opt => opt.classList.remove('active'));
            el.classList.add('active');
        }

        // ২. কালার আপডেট
        function updateColor(color) {
            document.documentElement.style.setProperty('--accent', color);
            // Elegant স্টাইলের জন্য হেডার ব্যাকগ্রাউন্ড সরাতে হয়
            if(!document.body.classList.contains('style-elegant')) {
                document.getElementById('header-bg').style.backgroundColor = color;
            }
        }

        // ৩. ইমেজ প্রিভিউ
        function previewImg(event) {
            const reader = new FileReader();
            reader.onload = () => document.getElementById('out-photo').src = reader.result;
            reader.readAsDataURL(event.target.files[0]);
        }

        // ৪. ডাটা সিঙ্ক এবং সাজেশন
        function syncData() {
            const nameIn = document.getElementById('in-name');
            const val = nameIn.value;
            
            // অটো সাজেশন চেক
            const options = document.querySelectorAll('#user-list option');
            options.forEach(opt => {
                if(opt.value === val) {
                    document.getElementById('in-id').value = opt.getAttribute('data-id');
                    document.getElementById('in-dept').value = opt.getAttribute('data-dept');
                    document.getElementById('in-role').value = opt.getAttribute('data-role');
                }
            });

            document.getElementById('out-name').innerText = val || "MEMBER NAME";
            document.getElementById('out-id').innerText = document.getElementById('in-id').value || "CH-000-000";
            document.getElementById('out-dept').innerText = document.getElementById('in-dept').value || "DEPARTMENT";
            document.getElementById('out-role').innerText = document.getElementById('in-role').value || "DESIGNATION";
            
            const date = document.getElementById('in-valid').value;
            document.getElementById('out-valid').innerText = date ? new Date(date).toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'}) : "DEC 2026";
        }

        // ৫. প্রফেশনাল পিডিএফ এক্সপোর্ট
        function exportPDF() {
            const element = document.getElementById('id-card-wrap');
            const name = document.getElementById('in-name').value || "Member";
            
            const opt = {
                margin: 0,
                filename: `ID_${name}.pdf`,
                image: { type: 'jpeg', quality: 1 },
                html2canvas: { scale: 4, useCORS: true },
                jsPDF: { unit: 'mm', format: [85, 135], orientation: 'portrait' }
            };

            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>