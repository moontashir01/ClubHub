<?php
session_start();
include 'connection.php';
mysqli_set_charset($con, "utf8mb4");

// 1. Ensure upload directories exist
$panel_dir = "images/panel/";
$gallery_dir = "images/gallery/";
if (!is_dir($panel_dir)) mkdir($panel_dir, 0777, true);
if (!is_dir($gallery_dir)) mkdir($gallery_dir, 0777, true);

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_club'])) {
    $club_id = (int)$_POST['club_id'];
    
    // Process Executives (Panel)
    $panel_names = $_POST['panel_name'] ?? [];
    $panel_roles = $_POST['panel_role'] ?? [];
    $existing_panel_imgs = $_POST['existing_panel_img'] ?? [];
    $panel_members = [];

    for ($i = 0; $i < count($panel_names); $i++) {
        if (!empty(trim($panel_names[$i]))) {
            $img_path = $existing_panel_imgs[$i] ?? ''; 

            if (isset($_FILES['panel_images']['name'][$i]) && $_FILES['panel_images']['error'][$i] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['panel_images']['tmp_name'][$i];
                $ext = pathinfo($_FILES['panel_images']['name'][$i], PATHINFO_EXTENSION);
                $new_name = uniqid() . '_panel_' . time() . '.' . $ext;
                $target_path = $panel_dir . $new_name;
                if (move_uploaded_file($tmp, $target_path)) {
                    $img_path = $target_path; 
                }
            }

            $panel_members[] = [
                'name' => trim(htmlspecialchars($panel_names[$i])),
                'role' => trim(htmlspecialchars($panel_roles[$i])),
                'image' => $img_path
            ];
        }
    }

    // Process Gallery
    $final_gallery = $_POST['existing_gallery'] ?? [];

    if (!empty($_FILES['gallery_images']['name'][0])) {
        foreach ($_FILES['gallery_images']['name'] as $key => $name) {
            if ($_FILES['gallery_images']['error'][$key] === UPLOAD_ERR_OK) {
                $tmp = $_FILES['gallery_images']['tmp_name'][$key];
                $ext = pathinfo($name, PATHINFO_EXTENSION);
                $new_name = uniqid() . '_gallery_' . time() . '.' . $ext;
                $target_path = $gallery_dir . $new_name;
                if (move_uploaded_file($tmp, $target_path)) {
                    $final_gallery[] = $target_path;
                }
            }
        }
    }

    $club_data_array = [
        'info' => trim($_POST['club_info']), // Storing raw HTML from the rich editor
        'events' => trim($_POST['club_events']), // Storing raw HTML from the rich editor
        'panel' => $panel_members,
        'gallery' => array_values($final_gallery) 
    ];

    $final_json = mysqli_real_escape_string($con, json_encode($club_data_array, JSON_UNESCAPED_UNICODE));
    $update = "UPDATE clubs SET club_data = '$final_json' WHERE club_id = $club_id";
              
    if (mysqli_query($con, $update)) {
        $msg = "<div class='toast success'>Club Updated Successfully! ✓</div>";
    } else {
        $msg = "<div class='toast error'>Error: " . mysqli_error($con) . "</div>";
    }
}

// Fetch selected club data
$selected_id = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
$club_record = null;
$parsed_data = ['info' => '', 'events' => '', 'panel' => [], 'gallery' => []];

if ($selected_id > 0) {
    $res = mysqli_query($con, "SELECT club_name, club_data FROM clubs WHERE club_id = $selected_id");
    if ($club_record = mysqli_fetch_assoc($res)) {
        if (!empty($club_record['club_data'])) {
            $parsed_data = json_decode($club_record['club_data'], true);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Club Hub | Editor</title>
    <style>
        :root { --pink: #ff4d8d; --dark-bg: #0b0b13; --card: #161621; --border: rgba(255, 77, 141, 0.3); }
        body { margin: 0; font-family: 'Segoe UI', sans-serif; background: var(--dark-bg); color: white; padding-bottom: 100px; }
        
        /* Nav matching dashboard */
        nav { display: flex; justify-content: space-between; padding: 20px 5%; background: linear-gradient(to bottom, rgba(0,0,0,0.9), transparent); position: sticky; top: 0; width: 90%; z-index: 1000; align-items: center; margin: 0 auto; }
        .logo { font-size: 26px; font-weight: 900; letter-spacing: -1px; }
        .nav-btn { background: var(--pink); padding: 8px 20px; border-radius: 20px; font-weight: bold; color: white; text-decoration: none; border: none; cursor: pointer; transition: 0.3s; font-size: 14px; }
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255, 77, 141, 0.4); }

        .container { max-width: 1000px; margin: 50px auto; padding: 0 5%; }
        h1 { font-size: 3rem; text-align: center; color: white; margin-bottom: 50px; text-transform: uppercase; }
        .section-title { font-size: 1.5rem; margin-bottom: 15px; font-weight: 800; border-left: 4px solid var(--pink); padding-left: 15px; text-transform: uppercase; }
        
        /* Vertical List for Selection */
        .club-list { display: flex; flex-direction: column; gap: 15px; margin-top: 40px; max-width: 600px; margin-left: auto; margin-right: auto; }
        .list-item { background: var(--card); padding: 20px; border-radius: 10px; color: white; text-decoration: none; font-size: 1.2rem; font-weight: bold; border: 1px solid transparent; transition: 0.3s; display: flex; justify-content: space-between; align-items: center; }
        .list-item:hover { transform: translateX(10px); border-color: var(--pink); background: rgba(255, 77, 141, 0.1); }
        .list-item::after { content: '➔'; color: var(--pink); }

        /* Rich Text Editor */
        .rich-editor-wrapper { background: #000; border: 1px dashed var(--border); border-radius: 15px; margin-bottom: 40px; overflow: hidden; }
        .toolbar { background: var(--card); padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1); display: flex; gap: 10px; }
        .toolbar button { background: transparent; border: 1px solid rgba(255,255,255,0.2); color: white; border-radius: 5px; width: 35px; height: 35px; cursor: pointer; font-size: 16px; transition: 0.2s; }
        .toolbar button:hover { background: rgba(255,255,255,0.1); border-color: var(--pink); }
        .editor-content { padding: 20px; min-height: 150px; outline: none; line-height: 1.6; font-size: 1.1rem; }
        .editor-content:focus { background: rgba(255,77,141,0.02); }

        /* Panel & Gallery */
        .panel-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .panel-card { background: var(--card); padding: 20px; border-radius: 15px; text-align: center; border: 1px dashed var(--border); position: relative; }
        .dp-dropzone { width: 100px; height: 100px; border-radius: 50%; border: 2px dashed var(--pink); margin: 0 auto 15px auto; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.5); cursor: pointer; overflow: hidden; position: relative; background-size: cover; background-position: center; }
        .dp-dropzone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .panel-card input[type="text"] { width: 100%; background: transparent; border: none; border-bottom: 1px solid #444; color: white; text-align: center; padding: 8px; margin-bottom: 10px; font-size: 1rem; outline: none; transition: 0.3s; }
        .panel-card input[type="text"]:focus { border-bottom-color: var(--pink); }
        .btn-remove { position: absolute; top: 10px; right: 10px; background: #e74c3c; color: white; border: none; border-radius: 50%; width: 25px; height: 25px; cursor: pointer; font-weight: bold; z-index: 10; }
        .btn-add { background: transparent; border: 1px dashed white; color: white; padding: 15px; border-radius: 15px; cursor: pointer; width: 100%; font-weight: bold; font-size: 1.1rem; margin-bottom: 50px; }

        .gallery-dropzone { background: rgba(0,0,0,0.5); border: 2px dashed var(--pink); border-radius: 20px; padding: 40px; text-align: center; cursor: pointer; position: relative; margin-bottom: 30px; }
        .gallery-dropzone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; }
        .gallery-preview-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
        .gallery-item { position: relative; height: 120px; border-radius: 10px; overflow: hidden; border: 1px solid #333; }
        .gallery-item img { width: 100%; height: 100%; object-fit: cover; }

        .toast { position: fixed; bottom: 20px; right: 20px; padding: 15px 25px; border-radius: 10px; font-weight: bold; z-index: 2000; transition: 0.5s; }
        .success { background: #2ecc71; color: #000; }
    </style>
</head>
<body>

    <?= $msg ?? '' ?>

    <?php if ($selected_id === 0): ?>
        <nav>
            <div class="logo">Club Hub</div>
            <div>
                <a href="dashboard.php" class="nav-btn">Back to Portal</a>
            </div>
        </nav>
        
        <div class="container">
            <h1>Select Club to Edit</h1>
            <div class="club-list">
                <?php
                $clubs = mysqli_query($con, "SELECT club_id, club_name FROM clubs ORDER BY club_name");
                while ($c = mysqli_fetch_assoc($clubs)) {
                    echo "<a href='?edit_id={$c['club_id']}' class='list-item'>{$c['club_name']}</a>";
                }
                ?>
            </div>
        </div>

    <?php else: ?>
        <form method="POST" enctype="multipart/form-data" id="editorForm">
            <nav>
                <div class="logo">Club Hub</div>
                <div>
                    <a href="club_info_editor.php" style="color:white; text-decoration:none; margin-right:20px; font-weight:bold;">← Select Another</a>
                    <input type="hidden" name="club_id" value="<?= $selected_id ?>">
                    <button type="submit" name="update_club" class="nav-btn">Save Changes</button>
                </div>
            </nav>

            <div class="container">
                <h1><?= htmlspecialchars($club_record['club_name']) ?></h1>

                <div class="section-title">About Us</div>
                <div class="rich-editor-wrapper">
                    <div class="toolbar">
                        <button type="button" onclick="formatText('bold')"><b>B</b></button>
                        <button type="button" onclick="formatText('italic')"><i>I</i></button>
                        <button type="button" onclick="formatText('underline')"><u>U</u></button>
                    </div>
                    <div class="editor-content" contenteditable="true" id="info-editor"><?= $parsed_data['info'] ?? '' ?></div>
                    <input type="hidden" name="club_info" id="info-input">
                </div>

                <div class="section-title">Upcoming & Past Events</div>
                <div class="rich-editor-wrapper">
                    <div class="toolbar">
                        <button type="button" onclick="formatText('bold')"><b>B</b></button>
                        <button type="button" onclick="formatText('italic')"><i>I</i></button>
                        <button type="button" onclick="formatText('underline')"><u>U</u></button>
                    </div>
                    <div class="editor-content" contenteditable="true" id="events-editor"><?= $parsed_data['events'] ?? '' ?></div>
                    <input type="hidden" name="club_events" id="events-input">
                </div>

                <div class="section-title">Executives</div>
                <div class="panel-grid" id="panel-container">
                    <?php
                    $panels = $parsed_data['panel'] ?? [];
                    foreach ($panels as $p): 
                        $bg = !empty($p['image']) ? "background-image: url('{$p['image']}');" : "";
                    ?>
                    <div class="panel-card">
                        <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                        <div class="dp-dropzone" style="<?= $bg ?>" ondragover="this.style.borderColor='white'" ondragleave="this.style.borderColor='var(--pink)'">
                            <?php if(empty($p['image'])) echo "<span style='font-size:10px; color:#aaa;'>Drop Photo</span>"; ?>
                            <input type="file" name="panel_images[]" accept="image/*" onchange="previewDP(this)">
                            <input type="hidden" name="existing_panel_img[]" value="<?= htmlspecialchars($p['image']) ?>">
                        </div>
                        <input type="text" name="panel_name[]" value="<?= htmlspecialchars($p['name']) ?>" placeholder="Executive Name" required>
                        <input type="text" name="panel_role[]" value="<?= htmlspecialchars($p['role']) ?>" placeholder="Role (e.g. President)" required style="color:var(--pink); font-size:12px; text-transform:uppercase; font-weight:bold;">
                    </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="btn-add" onclick="addExecutive()">+ Add Executive</button>

                <div class="section-title">Event Gallery</div>
                <div class="gallery-dropzone" id="galleryDrop">
                    <h3 style="margin:0; color:var(--pink);">+ Drag & Drop Photos Here</h3>
                    <input type="file" name="gallery_images[]" multiple accept="image/*" id="galleryInput">
                </div>

                <div class="gallery-preview-grid" id="galleryPreview">
                    <?php foreach($parsed_data['gallery'] as $img): ?>
                        <div class="gallery-item">
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                            <img src="<?= htmlspecialchars($img) ?>">
                            <input type="hidden" name="existing_gallery[]" value="<?= htmlspecialchars($img) ?>">
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </form>
    <?php endif; ?>

    <script>
        // Rich Text Formatting
        function formatText(command) {
            document.execCommand(command, false, null);
        }

        // Before submitting, copy the HTML from the editable divs into the hidden inputs
        const form = document.getElementById('editorForm');
        if (form) {
            form.addEventListener('submit', function() {
                document.getElementById('info-input').value = document.getElementById('info-editor').innerHTML;
                document.getElementById('events-input').value = document.getElementById('events-editor').innerHTML;
            });
        }

        // Preview DP
        function previewDP(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    input.parentElement.style.backgroundImage = `url('${e.target.result}')`;
                    let span = input.parentElement.querySelector('span');
                    if(span) span.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Add Executive
        function addExecutive() {
            const container = document.getElementById('panel-container');
            const card = document.createElement('div');
            card.className = 'panel-card';
            card.innerHTML = `
                <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                <div class="dp-dropzone" ondragover="this.style.borderColor='white'" ondragleave="this.style.borderColor='var(--pink)'">
                    <span style='font-size:10px; color:#aaa;'>Drop Photo</span>
                    <input type="file" name="panel_images[]" accept="image/*" onchange="previewDP(this)">
                    <input type="hidden" name="existing_panel_img[]" value="">
                </div>
                <input type="text" name="panel_name[]" placeholder="Executive Name" required>
                <input type="text" name="panel_role[]" placeholder="Role" required style="color:var(--pink); font-size:12px; text-transform:uppercase; font-weight:bold;">
            `;
            container.appendChild(card);
        }

        // Gallery Drag/Drop
        const galleryInput = document.getElementById('galleryInput');
        const galleryPreview = document.getElementById('galleryPreview');
        if(galleryInput) {
            galleryInput.addEventListener('change', function() {
                for (const file of this.files) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const item = document.createElement('div');
                        item.className = 'gallery-item';
                        item.innerHTML = `
                            <button type="button" class="btn-remove" onclick="this.parentElement.remove()">X</button>
                            <img src="${e.target.result}">
                        `;
                        galleryPreview.appendChild(item);
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        const toast = document.querySelector('.toast');
        if (toast) {
            setTimeout(() => { toast.style.opacity = '0'; setTimeout(() => toast.remove(), 500); }, 3000);
        }
    </script>
</body>
</html>