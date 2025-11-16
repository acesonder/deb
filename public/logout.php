<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/logger.php';

// Log before logout
if (isLoggedIn()) {
    $user_id = getCurrentUserId();
    $username = $_SESSION['username'] ?? 'unknown';
    logLogout($username, $user_id);
}

logout();

header('Location: login.php');
exit();
?>
