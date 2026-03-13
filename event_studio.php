<?php
// ... [KEEP YOUR EXISTING PHP HEADER EXACTLY AS IT IS] ...
session_start();
include 'connection.php';
mysqli_set_charset($con, "utf8mb4");

if(!isset($_SESSION['Club_id'])){ $_SESSION['Club_id'] = '1'; }
$club_id = $_SESSION['Club_id'];

$event_query = mysqli_query($con, "
    SELECT e.event_id, e.event_name 
    FROM events e 
    LEFT JOIN event_configs ec ON e.event_id = ec.event_id 
    WHERE e.club_id = '$club_id' 
    AND ec.event_id IS NULL 
    ORDER BY e.event_name ASC
");

$events_list = [];
if ($event_query) {
    while ($row = mysqli_fetch_assoc($event_query)) { $events_list[] = $row; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_trigger'])) {
    $event_id = mysqli_real_escape_string($con, $_POST['event_id']);
    $slider_endtime = mysqli_real_escape_string($con, $_POST['slider_endtime']);
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
    
    $sql = "INSERT INTO event_configs (event_id, config_name, config_data, slider_endtime) 
            VALUES ('$event_id', '$config_name', '$final_json', '$slider_endtime')
            ON DUPLICATE KEY UPDATE 
            config_name = VALUES(config_name), config_data = VALUES(config_data), slider_endtime = VALUES(slider_endtime)";

    if (mysqli_query($con, $sql)) { echo "Success! Configuration Saved."; } 
    else { echo "Database Error: " . mysqli_error($con); }
    exit; 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Event Designer</title>
    <style>
        :root { --sidebar-width: 380px; --pos-y: 50%; --bg-img: url(''); --blur: 0px; --h-size: 1.4rem; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; display: flex; height: 100vh; overflow: hidden; background: #000; }

        /* RESTORED ERROR STYLING */
        .required-error { border: 2px solid #ff4757 !important; box-shadow: 0 0 10px rgba(255, 71, 87, 0.4); }
        #drop-zone.required-error { border: 2px dashed #ff4757 !important; }

        #builder { width: var(--sidebar-width); background: #ffffff; padding: 30px; overflow-y: auto; transition: 0.4s; flex-shrink: 0; z-index: 10; border-right: 1px solid #ddd; }
        .hide-side #builder { margin-left: calc(var(--sidebar-width) * -1); }

        #preview-wrap { flex-grow: 1; position: relative; background: #222; height: 100%; transition: 0.4s; overflow: hidden; }
        #bg-container { position: absolute; inset: 0; background-color: #111; background-image: linear-gradient(rgba(0,0,0,0.3), rgba(0,0,0,0.7)), var(--bg-img); background-size: cover; background-position: center var(--pos-y); filter: blur(var(--blur)); transition: filter 0.5s ease; z-index: 1; }
        .pane-overlay { position: absolute; inset: 0; display: flex; z-index: 2; }

        #form-preview-view, #details-view { display: none; align-items: center; justify-content: center; padding: 40px; background: rgba(0,0,0,0.7); }
        
        .preview-box-dark { background: #000; padding: 50px; border-radius: 15px; width: 100%; max-width: 800px; border: 2px solid #ff47bc; box-shadow: 0 0 25px rgba(255, 71, 188, 0.5); max-height: 90vh; overflow-y: auto; position: relative; color: white; }
        .preview-box-dark h2.main-head { text-align: center; margin-bottom: 30px; font-size: 32px; color: #ff47bc; text-transform: uppercase; letter-spacing: 2px; }
        
        .detail-h { color: #ff47bc; font-size: var(--h-size); font-weight: bold; display: block; margin-top: 25px; margin-bottom: 10px; border-bottom: 1px solid rgba(255, 71, 188, 0.3); padding-bottom: 5px; }

        .form-preview-box { background: white; padding: 50px; border-radius: 15px; width: 100%; max-width: 850px; box-shadow: 0 25px 60px rgba(0,0,0,0.6); max-height: 90vh; overflow-y: auto; position: relative; }
        .close-preview { position: absolute; top: 20px; right: 20px; background: #ff47bc; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; color: white; text-transform: uppercase; font-size: 10px; }

        .form-grid { display: flex; flex-wrap: wrap; gap: 20px; }
        .form-item { margin-bottom: 5px; }
        .full { flex: 0 0 100%; }
        .half { flex: 0 0 calc(50% - 10px); }

        #full-cover { align-items: flex-end; justify-content: flex-start; display: flex; }
        .content-box { margin: 0 0 10% 8%; color: white; max-width: 80%; }
        h1 { font-size: 4rem; text-transform: uppercase; font-weight: 900; line-height: 1; }
        p { font-size: 1.2rem; margin: 20px 0; opacity: 0.8; }

        .btn-area { display: flex; gap: 10px; }
        .action-btn { padding: 14px 28px; border-radius: 20px; font-weight: bold; border: none; cursor: pointer; text-transform: uppercase; font-size: 0.8rem; }
        .btn-reg { background: #ff4757; color: white; display: none; }
        .btn-ticket { background: transparent; color: white; border: 1px solid white; display: none; }
        .btn-details { background: rgba(255,255,255,0.2); color: white; backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.3); }

        .group { margin-bottom: 18px; position: relative; }
        label { display: block; font-size: 11px; font-weight: bold; color: #777; margin-bottom: 5px; text-transform: uppercase; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; outline: none; }
        
        #drop-zone { border: 2px dashed #ccc; padding: 20px; text-align: center; border-radius: 8px; cursor: pointer; font-size: 13px; color: #888; }
        #toggle-btn { position: fixed; top: 20px; left: 20px; background: #000; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; z-index: 100; font-weight: bold; font-size: 12px; }
        #edit-table-btn { margin-top: 10px; background: #eee; border: 1px solid #ccc; padding: 8px; width: 100%; border-radius: 4px; cursor: pointer; font-size: 11px; font-weight: bold; display: none; }
        
        .field-block { border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px; position: relative; }
        .remove-txt { color: #ff4757; font-size: 10px; cursor: pointer; float: right; text-transform: uppercase; font-weight: bold; }
        .check-item { display: flex; align-items: center; gap: 8px; margin-bottom: 5px; }
        .check-item input { width: auto; margin: 0; }
        .color-row { display: flex; gap: 5px; }
        .color-row div { flex: 1; }
        .helper-btn { background: #f0f0f0; border: 1px solid #ccc; padding: 4px 8px; font-size: 10px; border-radius: 3px; cursor: pointer; margin-bottom: 5px; display: inline-block; }
    </style>
</head>
<body>

<button id="toggle-btn" onclick="toggleView()">HIDE/SHOW CONFIG</button>

<div id="builder">
    <h2 style="margin-top:50px;margin-bottom:15px;font-size:30px" id="side-title">CONFIGURATION</h2>
    
    <div id="event-config">
        <div class="group"><label>Link to Event (Required)</label><select id="eventSelect" onchange="this.classList.remove('required-error'); updateUI()"><option value="">-- Select Event --</option><?php foreach ($events_list as $ev): ?><option value="<?= $ev['event_id'] ?>"><?= htmlspecialchars($ev['event_name']) ?></option><?php endforeach; ?></select></div>
        <div class="group"><label>Slider End Time (Required)</label><input type="datetime-local" id="endTimeIn" onchange="this.classList.remove('required-error'); updateUI()"></div>
        <div class="group"><label>Cover Image</label><div id="drop-zone" onclick="document.getElementById('fileIn').click()">Drop or Click Image</div><input type="file" id="fileIn" hidden accept="image/*"></div>
        <div class="group"><label>Vertical Position</label><input type="range" id="yRange" min="0" max="100" value="50"></div>
        <div class="group"><label>Event Name</label><input type="text" id="nameIn" placeholder="Summer Fest" oninput="this.classList.remove('required-error'); updateUI()"></div>
        <div class="group"><label>Event Name Font</label>
            <select id="fontSelect" onchange="updateUI()">
                <option value="'Segoe UI', sans-serif">Modern</option>
                <option value="'Times New Roman', serif">Classic</option>
                <option value="'Courier New', monospace">Technical</option>
            </select>
        </div>
        <div class="group"><label>Event Name Color</label><input type="color" id="colorIn" value="#ffffff" oninput="updateUI()" style="height:40px; padding:2px;"></div>
        <div class="group"><label>Description</label><input type="text" id="descIn" oninput="this.classList.remove('required-error'); updateUI()"></div>
        <div class="group">
            <label>Buttons</label>
            <div style="font-size: 13px;">
                <label class="check-item"><input type="checkbox" id="regToggle" onchange="updateUI()"> Register Button</label>
                <button id="edit-table-btn" onclick="toggleEditPlace()">EDIT REGISTRATION FORM</button>
                <label class="check-item" style="margin-top:5px;"><input type="checkbox" id="tktToggle" onchange="updateUI()"> Ticket Button</label>
            </div>
        </div>
        
        <div class="group">
            <label>Long Detail</label>
            <div class="helper-btn" onclick="addHeaderTag()">+ Add Header Tag</div>
            <textarea id="detailIn" rows="6" oninput="this.classList.remove('required-error'); updateUI()" placeholder="Use [+H] Text [H+] for headers"></textarea>
        </div>
        <div class="group">
            <label>Detail Header Size</label>
            <input type="range" id="hSizeRange" min="10" max="60" value="22" oninput="updateHSize()">
        </div>

        <button class="action-btn" style="width:100%; background:#2ed573; color:white;" onclick="saveJSON()">Submit Config</button>
    </div>

    <div id="form-settings" style="display:none;">
        <div class="color-row group">
            <div><label>Box BG</label><input type="color" id="formBgIn" value="#ffffff" oninput="renderForm()"></div>
            <div><label>Title Col</label><input type="color" id="formTitleColIn" value="#222222" oninput="renderForm()"></div>
            <div><label>Label Col</label><input type="color" id="labelColIn" value="#444444" oninput="renderForm()"></div>
        </div>
        <div class="color-row group">
            <div><label>Field BG</label><input type="color" id="fieldBgIn" value="#fdfdfd" oninput="renderForm()"></div>
            <div><label>Field Txt</label><input type="color" id="fieldTextIn" value="#444444" oninput="renderForm()"></div>
            <div><label>Submit Btn</label><input type="color" id="formBtnIn" value="#ff4757" oninput="renderForm()"></div>
        </div>
        <div class="group"><label>Form Title Text</label><input type="text" id="formTitleIn" value="Registration Form" oninput="renderForm()"></div>
        <div id="fields-container"></div>
        <div class="color-row group" style="margin-top:10px;">
            <button class="action-btn" style="width:100%; background:#333; color:#fff; font-size:9px;" onclick="addField('text')">+ Add Text</button>
            <button class="action-btn" style="width:100%; background:#333; color:#fff; font-size:9px;" onclick="addField('dropdown')">+ Add Dropdown</button>
        </div>
        <button class="action-btn" style="width:100%; background:#ff4757; color:#fff; font-size:10px; margin-top:10px;" onclick="toggleEditPlace()">Save & Back</button>
    </div>
</div>

<div id="preview-wrap">
    <div id="bg-container"></div>
    <div id="full-cover" class="pane-overlay">
        <div class="content-box">
            <h1 id="view-name">EVENT NAME</h1>
            <p id="view-desc">Briefly describe your event.</p>
            <div class="btn-area">
                <button id="view-reg" class="action-btn btn-reg" onclick="toggleFormPreview(true)">Register</button>
                <button id="view-tkt" class="action-btn btn-ticket">Tickets</button>
                <button class="action-btn btn-details" onclick="toggleDetailsView(true)">Details</button>
            </div>
        </div>
    </div>

    <div id="form-preview-view" class="pane-overlay">
        <div class="form-preview-box" id="f-box">
            <button class="close-preview" onclick="toggleFormPreview(false)">CLOSE PREVIEW</button>
            <h2 id="form-title-display" style="margin-bottom:30px; font-size: 28px;">Registration Form</h2>
            <div class="form-grid" id="form-grid"></div>
            <button id="form-submit-btn" class="action-btn" style="display:block; width:100%; margin-top:30px; height: 50px; font-size: 1rem; color:white;">Submit Registration</button>
        </div>
    </div>

    <div id="details-view" class="pane-overlay">
        <div class="preview-box-dark">
            <button class="close-preview" onclick="toggleDetailsView(false)">CLOSE</button>
            <h2 class="main-head">Event Details</h2>
            <div id="details-content" style="line-height:1.6; color:white; font-size:1.1rem; white-space: pre-wrap;"></div>
        </div>
    </div>
</div>

<script>
    let fields = [{ label: "Full Name", isFull: true, type: 'text' }, { label: "Department", isFull: false, type: 'dropdown', options: "Option 1\nOption 2" }];

    // IMAGE PREVIEW LOGIC
    document.getElementById('fileIn').onchange = e => {
        if(e.target.files.length > 0) {
            const dz = document.getElementById('drop-zone');
            dz.innerText = "File: " + e.target.files[0].name;
            dz.classList.remove('required-error');
            
            const r = new FileReader(); 
            r.onload = ev => {
                document.documentElement.style.setProperty('--bg-img', `url(${ev.target.result})`);
            }; 
            r.readAsDataURL(e.target.files[0]);
        }
    };

    function updateHSize() {
        const size = document.getElementById('hSizeRange').value;
        document.documentElement.style.setProperty('--h-size', (size / 16) + 'rem');
        if(document.getElementById('details-view').style.display === 'flex') toggleDetailsView(true);
    }

    function addHeaderTag() {
        const txt = document.getElementById('detailIn');
        const start = txt.selectionStart;
        const end = txt.selectionEnd;
        const insert = "[+H] Heading Here [H+]";
        txt.value = txt.value.substring(0, start) + insert + txt.value.substring(end);
        updateUI();
    }

    function toggleView() { document.body.classList.toggle('hide-side'); }

    function toggleEditPlace() {
        const isCurrentlyDesigning = document.getElementById('form-settings').style.display === 'block';
        if (!isCurrentlyDesigning) {
            document.getElementById('event-config').style.display = 'none';
            document.getElementById('form-settings').style.display = 'block';
            document.getElementById('side-title').innerText = "Form Designer";
            toggleFormPreview(true); 
            renderFieldSettings();
        } else {
            document.getElementById('event-config').style.display = 'block';
            document.getElementById('form-settings').style.display = 'none';
            document.getElementById('side-title').innerText = "Config";
            toggleFormPreview(false); 
        }
    }

    function toggleFormPreview(show) {
        document.getElementById('form-preview-view').style.display = show ? 'flex' : 'none';
        document.getElementById('full-cover').style.display = show ? 'none' : 'flex';
        document.documentElement.style.setProperty('--blur', show ? '15px' : '0px');
        if(show) renderForm();
    }

    function toggleDetailsView(show) {
        document.getElementById('details-view').style.display = show ? 'flex' : 'none';
        document.getElementById('full-cover').style.display = show ? 'none' : 'flex';
        document.documentElement.style.setProperty('--blur', show ? '15px' : '0px');
        if(show) {
            let rawText = document.getElementById('detailIn').value || "No details provided.";
            let formatted = rawText.replace(/\[\+H\](.*?)\[H\+\]/g, '<span class="detail-h">$1</span>');
            document.getElementById('details-content').innerHTML = formatted;
        }
    }

    function addField(type) {
        fields.push({ label: "New " + type, isFull: true, type: type, options: type === 'dropdown' ? "Option 1\nOption 2" : "" });
        renderFieldSettings();
        renderForm();
    }

    function removeField(i) {
        fields.splice(i, 1);
        renderFieldSettings();
        renderForm();
    }

    function renderFieldSettings() {
        const container = document.getElementById('fields-container');
        container.innerHTML = '';
        fields.forEach((f, i) => {
            const div = document.createElement('div'); div.className = 'field-block';
            let extra = f.type === 'dropdown' ? `<div class="group"><label>Options</label><textarea rows="2" oninput="fields[${i}].options=this.value; renderForm()">${f.options}</textarea></div>` : '';
            div.innerHTML = `<span class="remove-txt" onclick="removeField(${i})">Remove</span><div class="group"><label>${f.type} Label</label><input type="text" value="${f.label}" oninput="fields[${i}].label=this.value; renderForm()"></div>${extra}<label class="check-item"><input type="checkbox" ${f.isFull ? 'checked' : ''} onchange="fields[${i}].isFull=this.checked; renderForm()"> Full Width</label>`;
            container.appendChild(div);
        });
    }

    function renderForm() {
        const bg = document.getElementById('formBgIn').value;
        const headCol = document.getElementById('formTitleColIn').value;
        const labCol = document.getElementById('labelColIn').value;
        const fBg = document.getElementById('fieldBgIn').value;
        const fTxt = document.getElementById('fieldTextIn').value;
        const btnC = document.getElementById('formBtnIn').value;

        document.getElementById('f-box').style.backgroundColor = bg;
        document.getElementById('form-title-display').style.color = headCol;
        document.getElementById('form-title-display').innerText = document.getElementById('formTitleIn').value;
        document.getElementById('form-submit-btn').style.backgroundColor = btnC;
        
        const grid = document.getElementById('form-grid'); grid.innerHTML = '';
        fields.forEach(f => {
            const item = document.createElement('div'); item.className = `form-item ${f.isFull ? 'full' : 'half'}`;
            let input = f.type === 'dropdown' ? `<select style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; background:${fBg}; color:${fTxt};">${f.options.split('\n').map(o => `<option>${o.trim()}</option>`).join('')}</select>` : `<input type="text" style="width:100%; padding:12px; border:1px solid #ddd; border-radius:6px; background:${fBg}; color:${fTxt};">`;
            item.innerHTML = `<label style="display:block; font-size:11px; font-weight:bold; color:${labCol}; margin-bottom:6px; text-transform:uppercase;">${f.label}</label>${input}`;
            grid.appendChild(item);
        });
    }

    function updateUI() {
        const nameEl = document.getElementById('view-name');
        nameEl.innerText = document.getElementById('nameIn').value || "EVENT NAME";
        nameEl.style.color = document.getElementById('colorIn').value;
        nameEl.style.fontFamily = document.getElementById('fontSelect').value;
        document.getElementById('view-desc').innerText = document.getElementById('descIn').value || "Briefly describe your event.";
        document.getElementById('view-reg').style.display = document.getElementById('regToggle').checked ? 'block' : 'none';
        document.getElementById('edit-table-btn').style.display = document.getElementById('regToggle').checked ? 'block' : 'none';
        document.getElementById('view-tkt').style.display = document.getElementById('tktToggle').checked ? 'block' : 'none';
    }

    function saveJSON() {
        const eSelect = document.getElementById('eventSelect'), 
              eTime = document.getElementById('endTimeIn'), 
              nIn = document.getElementById('nameIn'), 
              dIn = document.getElementById('descIn'), 
              detIn = document.getElementById('detailIn'), 
              fIn = document.getElementById('fileIn'), 
              dz = document.getElementById('drop-zone');
        
        // CLEAR PREVIOUS ERRORS
        [eSelect, eTime, nIn, dIn, detIn, dz].forEach(el => el.classList.remove('required-error'));

        let error = false;
        if(!eSelect.value){ eSelect.classList.add('required-error'); error=true; }
        if(!eTime.value){ eTime.classList.add('required-error'); error=true; }
        if(!nIn.value.trim()){ nIn.classList.add('required-error'); error=true; }
        if(!dIn.value.trim()){ dIn.classList.add('required-error'); error=true; }
        if(!detIn.value.trim()){ detIn.classList.add('required-error'); error=true; }
        if(fIn.files.length === 0){ dz.classList.add('required-error'); error=true; }

        if(error){ 
            alert("Please fill all highlighted required fields."); 
            return; 
        }

        const formData = new FormData();
        const configData = { 
            description: dIn.value, 
            longDetail: detIn.value, 
            fields: fields, 
            yPos: document.getElementById('yRange').value, 
            font: document.getElementById('fontSelect').value, 
            color: document.getElementById('colorIn').value, 
            hSize: document.getElementById('hSizeRange').value, 
            regToggle: document.getElementById('regToggle').checked, 
            tktToggle: document.getElementById('tktToggle').checked, 
            formColors: { 
                bg: document.getElementById('formBgIn').value, 
                title: document.getElementById('formTitleColIn').value, 
                label: document.getElementById('labelColIn').value, 
                fieldBg: document.getElementById('fieldBgIn').value, 
                fieldTxt: document.getElementById('fieldTextIn').value, 
                btn: document.getElementById('formBtnIn').value, 
                formTitleText: document.getElementById('formTitleIn').value 
            } 
        };
        formData.append('save_trigger', 'true');
        formData.append('event_id', eSelect.value);
        formData.append('slider_endtime', eTime.value);
        formData.append('name', nIn.value);
        formData.append('data_json', JSON.stringify(configData));
        formData.append('image_file', fIn.files[0]);

        fetch(window.location.href, { method: 'POST', body: formData })
            .then(r => r.text())
            .then(t => { 
                alert(t); 
                if(t.includes("Success")) window.location.reload(); 
            });
    }

    document.getElementById('yRange').oninput = function() { document.documentElement.style.setProperty('--pos-y', this.value + "%"); };
</script>
</body>
</html>