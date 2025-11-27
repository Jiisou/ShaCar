<?php
/**
 * CRUD operations for Vehicles table
 * Schema: Vehicles (id, type, registered_at)
 * Type: ENUM('Compact', 'MidSize', 'SUV', 'Truck', 'Electric')
 */

require_once 'db.php';
require_once 'utils.php';

/**
 * 새로운 차량 추가
 * @param string $vehicle_type 
 * @param string $registered_at 
 * @return bool 
 */
function insert_vehicle($vehicle_type, $registered_at)
{
    global $conn;

    // 날짜값 검증
    if ($registered_at && !valid_date($registered_at)) {
        return false;
    }

    try {
        // 중복 확인
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Vehicles WHERE type = ? AND registered_at = ?");
        $stmt->bind_param("ss", $vehicle_type, $registered_at);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            echo "Vehicle already exist<br>\n";
            $stmt->close();
            return false;
        }
        $stmt->close();

        // INSERT
        $stmt = $conn->prepare("INSERT INTO Vehicles (type, registered_at) VALUES (?, ?)");
        $stmt->bind_param("ss", $vehicle_type, $registered_at);

        if ($stmt->execute()) {
            echo "Vehicle added<br>\n";
            $stmt->close();
            return true;
        } else {
            echo "Error adding vehicle: " . $stmt->error . "<br>\n";
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        echo "Error adding vehicle: " . $e->getMessage() . "<br>\n";
        return false;
    }
}

/**
 * 모든 차량 조회
 * @return array 
 */
function select_all_vehicles()
{
    global $conn;

    try {
        $sql = "SELECT * FROM Vehicles";
        $result = $conn->query($sql);

        if (!$result) {
            echo "Error fetching vehicles: " . $conn->error . "<br>\n";
            return [];
        }

        $vehicles = [];
        while ($row = $result->fetch_assoc()) {
            $vehicles[] = $row;
        }

        echo "<br>=== Vehicles Table ===<br>\n";
        foreach ($vehicles as $vehicle) {
            echo "ID: {$vehicle['id']}, Type: {$vehicle['type']}, Registered: {$vehicle['registered_at']}<br>\n";
        }

        return $vehicles;

    } catch (Exception $e) {
        echo "Error fetching vehicles: " . $e->getMessage() . "<br>\n";
        return [];
    }
}

/**
 * 차량 정보 수정
 * @param int $vehicle_id 
 * @param string|null $new_type 
 * @param string|null $new_registered_at 
 * @return bool 
 */
function update_vehicle($vehicle_id, $new_type = null, $new_registered_at = null)
{
    global $conn;

    // Validate date if provided
    if ($new_registered_at && !valid_date($new_registered_at)) {
        return false;
    }

    try {
        $sql = "UPDATE Vehicles SET ";
        $params = [];
        $types = "";

        if ($new_type !== null) {
            $sql .= "type = ?, ";
            $params[] = $new_type;
            $types .= "s";
        }

        if ($new_registered_at !== null) {
            $sql .= "registered_at = ?, ";
            $params[] = $new_registered_at;
            $types .= "s";
        }

        // No fields to update
        if (empty($params)) {
            echo "[UPDATE SKIPPED] No values provided.<br>\n";
            return false;
        }

        $sql = rtrim($sql, ", ") . " WHERE id = ?";
        $params[] = $vehicle_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo "[UPDATE OK] Vehicle (id) {$vehicle_id}";
            if ($new_type !== null)
                echo ":{$new_type}";
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
 * 차량 삭제
 * @param int $vehicle_id 
 * @return bool 
 */
function delete_vehicle($vehicle_id)
{
    global $conn;

    try {
        $stmt = $conn->prepare("DELETE FROM Vehicles WHERE id = ?");
        $stmt->bind_param("i", $vehicle_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows == 0) {
                echo "[DELETE] Vehicle (id) {$vehicle_id} not found.<br>\n";
                $stmt->close();
                return false;
            }

            echo "[DELETE OK] Vehicle (id) {$vehicle_id} deleted.<br>\n";
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