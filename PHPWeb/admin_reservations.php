<?php
require_once 'db.php';
require_once 'reservation.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $user_id = $_POST['user_id'] ?? '';
        $vehicle_id = $_POST['vehicle_id'] ?? '';
        $insurance_id = $_POST['insurance_id'] ?? '';
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';

        if ($user_id && $vehicle_id && $insurance_id && $start_date && $end_date) {
            ob_start();
            $result = insert_reservation($user_id, $vehicle_id, $insurance_id, $start_date, $end_date);
            $output = ob_get_clean();

            if ($result) {
                $message = "예약이 생성되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        if ($id) {
            ob_start();
            $result = delete_reservation($id);
            $output = ob_get_clean();

            if ($result) {
                $message = "예약이 삭제되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    }
}

// Get all data for dropdowns and display
$stmt = $conn->prepare("SELECT id, name, age, license_year FROM Users ORDER BY name");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT id, type, registered_at FROM Vehicles ORDER BY type");
$stmt->execute();
$vehicles = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("SELECT id, name, type, vehicle_class, min_driver_age, min_license_years FROM Insurance_Plan ORDER BY name");
$stmt->execute();
$insurance_plans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare("
    SELECT r.*, 
           u.name as user_name, u.age as user_age, u.license_year,
           v.type as vehicle_type,
           i.name as insurance_name, i.min_driver_age, i.min_license_years, i.daily_fee, i.deductible_amount
    FROM Rental_Reservation r
    JOIN Users u ON r.uid = u.id
    JOIN Vehicles v ON r.vid = v.id
    JOIN Insurance_Plan i ON r.iid = i.id
    ORDER BY r.id DESC
");
$stmt->execute();
$reservations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>예약 관리 - 모빌리티 서비스</title>
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.5rem;
        }

        .header a {
            color: white;
            text-decoration: none;
        }

        .container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .message {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .card h2 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .card h3 {
            margin-top: 1.5rem;
            margin-bottom: 0.75rem;
            color: #555;
            font-size: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.25rem;
            color: #555;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }

        .form-group small {
            display: block;
            margin-top: 0.25rem;
            color: #666;
            font-size: 0.85rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background: #c0392b;
        }

        .btn-success {
            background: #2ecc71;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        table th,
        table td {
            padding: 0.75rem 0.5rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        table tbody tr {
            cursor: pointer;
            transition: background-color 0.2s;
        }

        table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-success {
            background: #2ecc71;
            color: white;
        }

        .badge-warning {
            background: #f39c12;
            color: white;
        }

        .badge-danger {
            background: #e74c3c;
            color: white;
        }

        .badge-secondary {
            background: #95a5a6;
            color: white;
        }

        .badge-info {
            background: #667eea;
            color: white;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
        }

        .modal-content h3 {
            margin-bottom: 1rem;
            color: #2c3e50;
        }

        .close-btn {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
        }

        .close-btn:hover {
            color: #333;
        }

        .price-detail {
            margin: 1rem 0;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 4px;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin: 0.5rem 0;
        }

        .price-row.total {
            font-size: 1.2rem;
            font-weight: bold;
            color: #2c3e50;
            padding-top: 0.5rem;
            border-top: 2px solid #ddd;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #3498db;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }

        .info-box h4 {
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .info-box ul {
            margin-left: 1.5rem;
        }

        .info-box li {
            margin-bottom: 0.25rem;
            color: #555;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>📅 예약 검수 및 관리</h1>
        <a href="admin_index.php">← 대시보드</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="card">
            <h2>신규 예약 생성</h2>
            <div class="info-box">
                <h4>⚠️ 예약 생성 시 검증이 필요한 항목</h4>
                <ul>
                    <li>사용자 나이가 보험 플랜의 최소 운전자 나이 이상인지 확인</li>
                    <li>사용자의 면허 취득 경력이 보험 플랜의 최소 면허 경력 이상인지 확인</li>
                    <li>차량 타입이 보험 플랜의 차량 클래스와 일치하는지 확인</li>
                    <li>동일한 예약이 이미 존재하는지 확인</li>
                </ul>
            </div>

            <form method="POST" id="reservationForm">
                <input type="hidden" name="action" value="add">
                <div class="grid">
                    <div class="form-group">
                        <label>사용자 *</label>
                        <select name="user_id" id="user_id" required onchange="updateUserInfo()">
                            <option value="">선택하세요</option>
                            <?php foreach ($users as $user):
                                $license_exp = date('Y') - $user['license_year'];
                                ?>
                                <option value="<?= $user['id'] ?>" data-age="<?= $user['age'] ?>"
                                    data-license="<?= $license_exp ?>">
                                    <?= htmlspecialchars($user['name']) ?> (<?= $user['age'] ?>세, 경력 <?= $license_exp ?>년)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="userInfo"></small>
                    </div>

                    <div class="form-group">
                        <label>차량 *</label>
                        <select name="vehicle_id" id="vehicle_id" required onchange="updateVehicleInfo()">
                            <option value="">선택하세요</option>
                            <?php foreach ($vehicles as $vehicle): ?>
                                <option value="<?= $vehicle['id'] ?>" data-type="<?= $vehicle['type'] ?>">
                                    ID: <?= $vehicle['id'] ?> - <?= $vehicle['type'] ?> (등록일:
                                    <?= $vehicle['registered_at'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="vehicleInfo"></small>
                    </div>

                    <div class="form-group">
                        <label>보험 플랜 *</label>
                        <select name="insurance_id" id="insurance_id" required onchange="updateInsuranceInfo()">
                            <option value="">선택하세요</option>
                            <?php foreach ($insurance_plans as $plan): ?>
                                <option value="<?= $plan['id'] ?>" data-minage="<?= $plan['min_driver_age'] ?>"
                                    data-minlicense="<?= $plan['min_license_years'] ?>"
                                    data-class="<?= $plan['vehicle_class'] ?>">
                                    <?= htmlspecialchars($plan['name']) ?> (나이≥<?= $plan['min_driver_age'] ?>,
                                    경력≥<?= $plan['min_license_years'] ?>년, <?= $plan['vehicle_class'] ?> 전용)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="insuranceInfo"></small>
                    </div>

                    <div class="form-group">
                        <label>렌탈 시작일 *</label>
                        <input type="date" name="start_date" required min="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-group">
                        <label>렌탈 종료일 *</label>
                        <input type="date" name="end_date" required min="<?= date('Y-m-d') ?>">
                    </div>
                </div>

                <div id="validationMessage" style="margin-top: 1rem; padding: 1rem; border-radius: 4px; display: none;">
                </div>

                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">예약 생성</button>
            </form>
        </div>

        <div class="card">
            <h2>예약 목록 (총 <?= count($reservations) ?>건)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>사용자</th>
                        <th>차량</th>
                        <th>보험</th>
                        <th>기간</th>
                        <th>예약 상태</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res):
                        $license_exp = date('Y') - $res['license_year'];
                        $days = (strtotime($res['end_date']) - strtotime($res['start_date'])) / (60 * 60 * 24);

                        $today = strtotime('today');
                        $start_time = strtotime($res['start_date']);
                        $end_time = strtotime($res['end_date']);

                        // 상태 판단
                        if ($end_time < $today) {
                            $status = 'completed';  // 종료
                        } elseif ($start_time > $today) {
                            $status = 'scheduled';  // 예정
                        } else {
                            $status = 'in_progress';  // 진행중
                        }

                        // 가격 계산
                        $rental_days = $days + 1;  // end_date - start_date + 1
                        $total_price = ($res['daily_fee'] * $rental_days) + $res['deductible_amount'];
                        ?>
                        <tr
                            onclick="showPriceModal('<?= htmlspecialchars($res['user_name'], ENT_QUOTES) ?>', '<?= $res['vehicle_type'] ?>', '<?= htmlspecialchars($res['insurance_name'], ENT_QUOTES) ?>', <?= $res['daily_fee'] ?>, <?= $rental_days ?>, <?= $res['deductible_amount'] ?>, <?= $total_price ?>, '<?= $res['start_date'] ?>', '<?= $res['end_date'] ?>')">
                            <td><?= $res['id'] ?></td>
                            <td>
                                <?= htmlspecialchars($res['user_name']) ?><br>
                                <small style="color: #666;"><?= $res['user_age'] ?>세, 경력 <?= $license_exp ?>년</small>
                            </td>
                            <td><?= $res['vehicle_type'] ?> (ID: <?= $res['vid'] ?>)</td>
                            <td>
                                <?= htmlspecialchars($res['insurance_name']) ?><br>
                                <small style="color: #666;">나이≥<?= $res['min_driver_age'] ?>,
                                    경력≥<?= $res['min_license_years'] ?>년</small>
                            </td>
                            <td>
                                <?= $res['start_date'] ?> ~ <?= $res['end_date'] ?><br>
                                <small style="color: #666;"><?= $days ?>일</small>
                            </td>
                            <td>
                                <?php if ($status === 'completed'): ?>
                                    <span class="badge badge-secondary">✓ 종료</span>
                                <?php elseif ($status === 'scheduled'): ?>
                                    <span class="badge badge-info"> ▶︎ 예정</span>
                                <?php else: ?>
                                    <span class="badge badge-success">● 진행중</span>
                                <?php endif; ?>
                            </td>
                            <td onclick="event.stopPropagation()">
                                <form method="POST" style="display:inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $res['id'] ?>">
                                    <button type="submit" class="btn btn-danger">삭제</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="priceModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal()">&times;</span>
            <h3>예약 상세 정보</h3>
            <div class="price-detail">
                <div class="price-row">
                    <span>사용자:</span>
                    <span id="modalUser"></span>
                </div>
                <div class="price-row">
                    <span>차량:</span>
                    <span id="modalVehicle"></span>
                </div>
                <div class="price-row">
                    <span>보험:</span>
                    <span id="modalInsurance"></span>
                </div>
                <div class="price-row">
                    <span>기간:</span>
                    <span id="modalPeriod"></span>
                </div>
                <hr style="margin: 10px 0; border: 0; border-top: 1px solid #ddd;">
                <div class="price-row">
                    <span>일일 대여료:</span>
                    <span id="modalDailyFee"></span>
                </div>
                <div class="price-row">
                    <span>대여 일수:</span>
                    <span id="modalDays"></span>
                </div>
                <div class="price-row">
                    <span>보험 자기부담금:</span>
                    <span id="modalDeductible"></span>
                </div>
                <div class="price-row total">
                    <span>총 예상 비용:</span>
                    <span id="modalTotal"></span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateUserInfo() {
            const select = document.getElementById('user_id');
            const option = select.options[select.selectedIndex];
            const info = document.getElementById('userInfo');

            if (option.value) {
                const age = option.dataset.age;
                const license = option.dataset.license;
                info.textContent = `나이: ${age}세, 면허 경력: ${license}년`;
                validateSelection();
            } else {
                info.textContent = '';
            }
        }

        function updateVehicleInfo() {
            const select = document.getElementById('vehicle_id');
            const option = select.options[select.selectedIndex];
            const info = document.getElementById('vehicleInfo');

            if (option.value) {
                const type = option.dataset.type;
                info.textContent = `차량 타입: ${type}`;
                validateSelection();
            } else {
                info.textContent = '';
            }
        }

        function updateInsuranceInfo() {
            const select = document.getElementById('insurance_id');
            const option = select.options[select.selectedIndex];
            const info = document.getElementById('insuranceInfo');

            if (option.value) {
                const minAge = option.dataset.minage;
                const minLicense = option.dataset.minlicense;
                const vclass = option.dataset.class;
                info.textContent = `요구사항: ${minAge}세 이상, ${minLicense}년 이상, ${vclass} 차량`;
                validateSelection();
            } else {
                info.textContent = '';
            }
        }

        function validateSelection() {
            const userSelect = document.getElementById('user_id');
            const vehicleSelect = document.getElementById('vehicle_id');
            const insuranceSelect = document.getElementById('insurance_id');
            const validationMsg = document.getElementById('validationMessage');

            if (!userSelect.value || !vehicleSelect.value || !insuranceSelect.value) {
                validationMsg.style.display = 'none';
                return;
            }

            const userOption = userSelect.options[userSelect.selectedIndex];
            const vehicleOption = vehicleSelect.options[vehicleSelect.selectedIndex];
            const insuranceOption = insuranceSelect.options[insuranceSelect.selectedIndex];

            const userAge = parseInt(userOption.dataset.age);
            const userLicense = parseInt(userOption.dataset.license);
            const vehicleType = vehicleOption.dataset.type;

            const minAge = parseInt(insuranceOption.dataset.minage);
            const minLicense = parseInt(insuranceOption.dataset.minlicense);
            const requiredClass = insuranceOption.dataset.class;

            let errors = [];

            if (userAge < minAge) {
                errors.push(`❌ 사용자 나이(${userAge}세)가 보험 요구 나이(${minAge}세) 미만입니다.`);
            }

            if (userLicense < minLicense) {
                errors.push(`❌ 사용자 경력(${userLicense}년)이 보험 요구 경력(${minLicense}년) 미만입니다.`);
            }

            if (vehicleType !== requiredClass) {
                errors.push(`❌ 차량 타입(${vehicleType})이 보험 차량 클래스(${requiredClass})와 일치하지 않습니다.`);
            }

            if (errors.length > 0) {
                validationMsg.innerHTML = errors.join('<br>');
                validationMsg.style.background = '#f8d7da';
                validationMsg.style.color = '#721c24';
                validationMsg.style.border = '1px solid #f5c6cb';
                validationMsg.style.display = 'block';
            } else {
                validationMsg.innerHTML = '✓ 모든 검증 조건을 만족합니다!';
                validationMsg.style.background = '#d4edda';
                validationMsg.style.color = '#155724';
                validationMsg.style.border = '1px solid #c3e6cb';
                validationMsg.style.display = 'block';
            }
        }

        function showPriceModal(userName, vehicleType, insuranceName, dailyFee, rentalDays, deductible, totalPrice, startDate, endDate) {
            document.getElementById('modalUser').textContent = userName;
            document.getElementById('modalVehicle').textContent = vehicleType;
            document.getElementById('modalInsurance').textContent = insuranceName;
            document.getElementById('modalPeriod').textContent = `${startDate} ~ ${endDate}`;

            document.getElementById('modalDailyFee').textContent = parseInt(dailyFee).toLocaleString() + '원';
            document.getElementById('modalDays').textContent = rentalDays + '일';
            document.getElementById('modalDeductible').textContent = parseInt(deductible).toLocaleString() + '원';
            document.getElementById('modalTotal').textContent = parseInt(totalPrice).toLocaleString() + '원';

            document.getElementById('priceModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('priceModal').classList.remove('active');
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('priceModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
</body>

</html>