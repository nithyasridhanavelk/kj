<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['customer_id'])) {
    $customerId = (int)$_SESSION['customer_id'];
    $stmt = $conn->prepare('UPDATE customers SET is_signed_in = 0 WHERE id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $stmt->close();
    }
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();
header('Location: login.php?logged_out=1');
exit;
