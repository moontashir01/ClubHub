<?php
session_start();
include 'connection.php';

$alertMessage = "";

// Fetch students for the dropdown
$students_query = "SELECT student_id, full_name FROM students ORDER BY full_name ASC";
$students_result = @mysqli_query($con, $students_query);

if (!$students_result) {
    $db_error = mysqli_error($con);
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_club'])) {
    $club_name = htmlspecialchars(trim($_POST['club_name']));
    $category = htmlspecialchars(trim($_POST['category']));
    $president_id = htmlspecialchars(trim($_POST['president_id']));
    $vision = htmlspecialchars(trim($_POST['vision']));
    
    // --- NEW ADDITIONS START ---
    $social_link = !empty(trim($_POST['social_link'])) ? htmlspecialchars(trim($_POST['social_link'])) : null;
    $advisor_name = "Admin";
    $est_date = date("Y-m-d"); // Auto generates today's date (YYYY-MM-DD)
    // --- NEW ADDITIONS END ---

    $logo_path = "uploads/dummy_logo.png"; 
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $target_dir = "uploads/club_logos/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $file_ext = pathinfo($_FILES["logo"]["name"], PATHINFO_EXTENSION);
        $new_filename = time() . '_' . preg_replace("/[^a-zA-Z0-9]/", "", $club_name) . '.' . $file_ext;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_path = $target_file;
        }
    }

    mysqli_begin_transaction($con);

    try {
        // 1. Insert into clubs table (Updated with advisor_name, est_date, social_link)
        $stmt_club = $con->prepare("INSERT INTO clubs (club_name, category, president_name, advisor_name, est_date, social_link, vision, logo) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_club->bind_param("ssssssss", $club_name, $category, $president_id, $advisor_name, $est_date, $social_link, $vision, $logo_path);
        $stmt_club->execute();
        $new_club_id = $stmt_club->insert_id; 


        $role = "EB-President";
        $active_status = 1;
        $stmt_member = $con->prepare("INSERT INTO club_members (student_id, club_id, Role, active) VALUES (?, ?, ?, ?)");
        $stmt_member->bind_param("sisi", $president_id, $new_club_id, $role, $active_status);
        $stmt_member->execute();

        mysqli_commit($con);
        $alertMessage = "success";
        
    } catch (Exception $e) {
        mysqli_rollback($con);
        $alertMessage = "error_db";
        $error_details = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Club Initialization | ClubHub</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-dark: #0a0b10; --card-bg: #161925; --primary: #ff477e; --primary-glow: rgba(255, 71, 126, 0.4);
            --text-main: #45dad572; --text-muted: #ba04c0; --border-color: rgba(255, 255, 255, 0.08);
        }
        body { 
            margin: 0; font-family: 'Segoe UI', Tahoma, sans-serif; background: var(--bg-dark); 
            color: var(--text-main); min-height: 100vh; display: flex; align-items: center; justify-content: center;
            background-image: radial-gradient(circle at top left, #1f111a, transparent 40%), radial-gradient(circle at bottom right, #111424, transparent 40%);
        }
        
        .main-wrapper {
            display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 40px; 
            width: 95%; max-width: 1300px; margin: 40px auto; align-items: start;
        }

        .form-section {
            background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 20px;
            padding: 40px; box-shadow: 0 25px 50px rgba(0,0,0,0.5); position: relative; overflow: hidden;
            animation: slideUp 0.8s ease-out forwards;
        }
        .form-section::before {
            content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(90deg, var(--primary), #ff9a9e);
        }
        
        .section-title { font-size: 1.8rem; margin-bottom: 30px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .section-title span { color: var(--primary); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .full-width { grid-column: 1 / -1; }

        .input-group { display: flex; flex-direction: column; gap: 8px; }
        .input-group label { font-size: 0.85rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }
        
        .input-group input, .input-group select, .input-group textarea {
            background: rgba(0,0,0,0.2); border: 1px solid var(--border-color); border-radius: 10px;
            padding: 14px 18px; color: #fff; font-size: 1rem; transition: 0.3s; box-sizing: border-box;
        }
        .input-group input:focus, .input-group select:focus, .input-group textarea:focus {
            border-color: var(--primary); outline: none; box-shadow: 0 0 15px var(--primary-glow);
        }
        
        /* 3D Animated File Upload Area */
        .upload-container {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            background: rgba(0,0,0,0.2); border: 2px dashed var(--border-color);
            border-radius: 15px; padding: 30px; cursor: pointer; position: relative;
            transform-style: preserve-3d; perspective: 1000px; transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: center;
        }
        .upload-container:hover {
            border-color: var(--primary);
            background: rgba(255, 71, 126, 0.05);
            transform: translateY(-5px) rotateX(4deg) rotateY(-2deg);
            box-shadow: -10px 15px 30px rgba(0,0,0,0.4), 0 0 20px var(--primary-glow);
        }
        .upload-container input[type="file"] {
            position: absolute; width: 100%; height: 100%; top: 0; left: 0; opacity: 0; cursor: pointer; z-index: 10;
        }
        .upload-icon {
            font-size: 3.5rem; margin-bottom: 10px; transition: all 0.4s ease;
        }
        .upload-container:hover .upload-icon {
            transform: translateZ(40px) scale(1.1); text-shadow: 0 10px 20px rgba(0,0,0,0.5);
        }
        .upload-text {
            color: #fff; font-size: 1.1rem; font-weight: 600; margin-bottom: 5px; transition: transform 0.4s ease;
        }
        .upload-container:hover .upload-text { transform: translateZ(20px); }
        .upload-subtext {
            color: var(--text-muted); font-size: 0.85rem; transition: transform 0.4s ease;
        }
        .upload-container:hover .upload-subtext { transform: translateZ(10px); }

        .btn-submit {
            background: linear-gradient(135deg, var(--primary), #ff9a9e); border: none; padding: 18px;
            border-radius: 10px; color: white; font-weight: bold; font-size: 1.1rem; cursor: pointer;
            text-transform: uppercase; letter-spacing: 2px; transition: 0.3s; margin-top: 10px; width: 100%;
        }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 15px 30px var(--primary-glow); }

        .preview-section { position: sticky; top: 40px; display: flex; flex-direction: column; align-items: center; }
        .preview-label { color: var(--text-muted); text-transform: uppercase; letter-spacing: 3px; margin-bottom: 20px; font-size: 0.9rem;}
        
        .id-card {
            width: 100%; max-width: 350px; height: 500px; background: linear-gradient(145deg, #1f2235, #12141e);
            border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); box-shadow: 0 30px 60px rgba(0,0,0,0.6);
            display: flex; flex-direction: column; align-items: center; padding: 30px; box-sizing: border-box;
            position: relative; overflow: hidden; transition: 0.3s;
        }
        .id-card::after {
            content: ''; position: absolute; top: -50px; right: -50px; width: 150px; height: 150px;
            background: var(--primary); border-radius: 50%; filter: blur(70px); opacity: 0.5; z-index: 0;
        }
        .id-card * { z-index: 1; }
        
        .preview-logo { width: 130px; height: 130px; border-radius: 50%; border: 4px solid var(--primary); background: #2a2d43; object-fit: cover; margin-bottom: 25px; box-shadow: 0 10px 20px rgba(0,0,0,0.5); }
        .preview-name { font-size: 1.6rem; font-weight: 800; text-align: center; margin: 0 0 10px 0; color: #fff; line-height: 1.2; }
        .preview-cat { background: rgba(255, 71, 126, 0.2); color: #ff9a9e; padding: 6px 15px; border-radius: 20px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; font-weight: bold; margin-bottom: 20px; }
        
        .preview-details { width: 100%; background: rgba(0,0,0,0.3); border-radius: 12px; padding: 15px; margin-top: auto; }
        .p-row { display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 8px; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 8px;}
        .p-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .p-label { color: var(--text-muted); }
        .p-val { font-weight: 600; color: #c6573eca; text-align: right; }

        @media (max-width: 900px) { .main-wrapper { grid-template-columns: 1fr; } .preview-section { display: none; } }
        .back-btn { position: absolute; top: 20px; left: 20px; color: var(--text-muted); text-decoration: none; font-weight: bold; font-size: 0.9rem; }
        .back-btn:hover { color: var(--primary); }
        
        .error-banner { background: #ff477e; color: white; padding: 15px; text-align: center; font-weight: bold; border-radius: 8px; margin-bottom: 20px; grid-column: 1/-1;}

        @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <a href="admin_dashboard.php" class="back-btn">← Back to Dashboard</a>

    <div class="main-wrapper">
        <div class="form-section">
            <h1 class="section-title"><span>❖</span> Initialize New Entity</h1>
            
            <?php if(isset($db_error)): ?>
                <div class="error-banner">
                    MySQL Error: <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <div class="form-grid">
                    
                    <div class="input-group full-width">
                        <label>Official Club Name</label>
                        <input type="text" name="club_name" id="input_name" placeholder="e.g. NSU Computer Club" required>
                    </div>

                    <div class="input-group">
                        <label>Domain / Category</label>
                        <select name="category" id="input_cat" required>
                            <option value="" disabled selected>Select...</option>
                            <option value="Technology">Technology</option>
                            <option value="Cultural">Cultural</option>
                            <option value="Business">Business</option>
                            <option value="Sports">Sports</option>
                        </select>
                    </div>

                    <div class="input-group">
                        <label>Appoint President (Role: EB-President)</label>
                        <select name="president_id" id="input_pres" required>
                            <option value="" disabled selected>Select Enrolled Student...</option>
                            <?php 
                            if($students_result && mysqli_num_rows($students_result) > 0) {
                                while($row = mysqli_fetch_assoc($students_result)) {
                                    echo "<option value='".$row['student_id']."'>".$row['full_name']." (".$row['student_id'].")</option>";
                                }
                            } else {
                                echo "<option disabled>No students found</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="input-group full-width">
                        <label>Upload Official Insignia (Logo)</label>
                        <div class="upload-container">
                            <input type="file" name="logo" id="input_logo" accept="image/*" required>
                            <div class="upload-icon">💠</div>
                            <div class="upload-text" id="upload-filename">Drag & Drop Logo or Click Here</div>
                            <div class="upload-subtext">Supports JPG, PNG, GIF</div>
                        </div>
                    </div>

                    <div class="input-group full-width">
                        <label>Social Media Link (Optional)</label>
                        <input type="url" name="social_link" placeholder="e.g. https://facebook.com/nsu.computer.club">
                    </div>

                    <div class="input-group full-width">
                        <label>Club Vision & Objectives</label>
                        <textarea name="vision" rows="3" required placeholder="Define the core mission..."></textarea>
                    </div>

                    <div class="full-width">
                        <button type="submit" name="create_club" class="btn-submit" <?php echo isset($db_error) ? 'disabled style="opacity:0.5;"' : ''; ?>>Deploy & Authorize Club</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="preview-section">
            <div class="preview-label">Live Interface Preview</div>
            <div class="id-card" id="preview_card">
                <img src="https://via.placeholder.com/150/2a2d43/ffffff?text=LOGO" alt="Logo" class="preview-logo" id="prev_img">
                <h2 class="preview-name" id="prev_name">System Club</h2>
                <div class="preview-cat" id="prev_cat">Uncategorized</div>
                
                <div class="preview-details">
                    <div class="p-row">
                        <span class="p-label">Status</span>
                        <span class="p-val" style="color: #4ade80;">Active</span>
                    </div>
                    <div class="p-row">
                        <span class="p-label">President</span>
                        <span class="p-val" id="prev_pres">Pending Assignment</span>
                    </div>
                    <div class="p-row">
                        <span class="p-label">Auto-Assigned Role</span>
                        <span class="p-val" style="color: #ff477e;">EB-President</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('input_name').addEventListener('input', function(e) {
            document.getElementById('prev_name').innerText = e.target.value || 'System Club';
        });

        document.getElementById('input_cat').addEventListener('change', function(e) {
            document.getElementById('prev_cat').innerText = e.target.value || 'Uncategorized';
        });

        document.getElementById('input_pres').addEventListener('change', function(e) {
            let text = e.target.options[e.target.selectedIndex].text;
            document.getElementById('prev_pres').innerText = text.split('(')[0].trim() || 'Pending Assignment';
        });

        document.getElementById('input_logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Update file name in 3D box
                document.getElementById('upload-filename').innerText = file.name;
                document.querySelector('.upload-icon').innerText = '✅';
                document.querySelector('.upload-subtext').innerText = 'File selected successfully!';
                
                // Update Image preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('prev_img').src = event.target.result;
                }
                reader.readAsDataURL(file);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            let status = "<?php echo $alertMessage; ?>";
            let details = "<?php echo isset($error_details) ? addslashes($error_details) : ''; ?>";
            
            if (status === "success") {
                Swal.fire({
                    title: 'Authorization Complete!',
                    text: 'Club successfully created and President assigned as EB-President.',
                    icon: 'success',
                    background: '#161925', color: '#fff', confirmButtonColor: '#ff477e'
                }).then(() => { window.location.href = 'admin_dashboard.php'; });
            } else if (status === "error_db") {
                Swal.fire({ title: 'Database Error', text: details, icon: 'error', background: '#161925', color: '#fff' });
            }
        });
    </script>
</body>
</html>