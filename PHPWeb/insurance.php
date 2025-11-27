<?php
/**
 * CRUD operations for Insurance_Plan table
 * Schema: Insurance_Plan (id, type, daily_fee, deductible_amount, 
 *                         vehicle_class, min_driver_age, min_license_years, 
 *                         name)
 */

require_once 'db.php';
require_once 'utils.php';

/**
 * 모든 보험 플랜 조회 (Fetch all insurance plans)
 * @return array - Array of insurance plan records
 */
function select_all_insurance_plans()
{
    global $conn;

    try {
        $sql = "SELECT * FROM Insurance_Plan";
        $result = $conn->query($sql);

        if (!$result) {
            echo "[SELECT ERROR] " . $conn->error . "<br>\n";
            return [];
        }

        $plans = [];
        while ($row = $result->fetch_assoc()) {
            $plans[] = $row;
        }

        echo "<br>=== Insurance_Plan Table ===<br>\n";
        foreach ($plans as $plan) {
            echo "ID: {$plan['id']}, Name: {$plan['name']}, Type: {$plan['type']}, " .
                "Vehicle Class: {$plan['vehicle_class']}, Daily Fee: {$plan['daily_fee']}, " .
                "Min Age: {$plan['min_driver_age']}, Min License Years: {$plan['min_license_years']}<br>\n";
        }

        return $plans;

    } catch (Exception $e) {
        echo "[SELECT ERROR] " . $e->getMessage() . "<br>\n";
        return [];
    }
}

/**
 * 새로운 보험 플랜 추가 (Insert a new insurance plan)
 * @param string $insurance_type 
 * @param float $daily_fee 
 * @param float $deductible_amount - Deductible amount
 * @param string $vehicle_class 
 * @param int $min_driver_age 
 * @param int $min_license_years 
 * @param string $name - Insurance plan name
 * @return bool - True if successful, false otherwise
 */
function insert_insurance_plan(
    $insurance_type,
    $daily_fee,
    $deductible_amount,
    $vehicle_class,
    $min_driver_age,
    $min_license_years,
    $name
) {
    global $conn;

    // Validate numeric fields
    if (!valid_number($daily_fee, "daily_fee"))
        return false;
    if (!valid_number($deductible_amount, "deductible_amount"))
        return false;
    if (!valid_number($min_driver_age, "min_driver_age"))
        return false;
    if (!valid_number($min_license_years, "min_license_years"))
        return false;

    try {
        // Check for duplicates: type, vehicle_class, and daily_fee all match
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM Insurance_Plan WHERE type = ? AND vehicle_class = ? AND daily_fee = ?");
        $stmt->bind_param("ssd", $insurance_type, $vehicle_class, $daily_fee);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            echo "Insurance plan already exists.<br>\n";
            $stmt->close();
            return false;
        }
        $stmt->close();

        // Insert new insurance plan
        $stmt = $conn->prepare("INSERT INTO Insurance_Plan (type, daily_fee, deductible_amount, vehicle_class, min_driver_age, min_license_years, name) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sddsiis", $insurance_type, $daily_fee, $deductible_amount, $vehicle_class, $min_driver_age, $min_license_years, $name);

        if ($stmt->execute()) {
            echo "[INSERT OK] Insurance plan added.<br>\n";
            $stmt->close();
            return true;
        } else {
            echo "[INSERT ERROR] " . $stmt->error . "<br>\n";
            $stmt->close();
            return false;
        }

    } catch (Exception $e) {
        echo "[INSERT ERROR] " . $e->getMessage() . "<br>\n";
        return false;
    }
}

/**
 * 보험 플랜 정보 수정 (Update insurance plan information)
 * @param int $plan_id - Insurance plan ID to update
 * @param string|null $new_type
 * @param float|null $new_daily_fee 
 * @param float|null $new_deductible_amount 
 * @param string|null $new_vehicle_class 
 * @param int|null $new_min_driver_age 
 * @param int|null $new_min_license_years 
 * @param string|null $new_name 
 * @return bool - True if successful, false otherwise
 */
function update_insurance_plan(
    $plan_id,
    $new_type = null,
    $new_daily_fee = null,
    $new_deductible_amount = null,
    $new_vehicle_class = null,
    $new_min_driver_age = null,
    $new_min_license_years = null,
    $new_name = null
) {
    global $conn;

    // Validate numeric fields if provided
    $numeric_checks = [
        [$new_daily_fee, "daily_fee"],
        [$new_deductible_amount, "deductible_amount"],
        [$new_min_driver_age, "min_driver_age"],
        [$new_min_license_years, "min_license_years"]
    ];

    foreach ($numeric_checks as list($val, $name)) {
        if ($val !== null && !valid_number($val, $name)) {
            return false;
        }
    }

    try {
        $sql = "UPDATE Insurance_Plan SET ";
        $params = [];
        $types = "";

        if ($new_type !== null) {
            $sql .= "type = ?, ";
            $params[] = $new_type;
            $types .= "s";
        }

        if ($new_daily_fee !== null) {
            $sql .= "daily_fee = ?, ";
            $params[] = $new_daily_fee;
            $types .= "d";
        }

        if ($new_deductible_amount !== null) {
            $sql .= "deductible_amount = ?, ";
            $params[] = $new_deductible_amount;
            $types .= "d";
        }

        if ($new_vehicle_class !== null) {
            $sql .= "vehicle_class = ?, ";
            $params[] = $new_vehicle_class;
            $types .= "s";
        }

        if ($new_min_driver_age !== null) {
            $sql .= "min_driver_age = ?, ";
            $params[] = $new_min_driver_age;
            $types .= "i";
        }

        if ($new_min_license_years !== null) {
            $sql .= "min_license_years = ?, ";
            $params[] = $new_min_license_years;
            $types .= "i";
        }

        if ($new_name !== null) {
            $sql .= "name = ?, ";
            $params[] = $new_name;
            $types .= "s";
        }

        // No fields to update
        if (empty($params)) {
            echo "[UPDATE SKIPPED] No values provided.<br>\n";
            return false;
        }

        $sql = rtrim($sql, ", ") . " WHERE id = ?";
        $params[] = $plan_id;
        $types .= "i";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo "[UPDATE OK] Insurance plan {$plan_id} updated.<br>\n";
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
 * 보험 플랜 삭제 (Delete an insurance plan by ID)
 * @param int $plan_id 
 * @return bool - True if successful, false otherwise
 */
function delete_insurance_plan($plan_id)
{
    global $conn;

    try {
        $stmt = $conn->prepare("DELETE FROM Insurance_Plan WHERE id = ?");
        $stmt->bind_param("i", $plan_id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows == 0) {
                echo "[DELETE] Insurance plan {$plan_id} not found.<br>\n";
                $stmt->close();
                return false;
            }

            echo "[DELETE OK] Insurance plan {$plan_id} deleted.<br>\n";
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