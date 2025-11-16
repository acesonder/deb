<?php
/**
 * Admin Logging Utility
 * Logs system events, errors, and user actions
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Log an event to the admin logs
 * 
 * @param string $level - 'info', 'warning', 'error', 'critical'
 * @param string $type - Type of log (e.g., 'form_submission', 'login', 'database_error')
 * @param string $message - The log message
 * @param int|null $user_id - Optional user ID
 * @param array|null $additional_data - Optional additional data to store as JSON
 * @return bool - True if logged successfully
 */
function logEvent($level, $type, $message, $user_id = null, $additional_data = null) {
    try {
        $conn = getDBConnection();
        
        // Get client information
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        // Convert additional data to JSON if provided
        $json_data = $additional_data ? json_encode($additional_data) : null;
        
        $stmt = $conn->prepare(
            "INSERT INTO admin_logs (log_level, log_type, message, user_id, ip_address, user_agent, request_uri, additional_data) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        $stmt->bind_param(
            "sssissss",
            $level,
            $type,
            $message,
            $user_id,
            $ip_address,
            $user_agent,
            $request_uri,
            $json_data
        );
        
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        
        return $success;
    } catch (Exception $e) {
        // If logging fails, write to PHP error log as fallback
        error_log("Failed to log to database: " . $e->getMessage());
        return false;
    }
}

/**
 * Log an info event
 */
function logInfo($type, $message, $user_id = null, $additional_data = null) {
    return logEvent('info', $type, $message, $user_id, $additional_data);
}

/**
 * Log a warning event
 */
function logWarning($type, $message, $user_id = null, $additional_data = null) {
    return logEvent('warning', $type, $message, $user_id, $additional_data);
}

/**
 * Log an error event
 */
function logError($type, $message, $user_id = null, $additional_data = null) {
    return logEvent('error', $type, $message, $user_id, $additional_data);
}

/**
 * Log a critical event
 */
function logCritical($type, $message, $user_id = null, $additional_data = null) {
    return logEvent('critical', $type, $message, $user_id, $additional_data);
}

/**
 * Log form submission
 */
function logFormSubmission($form_name, $success, $user_id = null, $errors = []) {
    $level = $success ? 'info' : 'error';
    $message = $success 
        ? "Form '$form_name' submitted successfully"
        : "Form '$form_name' submission failed";
    
    $additional_data = [
        'form_name' => $form_name,
        'success' => $success,
        'errors' => $errors,
        'post_data_keys' => array_keys($_POST)
    ];
    
    return logEvent($level, 'form_submission', $message, $user_id, $additional_data);
}

/**
 * Log form validation error
 */
function logValidationError($form_name, $errors, $user_id = null) {
    $message = "Validation failed for form '$form_name': " . implode(', ', $errors);
    
    $additional_data = [
        'form_name' => $form_name,
        'errors' => $errors,
        'post_data' => array_map(function($value) {
            return is_string($value) && strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
        }, $_POST)
    ];
    
    return logEvent('warning', 'form_validation', $message, $user_id, $additional_data);
}

/**
 * Log database error
 */
function logDatabaseError($query_type, $error_message, $user_id = null) {
    $message = "Database error during $query_type: $error_message";
    
    $additional_data = [
        'query_type' => $query_type,
        'error' => $error_message
    ];
    
    return logEvent('error', 'database_error', $message, $user_id, $additional_data);
}

/**
 * Log login attempt
 */
function logLogin($username, $success, $user_id = null) {
    $level = $success ? 'info' : 'warning';
    $message = $success 
        ? "Successful login for user '$username'"
        : "Failed login attempt for user '$username'";
    
    return logEvent($level, 'login', $message, $user_id);
}

/**
 * Log logout
 */
function logLogout($username, $user_id) {
    return logEvent('info', 'logout', "User '$username' logged out", $user_id);
}

/**
 * Get recent logs
 * 
 * @param int $limit - Number of logs to retrieve
 * @param string|null $level - Filter by log level
 * @param string|null $type - Filter by log type
 * @return array - Array of log entries
 */
function getRecentLogs($limit = 100, $level = null, $type = null) {
    try {
        $conn = getDBConnection();
        
        $query = "SELECT l.*, u.username 
                  FROM admin_logs l 
                  LEFT JOIN users u ON l.user_id = u.user_id 
                  WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if ($level) {
            $query .= " AND l.log_level = ?";
            $params[] = $level;
            $types .= "s";
        }
        
        if ($type) {
            $query .= " AND l.log_type = ?";
            $params[] = $type;
            $types .= "s";
        }
        
        $query .= " ORDER BY l.log_date DESC LIMIT ?";
        $params[] = $limit;
        $types .= "i";
        
        $stmt = $conn->prepare($query);
        
        if ($types) {
            $stmt->bind_param($types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $logs = [];
        while ($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        return $logs;
    } catch (Exception $e) {
        error_log("Failed to retrieve logs: " . $e->getMessage());
        return [];
    }
}

/**
 * Get log statistics
 */
function getLogStatistics($days = 7) {
    try {
        $conn = getDBConnection();
        
        $query = "SELECT 
                    log_level,
                    log_type,
                    COUNT(*) as count
                  FROM admin_logs
                  WHERE log_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY log_level, log_type
                  ORDER BY count DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $days);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $stats = [];
        while ($row = $result->fetch_assoc()) {
            $stats[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        return $stats;
    } catch (Exception $e) {
        error_log("Failed to retrieve log statistics: " . $e->getMessage());
        return [];
    }
}
?>
