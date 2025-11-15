<?php
/**
 * Authentication and Session Management
 */

session_start();

require_once __DIR__ . '/../config/database.php';

/**
 * Login function
 */
function login($username, $password) {
    $conn = getDBConnection();
    
    $stmt = $conn->prepare("SELECT user_id, username, password_hash, full_name, role, is_active FROM users WHERE username = ? AND is_active = TRUE");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password_hash'])) {
            // Update last login
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_stmt->bind_param("i", $user['user_id']);
            $update_stmt->execute();
            
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            $stmt->close();
            $update_stmt->close();
            $conn->close();
            
            return true;
        }
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

/**
 * Logout function
 */
function logout() {
    session_unset();
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if user has specific role
 */
function hasRole($role) {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require login - redirect if not logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /public/login.php');
        exit();
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: /public/dashboard.php');
        exit();
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user role
 */
function getCurrentUserRole() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

/**
 * Change password
 */
function changePassword($user_id, $new_password) {
    $conn = getDBConnection();
    
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param("si", $password_hash, $user_id);
    
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return $success;
}

/**
 * Create new user
 */
function createUser($username, $password, $full_name, $role, $email = null) {
    $conn = getDBConnection();
    
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $created_by = getCurrentUserId();
    
    $stmt = $conn->prepare("INSERT INTO users (username, password_hash, full_name, email, role, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssi", $username, $password_hash, $full_name, $email, $role, $created_by);
    
    $success = $stmt->execute();
    $new_user_id = $conn->insert_id;
    
    $stmt->close();
    $conn->close();
    
    return $success ? $new_user_id : false;
}
?>
