<?php
/**
 * CRUD operations for Users table
 * Schema: Users (id, name, age, license_year)
 */

require_once 'db.php';
require_once 'utils.php';

/**
 * 모든 사용자 조회
 * @return array - Array of user records
 */
function select_all_users()
{
    global $conn;

    try {
        $sql = "SELECT * FROM Users";
        $result = $conn->query($sql);

        if (!$result) {
            echo "Error fetching users: " . $conn->error . "<br>\n";
            return [];
        }

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        echo "<br>=== Users Table ===<br>\n";
        foreach ($users as $user) {
            echo "ID: {$user['id']}, Name: {$user['name']}, Age: {$user['age']}, License Year: {$user['license_year']}<br>\n";
        }

        return $users;

    } catch (Exception $e) {
        echo "Error fetching users: " . $e->getMessage() . "<br>\n";
        return [];
    }
}

/**
 * 사용자명으로 사용자 조회
 * @param string $username 
 * @return array|null - null if not found
 */
function select_user_by_username($username)
{
    global $conn;

    try {
        $stmt = $conn->prepare("SELECT * FROM Users WHERE name = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        $user = $result->fetch_assoc();

        if ($user) {
            echo "<br>User found: ID={$user['id']}, Name={$user['name']}, Age={$user['age']}, License Year={$user['license_year']}<br>\n";
        } else {
            echo "<br>User '{$username}' not found.<br>\n";
        }

        $stmt->close();
        return $user;

    } catch (Exception $e) {
        echo "Error while fetching user: " . $e->getMessage() . "<br>\n";
        return null;
    }
}

/**
 * 새로운 사용자 추가
 * @param string $username 
 * @param int $age 
 * @param int $license_year -
 * @return bool 
 */
function insert_user($username, $age, $license_year)
{
    global $conn;

    try {
        // 나이 검증 (17세 이상)
        if ($age < 17) {
            echo "User is underage to have a license<br>\n";
            return false;
        }

        // 중복 확인
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Users WHERE name = ? AND age = ? AND license_year = ?");
        $stmt->bind_param("sii", $username, $age, $license_year);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            echo "User already exists<br>\n";
            $stmt->close();
            return false;
        }
        $stmt->close();

        // INSERT
        $stmt = $conn->prepare("INSERT INTO Users (name, age, license_year) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $username, $age, $license_year);

        if ($stmt->execute()) {
            echo "User added<br>\n";
            $stmt->close();
            return true;
        } else {
            echo "Error adding user: " . $stmt->error . "<br>\n";
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        echo "Error adding user: " . $e->getMessage() . "<br>\n";
        return false;
    }
}

/**
 * 사용자 정보 수정
 * @param int $user_id 
 * @param string|null $new_name 
 * @param int|null $new_age 
 * @param int|null $new_license_year 
 * @return bool - True if successful, false otherwise
 */
function update_user($user_id, $new_name = null, $new_age = null, $new_license_year = null)
{
    global $conn;

    try {
        $sql = "UPDATE Users SET ";
        $params = [];
        $types = "";

        if ($new_name !== null) {
            $sql .= "name = ?, ";
            $params[] = $new_name;
            $types .= "s";
        }

        if ($new_age !== null) {
            $sql .= "age = ?, ";
            $params[] = $new_age;
            $types .= "i";
        }

        if ($new_license_year !== null) {
            $sql .= "license_year = ?, ";
            $params[] = $new_license_year;
            $types .= "i";
        }

        // No fields to update
        if (empty($params)) {
            echo "[UPDATE SKIPPED] No values provided.<br>\n";
            return false;
        }

        $sql = rtrim($sql, ", ") . " WHERE id = ?";
        $params[] = $user_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo "[UPDATE OK] User (id) {$user_id}";
            if ($new_name !== null)
                echo ":{$new_name}";
            echo " updated.<br>\n";
            $stmt->close();
            return true;
        } else {
            echo "[UPDATE ERROR] " . $stmt->error . "<br>\n";
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        echo "[UPDATE ERROR] " . $e->getMessage() . "<br>\n";
        return false;
    }
}

/**
 * 사용자명으로 사용자 삭제
 * @param string $username 
 * @return bool 
 */
function delete_user_by_username($username)
{
    global $conn;

    try {
        $stmt = $conn->prepare("DELETE FROM Users WHERE name = ?");
        $stmt->bind_param("s", $username);

        if ($stmt->execute()) {
            if ($stmt->affected_rows == 0) {
                echo "[DELETE] No such user: {$username}<br>\n";
                $stmt->close();
                return false;
            }

            echo "[DELETE OK] User '{$username}' deleted.<br>\n";
            $stmt->close();
            return true;
        } else {
            echo "[DELETE ERROR] " . $stmt->error . "<br>\n";
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        echo "[DELETE ERROR] " . $e->getMessage() . "<br>\n";
        return false;
    }
}
?>