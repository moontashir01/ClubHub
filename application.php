<?php
session_start();
// include 'connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Center - ClubHub</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0b0e14; /* Your dashboard's deep dark background */
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #ffffff;
        }

        /* Clean Back Button */
        .back-btn {
            position: absolute;
            top: 30px;
            left: 40px;
            padding: 8px 16px;
            background-color: #1a1d27;
            color: #94a3b8;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            transition: 0.2s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .back-btn:hover {
            background-color: #2d3748;
            color: #ffffff;
        }

        /* Minimal Header */
        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 600;
            margin-bottom: 8px;
            color: #ffffff;
            letter-spacing: 0.5px;
        }

        .header p {
            color: #94a3b8;
            font-size: 1rem;
        }

        /* Card Container */
        .cards-container {
            display: flex;
            gap: 25px;
        }

        /* Dashboard Matching Cards */
        .dashboard-card {
            width: 280px;
            background-color: #151923; /* Same as your dashboard cards */
            padding: 40px 25px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: white;
            transition: all 0.2s ease-in-out;
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .dashboard-card:hover {
            transform: translateY(-5px);
            background-color: #1e2433;
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }

        .icon {
            font-size: 3rem;
            margin-bottom: 20px;
        }

        .title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #f1f5f9;
        }

        .desc {
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.4;
        }
    </style>
</head>
<body>

    <a href="Club_dashboard.php" class="back-btn">
        <span>←</span> Dashboard
    </a>

    <div class="header">
        <h1>Application Center</h1>
        <p>Manage your official requests</p>
    </div>

    <div class="cards-container">
        <a href="vc_application.php" class="dashboard-card">
            <div class="icon">📝</div>
            <div class="title">New Application</div>
            <div class="desc">Draft and submit official applications to the admin's desk.</div>
        </a>

        <a href="application_status.php" class="dashboard-card">
            <div class="icon">🔍</div>
            <div class="title">Preview Status</div>
            <div class="desc">Track the current status of your submitted applications.</div>
        </a>
    </div>

</body>
</html>