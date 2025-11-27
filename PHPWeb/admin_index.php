<?php
require_once 'db.php';
require_once 'users.php';
require_once 'vehicles.php';
require_once 'insurance.php';
require_once 'reservation.php';

// Get counts
$user_count = count(select_all_users_silent());
$vehicle_count = count(select_all_vehicles_silent());
$insurance_count = count(select_all_insurance_plans_silent());
$reservation_count = count(select_all_reservations_silent());

function select_all_users_silent()
{
    global $conn;
    $result = $conn->query("SELECT * FROM Users");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function select_all_vehicles_silent()
{
    global $conn;
    $result = $conn->query("SELECT * FROM Vehicles");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function select_all_insurance_plans_silent()
{
    global $conn;
    $result = $conn->query("SELECT * FROM Insurance_Plan");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function select_all_reservations_silent()
{
    global $conn;
    $result = $conn->query("SELECT * FROM Rental_Reservation");
    return $result->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>관리자 대시보드 - 공유 모빌리티 서비스</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f5f5f5;
        }

        .header {
            background: #2c3e50;
            color: white;
            padding: 1rem 2rem;
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card h2 {
            font-size: 1.1rem;
            color: #666;
            margin-bottom: 0.5rem;
        }

        .card .count {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }

        .card a {
            display: inline-block;
            margin-top: 1rem;
            color: #3498db;
            text-decoration: none;
        }

        .card a:hover {
            text-decoration: underline;
        }

        .menu {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .menu h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .menu-list {
            list-style: none;
        }

        .menu-list li {
            margin-bottom: 0.75rem;
        }

        .menu-list a {
            display: block;
            padding: 0.75rem 1rem;
            background: #ecf0f1;
            border-radius: 4px;
            color: #2c3e50;
            text-decoration: none;
            transition: background 0.2s;
        }

        .menu-list a:hover {
            background: #3498db;
            color: white;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>🚗 공유 모빌리티 서비스 관리자 대시보드</h1>
    </div>

    <div class="container">
        <div class="grid">
            <div class="card">
                <h2>👤 사용자</h2>
                <div class="count"><?= $user_count ?></div>
                <a href="admin_users.php">관리하기 →</a>
            </div>

            <div class="card">
                <h2>🚙 차량</h2>
                <div class="count"><?= $vehicle_count ?></div>
                <a href="admin_vehicles.php">관리하기 →</a>
            </div>

            <div class="card">
                <h2>🛡️ 보험 플랜</h2>
                <div class="count"><?= $insurance_count ?></div>
                <a href="admin_insurance.php">관리하기 →</a>
            </div>

            <div class="card">
                <h2>📅 예약</h2>
                <div class="count"><?= $reservation_count ?></div>
                <a href="admin_reservations.php">관리하기 →</a>
            </div>
        </div>

        <div class="menu">
            <h2>주요 기능</h2>
            <ul class="menu-list">
                <li><a href="admin_users.php">🙋‍♂️ 사용자 조회 및 관리</a></li>
                <li><a href="admin_vehicles.php">🚙 차량 조회 및 관리</a></li>
                <li><a href="admin_insurance.php">🛡️ 보험 플랜 관리</a></li>
                <li><a href="admin_reservations.php">📅 예약 검수 및 관리</a></li>
            </ul>
        </div>
    </div>
</body>

</html>