<?php
// Redirect to login or dashboard depending on auth status
require_once __DIR__ . '/../includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    header('Location: login.php');
}
exit();
?>
