<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniLink | Pro Portal</title>
    <style>
        :root { --pink: #ff4d8d;
         --dark-bg: #0b0b13; 
         --card: #161621;
          --transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
        
        html { scroll-snap-type: y mandatory; scroll-behavior: smooth; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--dark-bg); color: white; overflow-x: hidden; }

        /* --- NAVBAR --- */
        nav { 
            display: flex; justify-content: space-between; padding: 20px 5%; 
            background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent);
            position: fixed; width: 90%; z-index: 1000; align-items: center; 
        }
        .logo { font-size: 26px; font-weight: 900; letter-spacing: -1px; }

        /* --- SLIDER SECTION --- */
        .hero-section { position: relative; height: 100vh; width: 100%; scroll-snap-align: start; overflow: hidden; }
        
        .slide { 
            position: absolute; inset: 0; opacity: 0; transition: var(--transition); 
            background-size: cover; background-position: center 20%; 
            display: flex; align-items: center; padding: 0 5%; 
        }
        .slide.active { opacity: 1; z-index: 1; }
        .slide::after { 
            content: ''; position: absolute; inset: 0; 
            background: linear-gradient(90deg, var(--dark-bg) 5%, transparent 70%),
                        linear-gradient(0deg, var(--dark-bg) 0%, transparent 60%);
            z-index: 2;
        }

        .content { position: relative; z-index: 10; max-width: 650px; }
        h1 { font-size: 4rem; margin: 0 0 15px 0; font-weight: 800; }
        .description { font-size: 1.1rem; color: #ccc; line-height: 1.6; margin-bottom: 30px; }

        /* --- MINI PORTALS (Inside Slider) --- */
        .slider-portals {
            position: absolute; bottom: 80px; left: 5%; z-index: 100;
            display: flex; gap: 15px; align-items: flex-end;
        }
        .mini-card {
            width: 140px; height: 80px; border-radius: 6px; overflow: hidden;
            cursor: pointer; position: relative; border: 2px solid transparent;
            transition: 0.3s; opacity: 0.6;
        }
        .mini-card img { width: 100%; height: 100%; object-fit: cover; }
        .mini-card.active { 
            opacity: 1; border-color: var(--pink); transform: translateY(-10px) scale(1.1);
            box-shadow: 0 5px 15px rgba(255, 77, 141, 0.4);
        }
        .mini-card:hover { opacity: 1; }
        .mini-label {
            position: absolute; bottom: 0; left: 0; width: 100%;
            background: rgba(0,0,0,0.7); font-size: 10px; padding: 4px;
            text-align: center; font-weight: bold;
        }

        /* --- SCROLL HINT --- */
        .scroll-hint {
            position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%);
            z-index: 100; display: flex; flex-direction: column; align-items: center;
            opacity: 0.5; color: white; text-decoration: none; font-size: 10px;
        }
        .mouse { width: 20px; height: 32px; border: 2px solid white; border-radius: 10px; position: relative; margin-bottom: 5px; }
        .mouse::before { content: ''; width: 3px; height: 6px; background: white; position: absolute; top: 6px; left: 50%; transform: translateX(-50%); border-radius: 2px; animation: scroll-anim 1.5s infinite; }
        @keyframes scroll-anim { 0% { opacity: 1; transform: translate(-50%, 0); } 100% { opacity: 0; transform: translate(-50%, 10px); } }

        /* --- TRENDING SECTION --- */
        .trending-section { position: relative; min-height: 100vh; padding: 120px 5% 50px 5%; background: var(--dark-bg); scroll-snap-align: start; z-index: 20; }
        .trending-header { font-size: 2rem; margin-bottom: 30px; font-weight: 800; border-left: 4px solid var(--pink); padding-left: 15px; }
        .portal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 25px; }
        .portal-card { background: var(--card); border-radius: 8px; overflow: hidden; cursor: pointer; transition: 0.4s; }
        .portal-card:hover { transform: translateY(-15px); border: 1px solid var(--pink); }
        .portal-card img { width: 100%; height: 260px; object-fit: cover; }
        .portal-label { padding: 15px; font-size: 1rem; font-weight: bold; text-align: center; }
    </style>
</head>
<body>

    <nav>
        <div class="logo">Club Hub</div>
        <div>Community | News | <span style="background:var(--pink); padding:6px 15px; border-radius:4px; font-weight:bold; cursor:pointer;">Login</span></div>
    </nav>

    <div class="hero-section" id="hero-slider">
        <div class="slider-portals" id="mini-portal-container"></div>

        <a href="#trending" class="scroll-hint" onclick="document.getElementById('trending').scrollIntoView({behavior:'smooth'}); return false;">
            <div class="mouse"></div>
            <span>SCROLL</span>
        </a>
    </div>

    <section class="trending-section" id="trending">
        <div class="trending-header">Trending Portals</div>
        <div class="portal-grid" id="portal-grid"></div>
    </section>

    <script>
        const contentData = [
            { club: 'Robotics', title: 'Robotics: Culling Game', img: 'images/blood-donation-5427229_1920.jpg', desc: 'A deadly jujutsu battle orchestrated by the high-tech Robotics society.' },
            { club: 'Art Club', title: 'Cyberpunk Art Expo', img: 'images/athlete-sport-design-illustration-art-vector.jpg', desc: 'A new wave of digital artists rise through the ranks in the underground.' },
            { club: 'Gaming', title: 'Elden Ring Tournament', img: 'images/debate-1.png', desc: 'Beckoned to the Gaming arena where the champions first set foot.' }
        ];

        const hero = document.getElementById('hero-slider');
        const miniContainer = document.getElementById('mini-portal-container');
        const grid = document.getElementById('portal-grid');

        contentData.forEach((item, i) => {
            // 1. Create Main Slide
            const slide = document.createElement('div');
            slide.className = `slide ${i === 0 ? 'active' : ''}`;
            slide.style.backgroundImage = `url('${item.img}')`;
            slide.innerHTML = `<div class="content">
                <h1>${item.title}</h1>
                <p class="description">${item.desc}</p>
                <div style="display:flex; gap:15px;">
                    <button style="background:var(--pink); border:none; color:white; padding:12px 30px; border-radius:30px; font-weight:bold; cursor:pointer;">Register Now</button>
                    <button style="background:rgba(255,255,255,0.1); border:1px solid white; color:white; padding:12px 30px; border-radius:30px; cursor:pointer;">View Event</button>
                </div>
            </div>`;
            hero.appendChild(slide);

            // 2. Create Mini Portal (Slider Bottom)
            const mini = document.createElement('div');
            mini.className = `mini-card ${i === 0 ? 'active' : ''}`;
            mini.innerHTML = `<img src="${item.img}"><div class="mini-label">${item.club}</div>`;
            mini.onclick = () => jumpToSlide(i);
            miniContainer.appendChild(mini);

            // 3. Create Trending Card
            grid.innerHTML += `<div class="portal-card">
                <img src="${item.img}">
                <div class="portal-label">${item.club}</div>
            </div>`;
        });

        let currentIdx = 0;
        const slides = document.querySelectorAll('.slide');
        const minis = document.querySelectorAll('.mini-card');

        function jumpToSlide(n) {
            slides[currentIdx].classList.remove('active');
            minis[currentIdx].classList.remove('active');
            currentIdx = n;
            slides[currentIdx].classList.add('active');
            minis[currentIdx].classList.add('active');
            
            // Reset interval when manual click happens
            clearInterval(autoPlay);
            autoPlay = setInterval(nextSlide, 6000);
        }

        function nextSlide() {
            let next = (currentIdx + 1) % slides.length;
            jumpToSlide(next);
        }

        let autoPlay = setInterval(nextSlide, 6000);
    </script>
</body>
</html>