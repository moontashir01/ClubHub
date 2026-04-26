<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'connection.php';

$conn = new mysqli($host, $user, $password, $dbname, $port);
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['pdf_file'])) {
    $club_name = $conn->real_escape_string($_POST['club_name']);
    $subject   = $conn->real_escape_string($_POST['subject']);

    $upload_dir = 'uploads/vc_applications/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $filename    = time() . '_' . preg_replace('/[^A-Za-z0-9\-]/', '', $club_name) . '.pdf';
    $target_file = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['pdf_file']['tmp_name'], $target_file)) {
        $sql = "INSERT INTO vc_applications (club_name, subject, letter_content, status)
                VALUES ('$club_name', '$subject', '$target_file', 'Pending')";
        if ($conn->query($sql) === TRUE) {
            echo "SUCCESS";
        } else {
            echo "Database Error: " . $conn->error;
        }
    } else {
        echo "Failed to save PDF on server.";
    }
    exit();
}

// ==========================================
// STEP 2: Preview Data
// ==========================================
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $clubName      = htmlspecialchars($_POST['clubName']      ?? 'No Club Found');
    $presidentName = htmlspecialchars($_POST['presidentName'] ?? 'President');
    $subject       = htmlspecialchars($_POST['subject']       ?? 'Application');
    $appBody       = htmlspecialchars($_POST['appBody']       ?? '');
    $todayDate     = date("F j, Y");
} else {
    header("Location: vc_application.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Official Preview - ClubHub</title>

    <!-- Use html2canvas + jsPDF directly (NO html2pdf wrapper) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #1e293b;
            font-family: 'Times New Roman', Times, serif;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 30px 0 50px;
        }

        .top-info {
            color: #fff;
            background: rgba(255,255,255,0.1);
            padding: 10px 22px;
            border-radius: 20px;
            margin-bottom: 24px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            border: 1px solid rgba(255,255,255,0.15);
        }

        /* =============================================
           A4 PAPER - EXACT DIMENSIONS, OVERFLOW HIDDEN
           This is what gets screenshotted = exactly 1 page
        ============================================= */
        #pdf-area {
            width:  794px;   /* 210mm at 96dpi */
            height: 1123px;  /* 297mm at 96dpi */
            padding: 60px 90px 60px 90px;
            background: white;
            box-shadow: 0 0 40px rgba(0,0,0,0.6);
            font-size: 15px;
            line-height: 1.55;
            color: #000;
            overflow: hidden;          /* CRITICAL: nothing leaks out */
            position: relative;
            outline: none;
        }

        .letter-header {
            text-align: center;
            border-bottom: 2.5px solid #000;
            padding-bottom: 10px;
            margin-bottom: 22px;
        }
        .letter-header h1 {
            font-size: 24px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .letter-header p {
            font-size: 13px;
            font-style: italic;
            margin-top: 4px;
        }

        .letter-body { text-align: justify; }

        .subject-line {
            text-align: center;
            font-weight: bold;
            margin: 20px 0 16px;
        }

        .signature-block { margin-top: 36px; }

        /* =============================================
           SEND BUTTON
        ============================================= */
        #submitBtn {
            margin-top: 28px;
            padding: 14px 44px;
            background: #22c55e;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 17px;
            font-family: Arial, sans-serif;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s, transform 0.2s;
        }
        #submitBtn:hover:not(:disabled) { background: #16a34a; transform: scale(1.05); }
        #submitBtn:disabled { background: #555; cursor: not-allowed; }
    </style>
</head>
<body>

<div class="top-info">
    <strong>Preview Mode:</strong> Click inside the paper to edit before sending.
</div>

<input type="hidden" id="data_club_name" value="<?php echo $clubName; ?>">
<input type="hidden" id="data_subject"   value="<?php echo $subject; ?>">

<!-- ================================================
     THIS EXACT DIV IS SCREENSHOTTED = ALWAYS 1 PAGE
================================================ -->
<div id="pdf-area" contenteditable="true">

    <div class="letter-header">
        <h1>North South University</h1>
        <p>Center of Excellence in Higher Education</p>
    </div>

    <div class="letter-body">

        <p><strong>Date:</strong> <?php echo $todayDate; ?></p>
        <br>

        <p>To</p>
        <p>The Vice Chancellor</p>
        <p>North South University</p>
        <p>Bashundhara, Dhaka-1229</p>
        <br>

        <p><strong>Through:</strong> Director, Office of Student Affairs</p>
        <br>

        <p><strong>From:</strong> <?php echo $presidentName; ?>, President, <?php echo $clubName; ?></p>

        <div class="subject-line">Subject: <?php echo $subject; ?></div>

        <p>Dear Sir,</p>
        <br>

        <p>
            With due respect and humble submission, I am writing to you on behalf of the
            <strong><?php echo $clubName; ?></strong>.
        </p>
        <br>

        <div id="main-body">
            <?php echo nl2br($appBody); ?>
        </div>
        <br>

        <p>
            We earnestly hope that you will kindly consider our application and grant us the
            necessary approval. Your continuous support and encouragement are always the biggest
            inspiration for our club.
        </p>
        <br>

        <p>Sincerely yours,</p>

        <div class="signature-block">
            <p>_______________________</p>
            <p><strong><?php echo $presidentName; ?></strong></p>
            <p>President, <?php echo $clubName; ?></p>
            <p>North South University</p>
        </div>

    </div>
</div>

<button type="button" id="submitBtn" onclick="uploadPDF()">
    Send Final PDF
</button>

<script>
async function uploadPDF() {
    const btn    = document.getElementById('submitBtn');
    const pdfDiv = document.getElementById('pdf-area');

    // Bracket check
    if (pdfDiv.innerText.includes('[') || pdfDiv.innerText.includes(']')) {
        if (!confirm("You still have unfilled brackets []. Send anyway?")) return;
    }

    btn.disabled   = true;
    btn.innerText  = "Generating PDF...";

    try {
        // ================================================
        // STEP 1: Screenshot the EXACT div (794x1123px)
        //         html2canvas captures exactly what you see
        // ================================================
        const canvas = await html2canvas(pdfDiv, {
            scale:          3,          // high resolution
            useCORS:        true,
            logging:        false,
            backgroundColor:'#ffffff',
            width:          pdfDiv.offsetWidth,
            height:         pdfDiv.offsetHeight,
            windowWidth:    pdfDiv.offsetWidth,
            windowHeight:   pdfDiv.offsetHeight,
            scrollX:        0,
            scrollY:        -window.scrollY   // handle page scroll offset
        });

        const imgData = canvas.toDataURL('image/jpeg', 0.98);

        // ================================================
        // STEP 2: Insert the screenshot as a SINGLE PAGE
        //         into a jsPDF A4 document
        // ================================================
        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({
            orientation: 'portrait',
            unit:        'mm',
            format:      'a4'           // 210 x 297 mm
        });

        // Fill entire A4 page with the screenshot image
        pdf.addImage(imgData, 'JPEG', 0, 0, 210, 297);

        // ================================================
        // STEP 3: Export as Blob and upload to server
        // ================================================
        const pdfBlob = pdf.output('blob');

        const formData = new FormData();
        formData.append('pdf_file',  pdfBlob, 'Application.pdf');
        formData.append('club_name', document.getElementById('data_club_name').value);
        formData.append('subject',   document.getElementById('data_subject').value);

        const response = await fetch('preview_application.php', {
            method: 'POST',
            body:   formData
        });
        const result = await response.text();

        if (result.trim() === 'SUCCESS') {
            alert('Official application sent successfully!');
            window.location.href = 'Club_dashboard.php';
        } else {
            alert('Server error: ' + result);
            btn.disabled  = false;
            btn.innerText = 'Send Final PDF';
        }

    } catch (err) {
        console.error(err);
        alert('An error occurred: ' + err.message);
        btn.disabled  = false;
        btn.innerText = 'Send Final PDF';
    }
}
</script>
</body>
</html>
