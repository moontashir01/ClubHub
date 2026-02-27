<?php include 'login.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UniLink | Login</title>
    <style>
        :root { 
            --pink: #ff4d8d; 
            --dark-bg: #0b0b13; 
            --input-bg: #161621; 
            --card-radius: 24px;
        }

        body, html { 
            margin: 0; padding: 0; height: 100%; 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            background: var(--dark-bg); color: white; overflow: hidden; 
        }

        .container { 
            display: flex; 
            height: 100vh; 
            width: 100vw; 
            padding: 20px; /* This creates the "frame" effect around the whole app */
            box-sizing: border-box;
            gap: 20px;
        }

        /* Left Box Frame Slider */
        .slider-frame { 
            flex: 1.2; 
            position: relative; 
            overflow: hidden; 
            border-radius: var(--card-radius);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .slide { 
            position: absolute; 
            inset: 0; 
            background-size: cover; 
            background-position: center; 
            opacity: 0; 
            transition: opacity 1.2s ease-in-out, transform 10s linear; 
        }

        .slide.active { 
            opacity: 1; 
            transform: scale(1.1); 
        }

        /* Overlay to make text readable and add depth */
        .slide::after { 
            content: ''; 
            position: absolute; 
            inset: 0; 
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(11,11,19,0.8)); 
        }

        /* Right Login Side */
        .login-side { 
            flex: 0.8; 
            display: flex; 
            flex-direction: column;
            justify-content: center; 
            align-items: center; 
            position: relative;
        }

        /* Branding Text */
        .brand {
            position: absolute;
            top: 40px;
            left: 120px;
            
        }
        .brand h2 {
            margin: 0;
            font-size: 2.5rem;
            letter-spacing: 1px;
            background: linear-gradient(45deg, #fff, var(--pink));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .login-box { 
            width: 100%; 
            max-width: 380px; 
            padding: 40px; 
        }

        .login-box h1 { font-size: 2.2rem; margin-bottom: 10px; }

        .input-group { margin-bottom: 20px; }
        .input-group label { 
            display: block; margin-bottom: 8px; font-size: 0.75rem; 
            color: #888; text-transform: uppercase; letter-spacing: 1px;
        }
        
        .input-group input { 
            width: 100%; padding: 14px; 
            background: var(--input-bg); 
            border: 1px solid rgba(255,255,255,0.05); 
            border-radius: 12px; color: white; outline: none; transition: 0.3s; 
            box-sizing: border-box;
        }

        .input-group input:focus { 
            border-color: var(--pink); 
            background: rgba(255, 255, 255, 0.05);
        }

        .login-btn { 
            width: 100%; padding: 16px; 
            background: var(--pink); color: white; 
            border: none; border-radius: 12px; 
            font-weight: bold; font-size: 1rem;
            cursor: pointer; transition: 0.3s; 
            margin-top: 10px;
        }

        .login-btn:hover { 
            transform: translateY(-3px); 
            box-shadow: 0 10px 20px rgba(255, 77, 141, 0.3); 
        }

        /* Responsive tweak */
        @media (max-width: 900px) {
            .slider-frame { display: none; }
            .login-side { flex: 1; }
        }
    </style>
</head>
<body>

    <div class="container">
        <div class="slider-frame" id="event-slider">
            <div style="position: absolute; bottom: 40px; left: 40px; z-index: 10;">
                <span style="background: var(--pink); padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold;">FEATURED EVENT</span>
                <h3 id="slide-title" style="margin-top: 10px; font-size: 1.8rem;">Connecting Campus Life</h3>
            </div>
        </div>

        <div class="login-side">
            <div class="brand">
                <h2>ClubHub </h2>
            </div>

            <div class="login-box">
                <h1>Welcome Back</h1>
                <p style="color: #666; margin-bottom: 35px;">Enter your credentials to manage your club.</p>
                
                <form action="login.php" method="POST">
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="Email" placeholder="name@university.edu" required>
                    </div>
                    <div class="input-group">
                        <label>Password</label>
                        <input type="password" name="Password" placeholder="••••••••" required>
                    </div>
                    <button type="submit" name="submit" class="login-btn">Sign In to Hub</button>
                </form>

                <p style="text-align: center; color: #444; margin-top: 30px; font-size: 0.85rem;">
                    Forgot password? <a href="#" style="color: var(--pink); text-decoration: none;">Reset here</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        const events = [
            { img: "https://images.unsplash.com/photo-1578632292335-df3abbb0d586?q=80&w=1600", title: "Connecting Campus Life" },
            { img: "https://images.unsplash.com/photo-1523580494863-6f3031224c94?q=80&w=1600", title: "Discover New Societies" },
            { img: "https://images.unsplash.com/photo-1614728263952-84ea256f9679?q=80&w=1600", title: "Grow Your Network" }
        ];

        const slider = document.getElementById('event-slider');
        const titleElement = document.getElementById('slide-title');

        events.forEach((ev, i) => {
            const slide = document.createElement('div');
            slide.className = `slide ${i === 0 ? 'active' : ''}`;
            slide.style.backgroundImage = `url('${ev.img}')`;
            slider.appendChild(slide);
        });

        let current = 0;
        const slides = document.querySelectorAll('.slide');
        
        setInterval(() => {
            slides[current].classList.remove('active');
            current = (current + 1) % slides.length;
            slides[current].classList.add('active');
            
            // Update the text title with a small fade effect
            titleElement.style.opacity = 0;
            setTimeout(() => {
                titleElement.innerText = events[current].title;
                titleElement.style.opacity = 1;
            }, 500);
        }, 5000);
    </script>
</body>
</html>