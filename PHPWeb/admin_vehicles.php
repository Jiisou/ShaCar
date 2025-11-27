<?php
require_once 'db.php';
require_once 'vehicles.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $type = $_POST['type'] ?? '';
        $registered_at = $_POST['registered_at'] ?? '';

        if ($type && $registered_at) {
            ob_start();
            $result = insert_vehicle($type, $registered_at);
            $output = ob_get_clean();

            if ($result) {
                $message = "차량이 추가되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $type = $_POST['type'] ?? null;
        $registered_at = $_POST['registered_at'] ?? null;

        if ($id) {
            ob_start();
            $result = update_vehicle($id, $type ?: null, $registered_at ?: null);
            $output = ob_get_clean();

            if ($result) {
                $message = "차량 정보가 수정되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';

        if ($id) {
            ob_start();
            $result = delete_vehicle($id);
            $output = ob_get_clean();

            if ($result) {
                $message = "차량이 삭제되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM Vehicles ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
$vehicles = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$vehicle_types = ['Compact', 'MidSize', 'SUV', 'Truck', 'Electric'];
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>차량 관리 - 모빌리티 서비스</title>
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
            max-width: 1200px;
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

        .btn-warning {
            background: #f39c12;
            color: white;
        }

        .btn-warning:hover {
            background: #e67e22;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th,
        table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
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
            max-width: 500px;
            width: 90%;
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
        <h1>🚙 차량 관리</h1>
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
            <h2>신규 차량 등록</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid">
                    <div class="form-group">
                        <label>차량 타입 *</label>
                        <select name="type" required>
                            <option value="">선택하세요</option>
                            <?php foreach ($vehicle_types as $vtype): ?>
                                <option value="<?= $vtype ?>"><?= $vtype ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>등록일 *</label>
                        <input type="date" name="registered_at" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">차량 추가</button>
            </form>
        </div>

        <div class="card">
            <h2>차량 목록 (총 <?= count($vehicles) ?>대)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>타입</th>
                        <th>등록일</th>
                        <th>운용 기간</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle):
                        $days = (strtotime('now') - strtotime($vehicle['registered_at'])) / (60 * 60 * 24);
                        $badge_class = 'badge-' . strtolower($vehicle['type']);
                        ?>
                        <tr>
                            <td><?= $vehicle['id'] ?></td>
                            <td><span class="badge <?= $badge_class ?>"><?= $vehicle['type'] ?></span></td>
                            <td><?= $vehicle['registered_at'] ?></td>
                            <td><?= floor($days) ?>일</td>
                            <td class="actions">
                                <button
                                    onclick="openEditModal(<?= $vehicle['id'] ?>, '<?= $vehicle['type'] ?>', '<?= $vehicle['registered_at'] ?>')"
                                    class="btn btn-warning">수정</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $vehicle['id'] ?>">
                                    <button type="submit" class="btn btn-danger">삭제</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeEditModal()">&times;</span>
            <h3>차량 정보 수정</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>차량 타입</label>
                    <select name="type" id="edit_type">
                        <?php foreach ($vehicle_types as $vtype): ?>
                            <option value="<?= $vtype ?>"><?= $vtype ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>등록일</label>
                    <input type="date" name="registered_at" id="edit_registered_at">
                </div>
                <button type="submit" class="btn btn-primary">수정 완료</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, type, registered_at) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_type').value = type;
            document.getElementById('edit_registered_at').value = registered_at;
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