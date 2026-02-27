<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubHub | Dashboard</title>
    <style>
        :root { 
            --pink: #ff4d8d;
            --dark-bg: #0b0b13; 
            --card: #161621;
            --transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1); 
        }
        
        html { scroll-snap-type: y mandatory; scroll-behavior: smooth; }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--dark-bg); color: white; overflow-x: hidden; }

        /* --- NAVBAR --- */
        nav { 
            display: flex; justify-content: space-between; padding: 20px 5%; 
            background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent);
            position: fixed; width: 90%; z-index: 1000; align-items: center; 
        }
        .logo { font-size: 26px; font-weight: 900; letter-spacing: -1px; }

        /* --- HERO SLIDER --- */
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

        /* --- DASHBOARD SECTION --- */
        .dashboard-section { position: relative; min-height: 100vh; padding: 120px 5% 50px 5%; background: var(--dark-bg); scroll-snap-align: start; z-index: 20; }
        .header { font-size: 2rem; margin-bottom: 30px; font-weight: 800; border-left: 4px solid var(--pink); padding-left: 15px; }
        .portal-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 25px; }
        .portal-card { background: var(--card); border-radius: 8px; padding: 30px; text-align: center; cursor: pointer; transition: 0.4s; border: 1px solid transparent; }
        .portal-card:hover { transform: translateY(-15px); border: 1px solid var(--pink); }

        /* --- MODAL --- */
        #modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:3000; align-items:center; justify-content:center; }
        .modal-content { background: var(--card); padding: 40px; border-radius: 12px; width: 400px; border: 1px solid var(--pink); }
    </style>
</head>
<body>

    <nav>
        <div class="logo">Club Hub</div>
    </nav>

    <div class="hero-section" id="hero-slider"></div>

    <section class="dashboard-section" id="dashboard">
        <div class="header">Club Management Dashboard</div>
        <div class="portal-grid" id="dashboard-grid"></div>
    </section>

    <div id="modal-overlay">
        <div class="modal-content">
            <h2 id="modal-title">Action</h2>
            <input type="text" placeholder="Enter details here..." style="width:100%; padding:10px; margin:15px 0; background:#0b0b13; border:1px solid #444; color:white; border-radius:4px; box-sizing:border-box;">
            <button onclick="closeModal()" style="background:var(--pink); border:none; padding:10px 20px; border-radius:4px; color:white; cursor:pointer;">Confirm</button>
            <button onclick="closeModal()" style="background:transparent; border:none; color:#888; cursor:pointer; margin-left:10px;">Cancel</button>
        </div>
    </div>

    <script>
        // Data
        const contentData = [
            { title: 'Welcome Commander', desc: 'Manage your club operations effectively.', img: 'images/club_dashboard_cover.jpg' }
        ];

        const dashboardActions = [
            { title: 'View Members', icon: '👥', desc: 'View Club Members' },
            { title: 'Send Volunteers', icon: '🚀', desc: 'Dispatch members for events.' },
            { title: 'Add Members', icon: '➕', desc: 'Register new members.' },
            { title: 'Event Logs', icon: '📝', desc: 'Review past and upcoming activities.' }
        ];

        // Populate Slider
        const hero = document.getElementById('hero-slider');
        contentData.forEach(item => {
            const slide = document.createElement('div');
            slide.className = 'slide active';
            slide.style.backgroundImage = `url('${item.img}')`;
            slide.innerHTML = `<div class="content"><h1>${item.title}</h1><p class="description">${item.desc}</p></div>`;
            hero.appendChild(slide);
        });

        // Populate Dashboard
        const dashGrid = document.getElementById('dashboard-grid');
        dashboardActions.forEach(action => {
            const card = document.createElement('div');
            card.className = 'portal-card';
            card.onclick = () => openModal(action.title);
            card.innerHTML = `
                <div style="font-size: 3rem; margin-bottom: 15px;">${action.icon}</div>
                <div style="font-weight:bold; font-size: 1.2rem;">${action.title}</div>
                <p style="color: #888; font-size: 0.9rem;">${action.desc}</p>
            `;
            dashGrid.appendChild(card);
        });

        // Modal Functions
        function openModal(title) {
            document.getElementById('modal-title').innerText = title;
            document.getElementById('modal-overlay').style.display = 'flex';
        }
        function closeModal() {
            document.getElementById('modal-overlay').style.display = 'none';
        }
    </script>
</body>
</html>