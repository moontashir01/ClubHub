<?php 
session_start();
include 'connection.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ClubHub | Gateway</title>
    <style>
        :root { 
            --pink: #ff4d8d; 
            --dark-bg: #0b0b13; 
            --input-bg: #161621; 
            --card-radius: 24px;
        }

        body, html { 
            margin: 0; padding: 0; height: 100%; 
            font-family: 'Segoe UI', Roboto, sans-serif; 
            background: var(--dark-bg); color: white; overflow: hidden; 
        }

        .container { 
            display: flex; height: 100vh; width: 100vw; 
            padding: 20px; box-sizing: border-box; gap: 20px;
        }

        
        .slider-frame { 
            flex: 1.2; position: relative; overflow: hidden; 
            border-radius: var(--card-radius);
            box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        }

        .slide { 
            position: absolute; inset: 0; background-size: cover; 
            background-position: center; opacity: 0; 
            transition: opacity 1.2s ease-in-out, transform 10s linear; 
        }

        .slide.active { opacity: 1; transform: scale(1.1); }

        .slide::after { 
            content: ''; position: absolute; inset: 0; 
            background: linear-gradient(to bottom, rgba(0,0,0,0.2), rgba(11,11,19,0.8)); 
        }

        
        .login-side { 
            flex: 0.8; display: flex; flex-direction: column;
            justify-content: center; align-items: center; position: relative;
        }

        
        .brand { position: absolute; top: 40px; left: 120px; }
        .brand h2 {
            margin: 0; font-size: 2.5rem; background: linear-gradient(45deg, #fff, var(--pink));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
        }

        .login-box { width: 100%; max-width: 380px; padding: 40px; }
        .login-box h1 { font-size: 2.2rem; margin-bottom: 10px; }
        
        .input-group { margin-bottom: 15px; }
        .input-group label { 
            display: block; margin-bottom: 8px; font-size: 0.7rem; 
            color: #888; text-transform: uppercase;
        }
        
        .input-group input { 
            width: 100%; padding: 12px; background: var(--input-bg); 
            border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; 
            color: white; outline: none; transition: 0.3s; box-sizing: border-box;
        }

        .input-group input:focus { border-color: var(--pink); }

        .login-btn { 
            width: 100%; padding: 16px; background: var(--pink); 
            color: white; border: none; border-radius: 12px; 
            font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 10px;
        }

        .login-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255, 77, 141, 0.3); }

        
        .alert-msg {
            padding: 12px; border-radius: 12px; margin-bottom: 20px; 
            font-size: 0.85rem; font-weight: bold; text-align: center;
        }
        .alert-error { background: rgba(255, 77, 77, 0.15); color: #ff4d4d; border: 1px solid rgba(255, 77, 77, 0.3); }
        .alert-success { background: rgba(77, 255, 141, 0.15); color: #4dff8d; border: 1px solid rgba(77, 255, 141, 0.3); }

        
        .msg-placeholder { min-height: 20px; margin-bottom: 10px; }

        @media (max-width: 900px) { .slider-frame { display: none; } .login-side { flex: 1; } }
    </style>
</head>
<body>

    <div class="container">
        <div class="slider-frame" id="event-slider">
            <div style="position: absolute; bottom: 40px; left: 40px; z-index: 10;">
                <span style="background: var(--pink); padding: 5px 12px; border-radius: 20px; font-size: 0.7rem; font-weight: bold;">FEATURED EVENT</span>
                <h3 id="slide-title" style="margin-top: 10px; font-size: 1.8rem; transition: opacity 0.5s;">Connecting Campus Life</h3>
            </div>
        </div>

        <div class="login-side">
            <div class="brand"><h2>ClubHub</h2></div>

            <div class="login-box" id="login-section">
                <h1>Welcome Back</h1>
                <p style="color: #666; margin-bottom: 30px;">Login to manage your club activities.</p>
                
                <?php if (isset($_SESSION['login_error'])): ?>
                    <div id="alert-msg alert-error" class="alert-msg alert-error"><?php echo $_SESSION['login_error']; unset($_SESSION['login_error']); ?></div>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <div class="input-group">
                        <label>Email Address</label>
                        <input type="email" name="Email" required>
                    </div>
                    <div class="input-group" style="margin-bottom: 5px;">
                        <label>Password</label>
                        <input type="password" name="Password" required>
                    </div>
                    <div style="text-align: right; margin-bottom: 20px;">
                        <a href="forgot_password.php" style="color: #888; text-decoration: none; font-size: 0.75rem; transition: 0.3s;" onmouseover="this.style.color='var(--pink)'" onmouseout="this.style.color='#888'">Forgot Password?</a>
                    </div>
                    <button type="submit" name="submit" class="login-btn">Sign In</button>
                </form>
                <p style="text-align: center; color: #666; margin-top: 25px; font-size: 0.85rem;">
                    Don't have an account? <a href="#" onclick="toggleAuth(event)" style="color: var(--pink); text-decoration: none; font-weight: bold;">Sign Up</a>
                </p>
            </div>

            <div class="login-box" id="signup-section" style="display: none;">
                <h1>Join the Hub</h1>
                
                <div class="msg-placeholder">
                    <?php if (isset($_SESSION['msg'])): ?>
                        <div id="signup-status-trigger" class="alert-msg <?php echo ($_SESSION['msg_type'] == 'success') ? 'alert-success' : 'alert-error'; ?>">
                            <?php 
                                echo $_SESSION['msg']; 
                                unset($_SESSION['msg']); 
                                unset($_SESSION['msg_type']); 
                            ?>
                        </div>
                    <?php else: ?>
                        <p style="color: #666; margin-bottom: 15px;">Create an account for your club.</p>
                    <?php endif; ?>
                </div>

                <form action="signup.php" method="POST">
                    <div class="input-group">
                        <label>Full Name</label>
                        <input type="text" name="Name" required>
                    </div>
                    <div class="input-group">
                        <label>Email</label>
                        <input type="email" name="Email" required>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        
                 <div class="input-group" style="flex:1;">
                   <label>Student Id</label>
                    <input 
                    type="text" 
                     name="ID" 
                    required 
                    minlength="10" 
                    maxlength="10" 
                    pattern="\d{10}" 
                    title="Student ID must be exactly 10 digits"
                    >
                  </div>



                        <div class="input-group" style="flex:1;">
                            <label>Password</label>
                            <input type="password" name="Password" required>
                        </div>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <div class="input-group" style="flex:1;">
                            <label>DOB</label>
                            <input type="date" name="DOB" required>
                        </div>
                        <div class="input-group" style="flex:1;">
                            <label>Phone</label>
                            <input
                             type="text"
                              name="Phone" 
                              required
                              minlength="11"
                              maxlength="11"
                              patter="\d{11}"
                              title="Phone Number must be 11 digits">
                        </div>
                    </div>
                    <button type="submit" name="submit" class="login-btn">Create Account</button>
                </form>
                <p style="text-align: center; color: #666; margin-top: 25px; font-size: 0.85rem;">
                    Already a member? <a href="#" onclick="toggleAuth(event)" style="color: var(--pink); text-decoration: none; font-weight: bold;">Login</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function toggleAuth(e) {
            if(e) e.preventDefault();
            const loginSection = document.getElementById('login-section');
            const signupSection = document.getElementById('signup-section');
            if (loginSection.style.display === 'none') {
                loginSection.style.display = 'block';
                signupSection.style.display = 'none';
            } else {
                loginSection.style.display = 'none';
                signupSection.style.display = 'block';
            }
        }

        window.onload = function() {
            const signupMsg = document.getElementById('signup-status-trigger');
            if (signupMsg) {
                document.getElementById('login-section').style.display = 'none';
                document.getElementById('signup-section').style.display = 'block';
                setTimeout(() => {
                    signupMsg.style.transition = "opacity 0.6s ease";
                    signupMsg.style.opacity = "0";
                    setTimeout(() => signupMsg.remove(), 600);
                }, 5000);
            }
            const loginMsg = document.getElementById('alert-msg alert-error');
               if (loginMsg) {
               
                setTimeout(() => {
                    loginMsg.style.transition = "opacity 0.6s ease";
                    loginMsg.style.opacity = "0";
                    setTimeout(() => loginMsg.remove(), 600);
                }, 5000);
            }
        };

        
        const events = [
            { img: 'images/blood-donation-5427229_1920.jpg', title: "Socio Camp" },
            { img: 'images/athlete-sport-design-illustration-art-vector.jpg', title: "NFL Tournament" },
            { img: 'images/debate-1.png', title: "Debate Competition" }
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
            titleElement.style.opacity = 0;
            setTimeout(() => {
                titleElement.innerText = events[current].title;
                titleElement.style.opacity = 1;
            }, 500);
        }, 5000);
    </script>
</body>
</html>