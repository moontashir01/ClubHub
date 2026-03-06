<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>club hub</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.css"/>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/pannellum@2.5.6/build/pannellum.js"></script>

    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; background: #000; }
        #panorama { width: 100%; height: 100vh; }

        /* 1. The Container (Pannellum moves this) */
        .hotspot-container {
            width: 292px;
            height: 384px;
            perspective: 600px; /* Depth starts here */
        }

        /* 2. The Button (We rotate this) */
        .tall-button {
            width: 100%;
            height: 100%;
            background: rgba(0, 255, 204, 0.2); 
            border: 2px solid #00ffcc;
            cursor: pointer;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-family: sans-serif;
            font-weight: bold;
            transition: all 0.3s ease;
            backface-visibility: hidden;
        }

        /* NEW: Yellow color change when clicked */
        .tall-button.clicked {
            background: rgba(255, 255, 0, 0.5);
            border-color: #ffff00;
            color: #000;
        }

        /* Specific 3D angles */
        .left-rotation { transform: rotateY(0deg); }
        .right-rotation { transform: rotateY(-45deg); }

        .tall-button:hover {
            background: rgba(0, 255, 204, 0.5);
            box-shadow: 0 0 20px rgba(0, 255, 204, 0.4);
            transform: scale(1.05) translateZ(20px); /* Pops out slightly */
        }
    </style>
</head>
<body>

<div id="panorama"></div>

<script>
    const viewer = pannellum.viewer('panorama', {
        "type": "equirectangular",
        "panorama": "images/IMG_8848.jpeg",
        "autoLoad": true,
        "showControls": false,
        
        "hfov": 110,
        "minHfov": 110,
        "maxHfov": 110,
        "minPitch": 0,
        "maxPitch": 0,
        
        "friction": 0.1,
        
        "hotSpots": [
            {
                "pitch": 0,
                "yaw": 90,
                "cssClass": "hotspot-container", // Pannellum targets this
                "createTooltipFunc": hotspotWrapper,
                "createTooltipArgs": { "label": "LEFT BOX", "className": "left-rotation" }
            },
            {
                "pitch": 0,
                "yaw": 12,
                "cssClass": "hotspot-container",
                "createTooltipFunc": hotspotWrapper,
                "createTooltipArgs": { "label": "RIGHT BOX", "className": "right-rotation" }
            }
        ]
    });

    // Function to build the 3D button inside the container
    function hotspotWrapper(hotSpotDiv, args) {
        hotSpotDiv.classList.add('hotspot-container');
        const button = document.createElement('div');
        button.classList.add('tall-button');
        button.classList.add(args.className);
        button.innerHTML = args.label;

        // Added click event to change color to yellow
        button.addEventListener('click', function() {
            this.classList.toggle('clicked');
        });

        hotSpotDiv.appendChild(button);
    }
</script>

</body>
</html>