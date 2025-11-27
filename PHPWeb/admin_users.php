<?php
require_once 'db.php';
require_once 'users.php';

// Handle form submissions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = $_POST['name'] ?? '';
        $age = $_POST['age'] ?? '';
        $license_year = $_POST['license_year'] ?? '';

        if ($name && $age && $license_year) {
            ob_start();
            $result = insert_user($name, $age, $license_year);
            $output = ob_get_clean();

            if ($result) {
                $message = "사용자 '{$name}'가 추가되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    } elseif ($action === 'update') {
        $id = $_POST['id'] ?? '';
        $name = $_POST['name'] ?? null;
        $age = $_POST['age'] ?? null;
        $license_year = $_POST['license_year'] ?? null;

        if ($id) {
            ob_start();
            $result = update_user($id, $name ?: null, $age ?: null, $license_year ?: null);
            $output = ob_get_clean();

            if ($result) {
                $message = "사용자 정보가 수정되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    } elseif ($action === 'delete') {
        $name = $_POST['name'] ?? '';

        if ($name) {
            ob_start();
            $result = delete_user_by_username($name);
            $output = ob_get_clean();

            if ($result) {
                $message = "사용자 '{$name}'가 삭제되었습니다.";
            } else {
                $error = strip_tags($output);
            }
        }
    }
}

// Get all users
ob_start();
$users = select_all_users();
ob_end_clean();

// Get users data without output
$stmt = $conn->prepare("SELECT * FROM Users ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
$users = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ko">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>사용자 관리 - 모빌리티 서비스</title>
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

        .form-group input {
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
        <h1>👤 사용자 관리</h1>
        <a href="admin_index.php">← 대시보드</a>
    </div>

    <div class="container">
        <?php if ($message): ?>
            <div class="message success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Add User Form -->
        <div class="card">
            <h2>신규 사용자 등록</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="grid">
                    <div class="form-group">
                        <label>이름 *</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>나이 * (최소 17세)</label>
                        <input type="number" name="age" min="17" required>
                    </div>
                    <div class="form-group">
                        <label>면허 취득 연도 *</label>
                        <input type="number" name="license_year" min="1950" max="<?= date('Y') ?>" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">사용자 추가</button>
            </form>
        </div>

        <!-- Users List -->
        <div class="card">
            <h2>사용자 목록 (총 <?= count($users) ?>명)</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>이름</th>
                        <th>나이</th>
                        <th>면허 취득 연도</th>
                        <th>운전 경력</th>
                        <th>작업</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user):
                        $experience = date('Y') - $user['license_year'];
                        ?>
                        <tr>
                            <td><?= $user['id'] ?></td>
                            <td><?= htmlspecialchars($user['name']) ?></td>
                            <td><?= $user['age'] ?>세</td>
                            <td><?= $user['license_year'] ?></td>
                            <td><?= $experience ?>년</td>
                            <td class="actions">
                                <button
                                    onclick="openEditModal(<?= $user['id'] ?>, '<?= htmlspecialchars($user['name'], ENT_QUOTES) ?>', <?= $user['age'] ?>, <?= $user['license_year'] ?>)"
                                    class="btn btn-warning">수정</button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('정말 삭제하시겠습니까?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="name" value="<?= htmlspecialchars($user['name']) ?>">
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
            <h3>사용자 정보 수정</h3>
            <form method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>이름</label>
                    <input type="text" name="name" id="edit_name">
                </div>
                <div class="form-group">
                    <label>나이</label>
                    <input type="number" name="age" id="edit_age" min="17">
                </div>
                <div class="form-group">
                    <label>면허 취득 연도</label>
                    <input type="number" name="license_year" id="edit_license_year" min="1950" max="<?= date('Y') ?>">
                </div>
                <button type="submit" class="btn btn-primary">수정 완료</button>
            </form>
        </div>
    </div>

    <script>
        function openEditModal(id, name, age, license_year) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_age').value = age;
            document.getElementById('edit_license_year').value = license_year;
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