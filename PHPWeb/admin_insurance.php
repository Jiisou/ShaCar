<?php
require_once 'db.php';
require_once 'insurance.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $type = $_POST['type'] ?? '';
        $daily_fee = $_POST['daily_fee'] ?? '';
        $deductible_amount = $_POST['deductible_amount'] ?? '';
        $vehicle_class = $_POST['vehicle_class'] ?? '';
        $min_driver_age = $_POST['min_driver_age'] ?? '';
        $min_license_years = $_POST['min_license_years'] ?? '';
        $name = $_POST['name'] ?? '';

        if ($type && $daily_fee && $deductible_amount && $vehicle_class && $min_driver_age && $min_license_years && $name) {
            ob_start();
            $result = insert_insurance_plan($type, $daily_fee, $deductible_amount, $vehicle_class, $min_driver_age, $min_license_years, $name);
            $output = ob_get_clean();

            if ($result) {
                $message = "보험 플랜이 추가되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $type = $_POST['type'] ?? null;
        $daily_fee = $_POST['daily_fee'] ?? null;
        $deductible_amount = $_POST['deductible_amount'] ?? null;
        $vehicle_class = $_POST['vehicle_class'] ?? null;
        $min_driver_age = $_POST['min_driver_age'] ?? null;
        $min_license_years = $_POST['min_license_years'] ?? null;
        $name = $_POST['name'] ?? null;

        if ($id) {
            ob_start();
            $result = update_insurance_plan($id, $type ?: null, $daily_fee ?: null, $deductible_amount ?: null, $vehicle_class ?: null, $min_driver_age ?: null, $min_license_years ?: null, $name ?: null);
            $output = ob_get_clean();

            if ($result) {
                $message = "보험 플랜이 수정되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        if ($id) {
            ob_start();
            $result = delete_insurance_plan($id);
            $output = ob_get_clean();

            if ($result) {
                $message = "보험 플랜이 삭제되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM Insurance_Plan ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
$plans = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$vehicle_classes = ['Compact', 'MidSize', 'SUV', 'Truck', 'Electric'];
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>보험 관리 - 모빌리티 서비스</title>
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

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-basic {
            background: #3498db;
            color: white;
        }

        .badge-standard {
            background: #9b59b6;
            color: white;
        }

        .badge-premium {
            background: #e74c3c;
            color: white;
        }

        .badge-compact {
            background: #3498db;
            color: white;
        }

        .badge-midsize {
            background: #9b59b6;
            color: white;
        }

        .badge-suv {
            background: #e74c3c;
            color: white;
        }

        .badge-truck {
            background: #f39c12;
            color: white;
        }

        .badge-electric {
            background: #2ecc71;
            color: white;
        }

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
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
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-content h3 {
            margin-bottom: 1rem;
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
    </style>
</head>

<body>
    <div class="header">
        <h1>🛡️ 보험 플랜 관리</h1>
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
            <h2>신규 보험 플랜 등록</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid">
                    <div class="form-group">
                        <label>플랜명 *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>보험 타입 *</label>
                        <input type="text" name="type" required placeholder="Basic, Standard, Premium 등">
                    </div>
                    <div class="form-group">
                        <label>차량 클래스 *</label>
                        <select name="vehicle_class" required>
                            <option value="">선택하세요</option>
                            <?php foreach ($vehicle_classes as $vclass): ?>
                                <option value="<?= $vclass ?>"><?= $vclass ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>일일 요금 (원) *</label>
                        <input type="number" name="daily_fee" required min="0">
                    </div>
                    <div class="form-group">
                        <label>자기부담금 (원) *</label>
                        <input type="number" name="deductible_amount" required min="0">
                    </div>
                    <div class="form-group">
                        <label>최소 운전자 나이 *</label>
                        <input type="number" name="min_driver_age" required min="17">
                    </div>
                    <div class="form-group">
                        <label>최소 면허 경력 (년) *</label>
                        <input type="number" name="min_license_years" required min="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">보험 플랜 추가</button>
            </form>
        </div>

        <div class="card">
            <h2>보험 플랜 목록 (총 <?= count($plans) ?>개)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>플랜명</th>
                        <th>타입</th>
                        <th>차량 클래스</th>
                        <th>일일 요금</th>
                        <th>자기부담금</th>
                        <th>최소 나이</th>
                        <th>최소 경력</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plans as $plan):
                        $badge_class = 'badge-' . strtolower($plan['vehicle_class']);
                        ?>
                        <tr>
                            <td><?= $plan['id'] ?></td>
                            <td><?= htmlspecialchars($plan['name']) ?></td>
                            <td><?= htmlspecialchars($plan['type']) ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= $plan['vehicle_class'] ?></span></td>
                            <td><?= number_format($plan['daily_fee']) ?>원</td>
                            <td><?= number_format($plan['deductible_amount']) ?>원</td>
                            <td><?= $plan['min_driver_age'] ?>세</td>
                            <td><?= $plan['min_license_years'] ?>년</td>
                            <td class="actions">
                                <button
                                    onclick="openEditModal(<?= $plan['id'] ?>, '<?= htmlspecialchars($plan['type'], ENT_QUOTES) ?>', <?= $plan['daily_fee'] ?>, <?= $plan['deductible_amount'] ?>, '<?= $plan['vehicle_class'] ?>', <?= $plan['min_driver_age'] ?>, <?= $plan['min_license_years'] ?>, '<?= htmlspecialchars($plan['name'], ENT_QUOTES) ?>')"
                                    class="btn btn-warning">수정</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $plan['id'] ?>">
                                    <button type="submit" class="btn btn-danger">삭제</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditModal()">&times;</span>
            <h3>보험 플랜 수정</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="grid">
                    <div class="form-group">
                        <label>플랜명</label>
                        <input type="text" name="name" id="edit_name">
                    </div>
                    <div class="form-group">
                        <label>보험 타입</label>
                        <input type="text" name="type" id="edit_type">
                    </div>
                    <div class="form-group">
                        <label>차량 클래스</label>
                        <select name="vehicle_class" id="edit_vehicle_class">
                            <?php foreach ($vehicle_classes as $vclass): ?>
                                <option value="<?= $vclass ?>"><?= $vclass ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>일일 요금 (원)</label>
                        <input type="number" name="daily_fee" id="edit_daily_fee" min="0">
                    </div>
                    <div class="form-group">
                        <label>자기부담금 (원)</label>
                        <input type="number" name="deductible_amount" id="edit_deductible_amount" min="0">
                    </div>
                    <div class="form-group">
                        <label>최소 운전자 나이</label>
                        <input type="number" name="min_driver_age" id="edit_min_driver_age" min="17">
                    </div>
                    <div class="form-group">
                        <label>최소 면허 경력 (년)</label>
                        <input type="number" name="min_license_years" id="edit_min_license_years" min="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">수정 완료</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, type, daily_fee, deductible_amount, vehicle_class, min_driver_age, min_license_years, name) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_type').value = type;
            document.getElementById('edit_daily_fee').value = daily_fee;
            document.getElementById('edit_deductible_amount').value = deductible_amount;
            document.getElementById('edit_vehicle_class').value = vehicle_class;
            document.getElementById('edit_min_driver_age').value = min_driver_age;
            document.getElementById('edit_min_license_years').value = min_license_years;
            document.getElementById('edit_name').value = name;
            document.getElementById('editModal').classList.add('active');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.remove('active');
        }

        window.onclick = function (event) {
            const modal = document.getElementById('editModal');
            if (event.target == modal) {
                closeEditModal();
            }
        }
    </script>
</body>

</html>