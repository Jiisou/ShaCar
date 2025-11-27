<?php
/**
 * Utility Functions
 * Validation helpers for dates and numbers
 */

/**
 * 정수값 검증
 * @param mixed $value - The value to validate
 * @param string $field_name - Field name for error messages
 * @return bool - Returns true if valid, false otherwise
 */
function valid_number($value, $field_name)
{
    if ($value === null) {
        return false;
    }

    if (!is_numeric($value) || intval($value) != $value) {
        echo "[Invalid Number] {$field_name} must be an integer. Given: {$value}<br>\n";
        return false;
    }

    return true;
}

/**
 * 날짜값 검증
 * @param string $date_str - The date string to validate
 * @return bool - Returns true if valid, false otherwise
 */
function valid_date($date_str)
{
    if (!is_string($date_str)) {
        $type = gettype($date_str);
        echo "[Invalid Type] Date must be a string in 'YYYY-MM-DD' format. Given: {$type}<br>\n";
        return false;
    }

    // 날짜값 파싱
    $date = DateTime::createFromFormat('Y-m-d', $date_str);

    // Check if the date was parsed correctly and matches the format exactly
    if (!$date || $date->format('Y-m-d') !== $date_str) {
        echo "[Invalid Date] Use YYYY-MM-DD. Given: {$date_str}<br>\n";
        return false;
    }

    return true;
}
?>