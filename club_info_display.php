<?php
session_start();
include 'connection.php';
mysqli_set_charset($con, "utf8mb4");

$club_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$query = mysqli_query($con, "SELECT club_name, club_data FROM clubs WHERE club_id = $club_id");
$row = mysqli_fetch_assoc($query);

if (!$row) {
    die("<body style='background:#0b0b13; color:white; display:flex; align-items:center; justify-content:center; height:100vh; font-family:sans-serif; margin:0;'>
            <div style='text-align:center;'>
                <h2>Club not found.</h2>
                <a href='User_dashboard_.php' style='color:#ff4d8d; text-decoration:none; border:1px solid #ff4d8d; padding:10px 20px; border-radius:20px;'>Return to Portal</a>
            </div>
         </body>");
}

$club_name = $row['club_name'];
$data = !empty($row['club_data']) ? json_decode($row['club_data'], true) : [];

// Extract data
$info = $data['info'] ?? '';
$events = $data['events'] ?? '';
$panels = $data['panel'] ?? [];
$gallery = $data['gallery'] ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($club_name) ?> | Club Hub</title>
    <style>
        :root { 
            --pink: #ff4d8d; 
            --pink-glow: rgba(255, 77, 141, 0.4);
            --dark-bg: #07070b; /* Slightly darker for a richer contrast */
            --card: rgba(255, 255, 255, 0.02); 
            --text-main: #f1f5f9;
            --text-muted: #94a3b8;
        }
        
        body { 
            margin: 0; 
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif; 
            background: var(--dark-bg); 
            color: var(--text-main); 
            overflow-x: hidden; 
            padding-bottom: 120px; 
        }

        /* --- NAVBAR --- */
        nav { 
            display: flex; justify-content: space-between; padding: 20px 5%; 
            background: rgba(7, 7, 11, 0.7); 
            backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000; 
            align-items: center; border-bottom: 1px solid rgba(255,255,255,0.03);
        }
        .logo { font-size: 24px; font-weight: 900; letter-spacing: -1px; color: white; }
        .nav-btn { 
            background: transparent; border: 1px solid rgba(255,255,255,0.2);
            padding: 10px 24px; border-radius: 30px; font-weight: 600; color: white; 
            text-decoration: none; transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-size: 14px; 
        }
        .nav-btn:hover { background: var(--pink); border-color: var(--pink); box-shadow: 0 0 20px var(--pink-glow); }

        /* --- HERO SECTION --- */
        .hero { 
            position: relative; padding: 200px 5% 100px 5%; text-align: center; 
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }
        /* Breathing neon orb */
        .hero::before {
            content: ''; position: absolute; top: 20%; left: 50%; transform: translate(-50%, -50%);
            width: 50vw; height: 50vw; max-width: 600px; max-height: 600px;
            background: radial-gradient(circle, var(--pink-glow) 0%, transparent 60%);
            filter: blur(60px); z-index: -1; pointer-events: none;
            animation: pulseGlow 6s infinite alternate;
        }
        @keyframes pulseGlow { 0% { opacity: 0.5; transform: translate(-50%, -50%) scale(0.9); } 100% { opacity: 0.8; transform: translate(-50%, -50%) scale(1.1); } }
        
        .hero-badge {
            background: rgba(255, 77, 141, 0.1); border: 1px solid rgba(255, 77, 141, 0.3);
            color: var(--pink); padding: 6px 16px; border-radius: 20px;
            font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px;
            margin-bottom: 20px; display: inline-block;
        }
        h1 { 
            font-size: clamp(3rem, 7vw, 6rem); margin: 0; font-weight: 900; 
            color: white; text-transform: uppercase; letter-spacing: -1px; line-height: 1.1;
        }

        /* --- WIDER LAYOUT CONTAINER --- */
        .container { max-width: 1200px; margin: 0 auto; padding: 0 5%; }
        
        /* --- TEXT SECTIONS --- */
        .text-section { margin-bottom: 80px; }
        .section-title { 
            font-size: 1.2rem; font-weight: 700; text-transform: uppercase; color: var(--pink);
            letter-spacing: 3px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;
        }
        .section-title::after {
            content: ''; height: 1px; flex-grow: 1;
            background: linear-gradient(90deg, rgba(255,77,141,0.3), transparent);
        }
        
        .content-body {
            position: relative; padding-left: 25px;
            font-size: 1.2rem; line-height: 1.8; color: var(--text-muted);
        }
        /* Sleek glowing left border */
        .content-body::before {
            content: ''; position: absolute; left: 0; top: 5px; bottom: 5px; width: 3px;
            background: linear-gradient(to bottom, var(--pink), transparent); border-radius: 3px;
        }
        .content-body b, .content-body strong { color: var(--text-main); font-weight: 700; }
        .content-body u { text-decoration-color: var(--pink); text-decoration-thickness: 2px; text-underline-offset: 4px; }

        /* --- CINEMATIC SLIDER (Perfectly Proportioned) --- */
        .slider-container {
            position: relative; max-width: 900px; height: 450px; 
            margin: 40px auto 100px auto; /* Centered, placed right after events */
            border-radius: 24px; overflow: hidden; 
            box-shadow: 0 30px 60px rgba(0,0,0,0.6); background: #000;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .slider-track { display: flex; height: 100%; transition: transform 0.6s cubic-bezier(0.25, 1, 0.5, 1); }
        .slide { min-width: 100%; height: 100%; }
        .slide img { width: 100%; height: 100%; object-fit: cover; display: block; }
        
        /* Floating Glass Arrows */
        .arrow {
            position: absolute; top: 50%; transform: translateY(-50%);
            background: rgba(11, 11, 19, 0.5); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);
            color: white; border: 1px solid rgba(255,255,255,0.1);
            width: 50px; height: 50px; border-radius: 50%; font-size: 18px;
            cursor: pointer; display: flex; align-items: center; justify-content: center;
            transition: all 0.3s; z-index: 10;
        }
        .arrow:hover { background: var(--pink); border-color: var(--pink); transform: translateY(-50%) scale(1.1); }
        .arrow-left { left: 20px; }
        .arrow-right { right: 20px; }

        /* --- EXECUTIVES (Bottom) --- */
        .exec-grid { 
            display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); 
            gap: 25px; margin-top: 40px;
        }
        .exec-card { 
            background: var(--card); border: 1px solid rgba(255,255,255,0.03);
            padding: 40px 20px; border-radius: 20px; text-align: center; 
            transition: all 0.4s cubic-bezier(0.25, 1, 0.5, 1); 
            backdrop-filter: blur(10px);
        }
        .exec-card:hover { 
            transform: translateY(-8px); 
            border-color: rgba(255, 77, 141, 0.4); background: rgba(255, 255, 255, 0.04);
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }
        .dp-wrapper {
            width: 110px; height: 110px; margin: 0 auto 20px auto;
            border-radius: 50%; padding: 3px;
            background: linear-gradient(135deg, var(--pink), transparent);
        }
        .dp-img { 
            width: 100%; height: 100%; border-radius: 50%; 
            object-fit: cover; background: var(--dark-bg); border: 3px solid var(--dark-bg);
        }
        .exec-name { font-size: 1.25rem; font-weight: 800; margin-bottom: 6px; color: white; letter-spacing: -0.5px; }
        .exec-role { color: var(--pink); font-size: 0.8rem; font-weight: 700; text-transform: uppercase; letter-spacing: 2px; }

        @media (max-width: 768px) {
            .slider-container { height: 300px; border-radius: 16px; }
            .hero { padding: 150px 5% 60px 5%; }
            .arrow { width: 40px; height: 40px; font-size: 14px; }
        }
    </style>
</head>
<body>

    <nav>
        <div class="logo">Club Hub</div>
        <a href="dashboard.php" class="nav-btn">Exit Portal</a>
    </nav>

    <header class="hero">
        <div class="hero-badge">Official Hub</div>
        <h1><?= htmlspecialchars($club_name) ?></h1>
    </header>

    <main class="container">
        
        <?php if (!empty($info)): ?>
        <section class="text-section">
            <div class="section-title">About Us</div>
            <div class="content-body"><?= $info ?></div>
        </section>
        <?php endif; ?>

        <?php if (!empty($events)): ?>
        <section class="text-section">
            <div class="section-title">Club Events</div>
            <div class="content-body"><?= $events ?></div>
        </section>
        <?php endif; ?>

        <?php if (!empty($gallery)): ?>
        <section class="slider-container">
            <button class="arrow arrow-left" onclick="moveSlide(-1)">❮</button>
            <div class="slider-track" id="sliderTrack">
                <?php foreach ($gallery as $img_path): ?>
                <div class="slide">
                    <img src="<?= htmlspecialchars($img_path) ?>" alt="Event Photo" loading="lazy">
                </div>
                <?php endforeach; ?>
            </div>
            <button class="arrow arrow-right" onclick="moveSlide(1)">❯</button>
        </section>
        <?php endif; ?>

        <?php if (!empty($panels)): ?>
        <section class="text-section">
            <div class="section-title">Panel Members</div>
            <div class="exec-grid">
                <?php foreach ($panels as $p): ?>
                <div class="exec-card">
                    <?php $img_src = !empty($p['image']) ? htmlspecialchars($p['image']) : 'images/default-avatar.png'; ?>
                    <div class="dp-wrapper">
                        <img src="<?= $img_src ?>" class="dp-img" alt="Executive Photo">
                    </div>
                    <div class="exec-name"><?= htmlspecialchars($p['name']) ?></div>
                    <div class="exec-role"><?= htmlspecialchars($p['role']) ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

    </main>

    <script>
        // Smooth Image Slider Logic
        let currentSlide = 0;
        const track = document.getElementById('sliderTrack');
        
        function moveSlide(direction) {
            if (!track) return;
            const slides = document.querySelectorAll('.slide');
            const totalSlides = slides.length;
            
            if (totalSlides <= 1) return; 

            currentSlide += direction;

            // Infinite loop logic
            if (currentSlide < 0) {
                currentSlide = totalSlides - 1; 
            } else if (currentSlide >= totalSlides) {
                currentSlide = 0; 
            }

            track.style.transform = `translateX(-${currentSlide * 100}%)`;
        }
    </script>
</body>
</html>