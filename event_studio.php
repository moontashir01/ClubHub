<?php
include 'connection.php';
mysqli_set_charset($con, "utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_trigger'])) {
    $config_name = mysqli_real_escape_string($con, $_POST['name']);
    $data_array = json_decode($_POST['data_json'], true);
    $image_name = ""; 

    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === 0) {
        $target_dir = "images/";
        if (!file_exists($target_dir)) { mkdir($target_dir, 0777, true); }
        $file_name = time() . "_" . basename($_FILES["image_file"]["name"]);
        if (move_uploaded_file($_FILES["image_file"]["tmp_name"], $target_dir . $file_name)) {
            $image_name = $file_name;
        }
    }

    $data_array['image_path'] = $image_name;
    $final_json = mysqli_real_escape_string($con, json_encode($data_array));
    $sql = "INSERT INTO event_configs (config_name, config_data) VALUES ('$config_name', '$final_json')";
    if (mysqli_query($con, $sql)) { echo "Success! Configuration Saved."; } 
    exit; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Designer - Mandatory Fields</title>
    <style>
        :root { --sidebar-width: 380px; --pos-y: 50%; --bg-img: url(''); --blur: 0px; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; height: 100vh; overflow: hidden; background: #000; }
        #builder { width: var(--sidebar-width); background: #ffffff; padding: 30px; overflow-y: auto; transition: all 0.4s ease; flex-shrink: 0; z-index: 10; }
        .hide-side #builder { margin-left: calc(var(--sidebar-width) * -1); }
        #preview-wrap { flex-grow: 1; position: relative; background: #222; height: 100%; transition: all 0.4s ease; overflow: hidden; }
        #bg-container { position: absolute; inset: 0; background-color: #111; background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), var(--bg-img); background-size: cover; background-position: center var(--pos-y); filter: blur(var(--blur)); z-index: 1; }
        .pane-overlay { position: absolute; inset: 0; display: flex; z-index: 2; }
        #form-edit-view { display: none; align-items: center; justify-content: center; padding: 40px; }
        .form-preview-box { background: white; padding: 50px; border-radius: 15px; width: 100%; max-width: 850px; box-shadow: 0 25px 60px rgba(0,0,0,0.6); max-height: 90vh; overflow-y: auto; }
        .form-grid { display: flex; flex-wrap: wrap; gap: 20px; }
        #full-cover { align-items: flex-end; justify-content: flex-start; }
        .content-box { margin: 0 0 10% 8%; color: white; max-width: 80%; }
        h1 { font-size: 4rem; font-weight: 900; line-height: 1; }
        p { font-size: 1.2rem; margin: 20px 0; opacity: 0.8; }
        .btn-area { display: flex; gap: 10px; }
        .action-btn { padding: 14px 28px; border-radius: 20px; font-weight: bold; border: none; cursor: pointer; text-transform: none; font-size: 0.8rem; }
        .btn-reg { background: #ff4757; color: white; display: none; }
        .btn-ticket { background: transparent; color: white; border: 1px solid white; display: none; }
        .btn-details { background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3); }
        h2 { margin-bottom: 20px; font-size: 22px; }
        .group { margin-bottom: 18px; }
        label { display: block; font-size: 11px; font-weight: bold; color: #777; margin-bottom: 5px; text-transform: uppercase; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
        
        /* Error Highlighting */
        .required-error { border: 2px solid #ff4757 !important; background: #fff1f2; }
        
        #drop-zone { border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 8px; cursor: pointer; font-size: 13px; color: #888; }
        #toggle-btn { position: fixed; top: 20px; left: 20px; background: #000; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; z-index: 100; font-weight: bold; font-size: 12px; }
        #edit-table-btn { margin-top: 10px; background: #eee; border: 1px solid #ccc; padding: 8px; width: 100%; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; display: none; }
        .check-item { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
        .check-item input { width: auto; margin: 0; }
    </style>
</head>
<body>

<button id="toggle-btn" onclick="toggleView()">HIDE/SHOW CONFIG</button>

<div id="builder">
    <h2 style="margin-top:50px">Config</h2>
    <div id="event-config">
        <div class="group"><label>Cover Image (Required)</label><div id="drop-zone" onclick="document.getElementById('fileIn').click()">Drop or Click Image</div><input type="file" id="fileIn" hidden accept="image/*"></div>
        <div class="group"><label>Vertical Position</label><input type="range" id="yRange" min="0" max="100" value="50"></div>
        <div class="group"><label>Event Name (Required)</label><input type="text" id="nameIn" placeholder="Summer Fest" oninput="updateUI()"></div>
        <div class="group"><label>Event Name Font</label>
            <select id="fontSelect" onchange="updateUI()">
                <option value="'Segoe UI', sans-serif">Modern</option>
                <option value="'Times New Roman', serif">Classic</option>
                <option value="'Courier New', monospace">Technical</option>
            </select>
        </div>
        <div class="group"><label>Event Name Color</label><input type="color" id="colorIn" value="#ffffff" oninput="updateUI()"></div>
        <div class="group"><label>Description (Required)</label><input type="text" id="descIn" oninput="updateUI()"></div>
        <div class="group">
            <label>Show Buttons</label>
            <div>
                <label class="check-item"><input type="checkbox" id="regToggle" onchange="updateUI()"> Register Button</label>
                <button id="edit-table-btn" onclick="toggleEditPlace()">EDIT REGISTRATION FORM</button>
                <label class="check-item"><input type="checkbox" id="tktToggle" onchange="updateUI()"> Ticket Button</label>
            </div>
        </div>
        <div class="group"><label>Long Detail (Required)</label><textarea id="detailIn" rows="4" oninput="updateUI()"></textarea></div>
        <button class="action-btn" style="width:100%; background:#2ed573; color:white;" onclick="saveJSON()">Submit Config</button>
    </div>

    <div id="form-settings" style="display:none;">
        <div class="color-row group">
            <div><label>Submit Btn Color</label><input type="color" id="formBtnIn" value="#ff4757" oninput="renderForm(); updateUI();"></div>
        </div>
        <div class="group"><label>Form Title</label><input type="text" id="formTitleIn" value="Registration Form" oninput="renderForm()"></div>
        <div id="fields-container"></div>
        <button class="action-btn" style="width:100%; background:#ff4757; color:#fff;" onclick="toggleEditPlace()">Save & Back</button>
    </div>
</div>

<div id="preview-wrap">
    <div id="bg-container"></div>
    <div id="full-cover" class="pane-overlay">
        <div class="content-box">
            <h1 id="view-name">EVENT NAME</h1>
            <p id="view-desc">Briefly describe your event.</p>
            <div class="btn-area">
                <button id="view-reg" class="action-btn btn-reg">Register</button>
                <button id="view-tkt" class="action-btn btn-ticket">Tickets</button>
                <button class="action-btn btn-details">Details</button>
            </div>
        </div>
    </div>
    <div id="form-edit-view" class="pane-overlay">
        <div class="form-preview-box" id="f-box">
            <h2 id="form-title-display">Registration Form</h2>
            <div class="form-grid" id="form-grid"></div>
            <button id="form-submit-btn" class="action-btn" style="width:100%; margin-top:30px; color:white;">Submit Registration</button>
        </div>
    </div>
</div>

<script>
    let fields = [{ label: "Full Name", isFull: true, type: 'text' }];

    function toggleView() { document.body.classList.toggle('hide-side'); }
    function toggleEditPlace() {
        const isFormOpen = document.getElementById('form-edit-view').style.display === 'flex';
        document.getElementById('form-edit-view').style.display = isFormOpen ? 'none' : 'flex';
        document.getElementById('full-cover').style.display = isFormOpen ? 'flex' : 'none';
        document.getElementById('event-config').style.display = isFormOpen ? 'block' : 'none';
        document.getElementById('form-settings').style.display = isFormOpen ? 'none' : 'block';
    }

    function renderForm() {
        document.getElementById('form-submit-btn').style.backgroundColor = document.getElementById('formBtnIn').value;
    }

    function updateUI() {
        const nameEl = document.getElementById('view-name');
        nameEl.innerText = document.getElementById('nameIn').value || "EVENT NAME";
        nameEl.style.color = document.getElementById('colorIn').value;
        nameEl.style.fontFamily = document.getElementById('fontSelect').value;
        
        const regBtn = document.getElementById('view-reg');
        regBtn.style.display = document.getElementById('regToggle').checked ? 'block' : 'none';
        regBtn.style.backgroundColor = document.getElementById('formBtnIn').value;
        
        document.getElementById('edit-table-btn').style.display = document.getElementById('regToggle').checked ? 'block' : 'none';
        document.getElementById('view-tkt').style.display = document.getElementById('tktToggle').checked ? 'block' : 'none';
        
        // Remove error highlights as user types
        document.querySelectorAll('.required-error').forEach(el => {
            if(el.value && el.value.trim() !== "") el.classList.remove('required-error');
        });
    }

    function saveJSON() {
        const nameIn = document.getElementById('nameIn');
        const descIn = document.getElementById('descIn');
        const detailIn = document.getElementById('detailIn');
        const fileIn = document.getElementById('fileIn');
        const dropZone = document.getElementById('drop-zone');

        let hasError = false;

        // Reset Styles
        [nameIn, descIn, detailIn, dropZone].forEach(el => el.classList.remove('required-error'));

        // Validate Text Fields
        if (!nameIn.value.trim()) { nameIn.classList.add('required-error'); hasError = true; }
        if (!descIn.value.trim()) { descIn.classList.add('required-error'); hasError = true; }
        if (!detailIn.value.trim()) { detailIn.classList.add('required-error'); hasError = true; }

        // Validate Photo
        if (fileIn.files.length === 0) {
            dropZone.classList.add('required-error');
            hasError = true;
        }

        if (hasError) {
            alert("Please provide a Name, Description, Detail, and a Cover Photo.");
            return;
        }

        const formData = new FormData();
        const configData = {
            description: descIn.value,
            longDetail: detailIn.value,
            fields: fields,
            yPos: document.getElementById('yRange').value,
            font: document.getElementById('fontSelect').value,
            color: document.getElementById('colorIn').value,
            regToggle: document.getElementById('regToggle').checked,
            tktToggle: document.getElementById('tktToggle').checked,
            regBtnColor: document.getElementById('formBtnIn').value 
        };
        
        formData.append('save_trigger', 'true');
        formData.append('name', nameIn.value);
        formData.append('data_json', JSON.stringify(configData));
        formData.append('image_file', fileIn.files[0]);

        fetch(window.location.href, { method: 'POST', body: formData })
        .then(r => r.text())
        .then(txt => {
            alert(txt);
            if(txt.includes("Success")) window.location.reload();
        });
    }

    document.getElementById('fileIn').onchange = e => {
        if(e.target.files.length > 0) {
            document.getElementById('drop-zone').classList.remove('required-error');
            document.getElementById('drop-zone').innerText = "Image Selected: " + e.target.files[0].name;
            const reader = new FileReader();
            reader.onload = ev => document.documentElement.style.setProperty('--bg-img', `url(${ev.target.result})`);
            reader.readAsDataURL(e.target.files[0]);
        }
    };
    document.getElementById('yRange').oninput = function() { document.documentElement.style.setProperty('--pos-y', this.value + "%"); };
</script>
</body>
</html>