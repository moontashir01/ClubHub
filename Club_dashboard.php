<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubHub | Dashboard</title>
    <link rel="stylesheet" href="styles.css">
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