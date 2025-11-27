<?php
/**
 * CRUD operations for Rental_Reservation table
 * Schema: Rental_Reservation (id, uid, vid, iid, start_date, end_date)
 *         Related tables - Users, Vehicles, Insurance_Plan
 */

require_once 'db.php';
require_once 'utils.php';

/**
 * 새로운 예약 생성 (유효성 검사 포함)
 * @param int $user_id 
 * @param int $vehicle_id 
 * @param int $insurance_id 
 * @param string $start_date 
 * @param string $end_date 
 * @return bool 
 */
function insert_reservation($user_id, $vehicle_id, $insurance_id, $start_date, $end_date)
{
    global $conn;

    // Validate dates
    if ($start_date && !valid_date($start_date)) {
        return false;
    }
    if ($end_date && !valid_date($end_date)) {
        return false;
    }

    try {
        // 1. Fetch and validate user information
        $stmt = $conn->prepare("SELECT age, license_year FROM Users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_row = $result->fetch_assoc();
        $stmt->close();

        if (!$user_row) {
            echo "[INSERT ERROR] User not found<br>\n";
            return false;
        }

        $user_age = $user_row['age'];
        $user_license_year = $user_row['license_year'];

        // 2. Fetch and validate vehicle information
        $stmt = $conn->prepare("SELECT type FROM Vehicles WHERE id = ?");
        $stmt->bind_param("i", $vehicle_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $vehicle_row = $result->fetch_assoc();
        $stmt->close();

        if (!$vehicle_row) {
            echo "[INSERT ERROR] Vehicle not found<br>\n";
            return false;
        }

        $vehicle_type = $vehicle_row['type'];

        // 3. Fetch and validate insurance information
        $stmt = $conn->prepare("SELECT vehicle_class, min_driver_age, min_license_years FROM Insurance_Plan WHERE id = ?");
        $stmt->bind_param("i", $insurance_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $insurance_row = $result->fetch_assoc();
        $stmt->close();

        if (!$insurance_row) {
            echo "[INSERT ERROR] Insurance plan not found<br>\n";
            return false;
        }

        $ins_vehicle_class = $insurance_row['vehicle_class'];
        $ins_min_age = $insurance_row['min_driver_age'];
        $ins_min_license_years = $insurance_row['min_license_years'];

        // 4. Business logic validation: Age check
        if ($user_age < $ins_min_age) {
            echo "[INSERT ERROR] User age ({$user_age}) is less than minimum required age ({$ins_min_age})<br>\n";
            return false;
        }

        // 5. Business logic validation: License duration check
        $current_year = date('Y');
        $license_duration = $current_year - $user_license_year;
        if ($license_duration < $ins_min_license_years) {
            echo "[INSERT ERROR] License duration ({$license_duration} years) is less than minimum required ({$ins_min_license_years} years)<br>\n";
            return false;
        }

        // 6. Business logic validation: Vehicle class matching
        if ($vehicle_type != $ins_vehicle_class) {
            echo "[INSERT ERROR] Vehicle type ({$vehicle_type}) does not match insurance vehicle class ({$ins_vehicle_class})<br>\n";
            return false;
        }

        // 7. Duplicate check: all fields match
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Rental_Reservation WHERE uid = ? AND vid = ? AND iid = ? AND start_date = ? AND end_date = ?");
        $stmt->bind_param("iiiss", $user_id, $vehicle_id, $insurance_id, $start_date, $end_date);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            echo "[INSERT SKIPPED] Reservation already exist<br>\n";
            $stmt->close();
            return false;
        }
        $stmt->close();

        // 8. Insert new reservation
        $stmt = $conn->prepare("INSERT INTO Rental_Reservation (uid, vid, iid, start_date, end_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiss", $user_id, $vehicle_id, $insurance_id, $start_date, $end_date);

        if ($stmt->execute()) {
            echo "[INSERT OK] Reservation added<br>\n";
            $stmt->close();
            return true;
        } else {
            echo "[INSERT ERROR] Error adding reservation: " . $stmt->error . "<br>\n";
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        echo "[INSERT ERROR] Error adding reservation: " . $e->getMessage() . "<br>\n";
        return false;
    }
}

/**
 * 모든 예약 조회 (Fetch all reservations)
 * @return array - Array of reservation records
 */
function select_all_reservations()
{
    global $conn;

    try {
        $sql = "SELECT * FROM Rental_Reservation";
        $result = $conn->query($sql);

        if (!$result) {
            echo "[SELECT ERROR] " . $conn->error . "<br>\n";
            return [];
        }

        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }

        echo "<br>=== Rental_Reservation Table ===<br>\n";
        foreach ($reservations as $res) {
            echo "ID: {$res['id']}, UID: {$res['uid']}, VID: {$res['vid']}, IID: {$res['iid']}, " .
                "Start: {$res['start_date']}, End: {$res['end_date']}<br>\n";
        }

        return $reservations;

    } catch (Exception $e) {
        echo "[SELECT ERROR] " . $e->getMessage() . "<br>\n";
        return [];
    }
}

/**
 * 예약 정보 수정 (충돌 감지 포함)
 * @param int $res_id 
 * @param string|null $new_start_date 
 * @param string|null $new_end_date 
 * @return bool 
 */
function update_reservation($res_id, $new_start_date = null, $new_end_date = null)
{
    global $conn;

    // 날짜 검증
    if ($new_start_date && !valid_date($new_start_date)) {
        return false;
    }
    if ($new_end_date && !valid_date($new_end_date)) {
        return false;
    }

    try {
        // Start transaction
        $conn->begin_transaction();

        // 예약 정보 조회 
        $stmt = $conn->prepare("SELECT vid, start_date, end_date FROM Rental_Reservation WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $res_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            echo "[UPDATE ERROR] Reservation not found.<br>\n";
            $conn->rollback();
            return false;
        }

        $vehicle_id = $row['vid'];
        $old_start = $row['start_date'];
        $old_end = $row['end_date'];

        $new_start = $new_start_date ?: $old_start;
        $new_end = $new_end_date ?: $old_end;

        // 충돌 검사
        $stmt = $conn->prepare("SELECT id FROM Rental_Reservation WHERE vid = ? AND id != ? AND start_date <= ? AND end_date >= ? FOR UPDATE");
        $stmt->bind_param("iiss", $vehicle_id, $res_id, $new_end, $new_start);
        $stmt->execute();
        $result = $stmt->get_result();
        $conflict = $result->fetch_assoc();
        $stmt->close();

        if ($conflict) {
            echo "[UPDATE CONFLICT] Overlaps with reservation {$conflict['id']}.<br>\n";
            $conn->rollback();
            return false;
        }

        // Update
        $stmt = $conn->prepare("UPDATE Rental_Reservation SET start_date = ?, end_date = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_start, $new_end, $res_id);

        if ($stmt->execute()) {
            $conn->commit();
            echo "[UPDATE OK] Reservation updated.<br>\n";
            $stmt->close();
            return true;
        } else {
            $conn->rollback();
            echo "[UPDATE ERROR] " . $stmt->error . "<br>\n";
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        $conn->rollback();
        echo "[UPDATE ERROR] " . $e->getMessage() . "<br>\n";
        return false;
    }
}

/**
 * 예약 삭제
 * @param int $res_id 
 * @return bool
 */
function delete_reservation($res_id)
{
    global $conn;

    try {
        $stmt = $conn->prepare("DELETE FROM Rental_Reservation WHERE id = ?");
        $stmt->bind_param("i", $res_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows == 0) {
                echo "[DELETE] Reservation {$res_id} not found.<br>\n";
                $stmt->close();
                return false;
            }

            echo "[DELETE OK] Reservation {$res_id} deleted.<br>\n";
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

/**
 * 조건별 예약 조회
 * @param int|null $reservation_id 
 * @param int|null $user_id 
 * @param int|null $vehicle_id 
 * @param int|null $insurance_id 
 * @param string|null $start_date - >=
 * @param string|null $end_date - <=
 * @return array - Array of matching reservation records
 */
function select_reservation_by_filters(
    $reservation_id = null,
    $user_id = null,
    $vehicle_id = null,
    $insurance_id = null,
    $start_date = null,
    $end_date = null
) {
    global $conn;

    try {
        $sql = "SELECT * FROM Rental_Reservation WHERE 1=1";
        $params = [];
        $types = "";

        if ($reservation_id !== null) {
            $sql .= " AND id = ?";
            $params[] = $reservation_id;
            $types .= "i";
        }

        if ($user_id !== null) {
            $sql .= " AND uid = ?";
            $params[] = $user_id;
            $types .= "i";
        }

        if ($vehicle_id !== null) {
            $sql .= " AND vid = ?";
            $params[] = $vehicle_id;
            $types .= "i";
        }

        if ($insurance_id !== null) {
            $sql .= " AND iid = ?";
            $params[] = $insurance_id;
            $types .= "i";
        }

        if ($start_date !== null) {
            $sql .= " AND start_date >= ?";
            $params[] = $start_date;
            $types .= "s";
        }

        if ($end_date !== null) {
            $sql .= " AND end_date <= ?";
            $params[] = $end_date;
            $types .= "s";
        }

        $stmt = $conn->prepare($sql);

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $reservations = [];
        while ($row = $result->fetch_assoc()) {
            $reservations[] = $row;
        }

        echo "<br>=== Filtered Reservations ===<br>\n";
        foreach ($reservations as $res) {
            echo "ID: {$res['id']}, UID: {$res['uid']}, VID: {$res['vid']}, IID: {$res['iid']}, " .
                "Start: {$res['start_date']}, End: {$res['end_date']}<br>\n";
        }

        $stmt->close();
        return $reservations;

    } catch (Exception $e) {
        echo "[SELECT ERROR] " . $e->getMessage() . "<br>\n";
        return [];
    }
}
?>