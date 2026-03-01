<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>UniLink | Pro Portal</title>

    <style>

        :root { --pink: #ff4d8d; --dark-bg: #0b0b13; --card: #161621; --transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1); }

       

        html { scroll-snap-type: y mandatory; scroll-behavior: smooth; }

        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--dark-bg); color: white; overflow-x: hidden; }



        /* --- NAVBAR --- */

        nav { display: flex; justify-content: space-between; padding: 20px 5%; background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent); position: fixed; width: 90%; z-index: 1000; align-items: center; }

        .logo { font-size: 26px; font-weight: 900; letter-spacing: -1px; }



        /* --- SLIDER SECTION --- */

        .hero-section { position: relative; height: 100vh; width: 100%; scroll-snap-align: start; overflow: hidden; }

        .slide { position: absolute; inset: 0; opacity: 0; transition: var(--transition); background-size: cover; background-position: center 20%; display: flex; align-items: center; padding: 0 5%; }

        .slide.active { opacity: 1; z-index: 1; }

        .slide::after { content: ''; position: absolute; inset: 0; background: linear-gradient(90deg, var(--dark-bg) 5%, transparent 70%), linear-gradient(0deg, var(--dark-bg) 0%, transparent 60%); z-index: 2; }

        .content { position: relative; z-index: 10; max-width: 650px; }

        h1 { font-size: 4rem; margin: 0 0 15px 0; font-weight: 800; }

        .description { font-size: 1.1rem; color: #ccc; line-height: 1.6; margin-bottom: 30px; }



        .slider-portals { position: absolute; bottom: 80px; left: 5%; z-index: 100; display: flex; gap: 15px; align-items: flex-end; }

        .mini-card { width: 140px; height: 80px; border-radius: 6px; overflow: hidden; cursor: pointer; position: relative; border: 2px solid transparent; transition: 0.3s; opacity: 0.6; }

        .mini-card img { width: 100%; height: 100%; object-fit: cover; }

        .mini-card.active { opacity: 1; border-color: var(--pink); transform: translateY(-10px) scale(1.1); box-shadow: 0 5px 15px rgba(255, 77, 141, 0.4); }

        .mini-label { position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.7); font-size: 10px; padding: 4px; text-align: center; font-weight: bold; }



        /* --- CLUBS SECTION --- */

        .clubs-section { position: relative; min-height: 100vh; padding: 120px 5% 50px 5%; background: var(--dark-bg); scroll-snap-align: start; }

        .section-title { font-size: 2rem; margin-bottom: 30px; font-weight: 800; border-left: 4px solid var(--pink); padding-left: 15px; }

        .club-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 25px; }

        .club-card { background: var(--card); border: none; border-radius: 8px; overflow: hidden; cursor: pointer; transition: 0.4s; padding: 0; width: 100%; color: white; }

        .club-card:hover { transform: translateY(-15px); outline: 1px solid var(--pink); }

        .club-card img { width: 100%; height: 260px; object-fit: cover; display: block; }

        .club-card .label { padding: 15px; font-size: 1rem; font-weight: bold; text-align: center; }









        .dynamic-btn {

    transition: all 0.3s ease; /* Smooth transition */

}



.dynamic-btn:hover {

    filter: brightness(1.2); /* Makes the color glow/lighten */

    transform: scale(1.05);  /* Slightly grows the button */

    box-shadow: 0 5px 15px rgba(0,0,0,0.3); /* Adds a soft shadow */

}



        /* --- SCROLL HINT --- */

        .scroll-hint { position: absolute; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 100; display: flex; flex-direction: column; align-items: center; opacity: 0.5; color: white; text-decoration: none; font-size: 10px; }

        .mouse { width: 20px; height: 32px; border: 2px solid white; border-radius: 10px; position: relative; margin-bottom: 5px; }

        .mouse::before { content: ''; width: 3px; height: 6px; background: white; position: absolute; top: 6px; left: 50%; transform: translateX(-50%); border-radius: 2px; animation: scroll-anim 1.5s infinite; }

        @keyframes scroll-anim { 0% { opacity: 1; transform: translate(-50%, 0); } 100% { opacity: 0; transform: translate(-50%, 10px); } }

    </style>

</head>

<body>



    <nav>

        <div class="logo">Club Hub</div>

        <div>Community | News | <span style="background:var(--pink); padding:6px 15px; border-radius:4px; font-weight:bold; cursor:pointer;">Login</span></div>

    </nav>



    <div class="hero-section" id="hero-slider">

        <div class="slider-portals" id="mini-portal-container"></div>

        <a href="#clubs" class="scroll-hint">

            <div class="mouse"></div>

            <span>SCROLL</span>

        </a>

    </div>



    <section class="clubs-section" id="clubs">

        <div class="section-title">Clubs</div>

        <div class="club-grid">

            <button class="club-card" onclick="location.href='#link1'">

                <img src="images/debate-1.png">

                <div class="label">Debate Society</div>

            </button>

            <button class="club-card" onclick="location.href='#link2'">

                <img src="images/athlete-sport-design-illustration-art-vector.jpg">

                <div class="label">Art Club</div>

            </button>

            <button class="club-card" onclick="location.href='#link3'">

                <img src="images/blood-donation-5427229_1920.jpg">

                <div class="label">Robotics</div>

            </button>

            <button class="club-card" onclick="location.href='#link4'">

                <img src="images/coding.jpg">

                <div class="label">Coding Hub</div>

            </button>

        </div>

    </section>



    <script>

        const contentData = [

            {

                club: 'Robotics',

                title: 'Robotics: Culling Game',

                img: 'images/blood-donation-5427229_1920.jpg',

                desc: 'A deadly jujutsu battle orchestrated by the high-tech Robotics society.',

                regLink: 'register_robotics.php',

                regText: 'Register Now',

                regColor: '#ff4d8d', // Pink

                eventLink: 'robotics_details.html',

                viewText: 'View Event'

            },

            {

                club: 'Art Club',

                title: 'Cyberpunk Art Expo',

                img: 'images/athlete-sport-design-illustration-art-vector.jpg',

                desc: 'A new wave of digital artists rise through the ranks in the underground.',

                regLink: 'register_art.php',

                regText: 'Join Expo',

                regColor: '#00d2ff', // Cyan

                eventLink: 'art_expo.html',

                viewText: 'Gallery'

            },

            {

                club: 'Gaming',

                title: 'Elden Ring Tournament',

                img: 'images/debate-1.png',

                desc: 'Beckoned to the Gaming arena where the champions first set foot.',

                regLink: 'gaming_signup.php',

                regText: 'Enter Arena',

                regColor: '#ffcc00', // Gold

                eventLink: 'tournament_info.html',

                viewText: 'Rules'

            }

        ];



        const hero = document.getElementById('hero-slider');

        const miniContainer = document.getElementById('mini-portal-container');



        contentData.forEach((item, i) => {

            const slide = document.createElement('div');

            slide.className = `slide ${i === 0 ? 'active' : ''}`;

            slide.style.backgroundImage = `url('${item.img}')`;

           

            slide.innerHTML = `

                <div class="content">

                    <h1>${item.title}</h1>

                    <p class="description">${item.desc}</p>

                    <div style="display:flex; gap:15px;">

                        <button class="dynamic-btn" onclick="location.href='${item.regLink}'"

                                style="background:${item.regColor}; border:none; color:white; padding:12px 30px; border-radius:30px; font-weight:bold; cursor:pointer;">

                                ${item.regText}

                        </button>

                        <button class="dynamic-btn" onclick="location.href='${item.eventLink}'"

                                style="background:rgba(255,255,255,0.1); border:1px solid white; color:white; padding:12px 30px; border-radius:30px; cursor:pointer;">

                                ${item.viewText}

                        </button>

                    </div>

                </div>`;

            hero.appendChild(slide);



            const mini = document.createElement('div');

            mini.className = `mini-card ${i === 0 ? 'active' : ''}`;

            mini.innerHTML = `<img src="${item.img}"><div class="mini-label">${item.club}</div>`;

            mini.onclick = () => jumpToSlide(i);

            miniContainer.appendChild(mini);

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

            clearInterval(autoPlay);

            autoPlay = setInterval(nextSlide, 6000);

        }



        function nextSlide() { jumpToSlide((currentIdx + 1) % slides.length); }

        let autoPlay = setInterval(nextSlide, 4000);

    </script>

</body>

</html>