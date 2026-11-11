<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/mailer.php';
header('Content-Type: application/json; charset=utf-8');

function kinz_forgot_reply(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    kinz_forgot_reply(405, ['success' => false, 'message' => 'Invalid request.']);
}

$conn->query("CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    expires_at DATETIME NOT NULL,
    attempts INT NOT NULL DEFAULT 0,
    used TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_password_resets_customer (customer_id, used)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$action = (string)($_POST['action'] ?? '');
$identifier = strtolower(trim((string)($_POST['identifier'] ?? '')));
if ($identifier === '') {
    kinz_forgot_reply(422, ['success' => false, 'message' => 'Enter your registered email or mobile number.']);
}

$findStmt = $conn->prepare('SELECT id, name, email, is_blocked, status FROM customers WHERE LOWER(email) = ? OR phone = ? LIMIT 1');
$findStmt->bind_param('ss', $identifier, $identifier);
$findStmt->execute();
$customer = $findStmt->get_result()->fetch_assoc();
$findStmt->close();
$customerActive = $customer && (int)$customer['is_blocked'] !== 1 && $customer['status'] === 'Active';

if ($action === 'send_code') {
    $genericMessage = 'If an account exists for these details, a 6-digit code has been sent to its registered email address.';

    if ($customerActive) {
        $customerId = (int)$customer['id'];
        $recentStmt = $conn->prepare('SELECT COUNT(*) AS total FROM password_resets WHERE customer_id = ? AND created_at > (NOW() - INTERVAL 1 HOUR)');
        $recentStmt->bind_param('i', $customerId);
        $recentStmt->execute();
        $recentCount = (int)($recentStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $recentStmt->close();

        if ($recentCount < 3) {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codeHash = password_hash($code, PASSWORD_DEFAULT);

            $expireStmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE customer_id = ? AND used = 0');
            $expireStmt->bind_param('i', $customerId);
            $expireStmt->execute();
            $expireStmt->close();

            $insertStmt = $conn->prepare('INSERT INTO password_resets (customer_id, code_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL 10 MINUTE)');
            $insertStmt->bind_param('is', $customerId, $codeHash);
            $insertStmt->execute();
            $insertStmt->close();

            $body = "Hello " . $customer['name'] . ",\n\n"
                . "Your KINZ password reset code is: " . $code . "\n\n"
                . "This code is valid for 10 minutes. If you did not ask to reset your password, you can ignore this email.\n\n"
                . "KINZ - A Hidden Treasure";
            try {
                kinz_send_mail((string)$customer['email'], 'Your KINZ password reset code', $body);
            } catch (Throwable $error) {
                kinz_forgot_reply(502, ['success' => false, 'message' => 'We could not send the email right now. Please try again later.']);
            }
        }
    }
    kinz_forgot_reply(200, ['success' => true, 'message' => $genericMessage]);
}

if ($action === 'reset') {
    $code = trim((string)($_POST['code'] ?? ''));
    $newPassword = (string)($_POST['new_password'] ?? '');
    $invalidMessage = 'That code is incorrect or has expired. Please request a new one.';

    if (!preg_match('/^\d{6}$/', $code)) {
        kinz_forgot_reply(422, ['success' => false, 'message' => 'Enter the 6-digit code from your email.']);
    }
    if (strlen($newPassword) < 6) {
        kinz_forgot_reply(422, ['success' => false, 'message' => 'Your new password must contain at least 6 characters.']);
    }
    if (!$customerActive) {
        kinz_forgot_reply(422, ['success' => false, 'message' => $invalidMessage]);
    }

    $customerId = (int)$customer['id'];
    $resetStmt = $conn->prepare('SELECT id, code_hash, attempts FROM password_resets WHERE customer_id = ? AND used = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
    $resetStmt->bind_param('i', $customerId);
    $resetStmt->execute();
    $resetRow = $resetStmt->get_result()->fetch_assoc();
    $resetStmt->close();

    if (!$resetRow || (int)$resetRow['attempts'] >= 5) {
        kinz_forgot_reply(422, ['success' => false, 'message' => $invalidMessage]);
    }

    if (!password_verify($code, (string)$resetRow['code_hash'])) {
        $attemptStmt = $conn->prepare('UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?');
        $resetId = (int)$resetRow['id'];
        $attemptStmt->bind_param('i', $resetId);
        $attemptStmt->execute();
        $attemptStmt->close();
        kinz_forgot_reply(422, ['success' => false, 'message' => $invalidMessage]);
    }

    $passwordStmt = $conn->prepare('UPDATE customers SET password = ? WHERE id = ?');
    $passwordStmt->bind_param('si', $newPassword, $customerId);
    $saved = $passwordStmt->execute();
    $passwordStmt->close();
    if (!$saved) {
        kinz_forgot_reply(500, ['success' => false, 'message' => 'Your password could not be updated. Please try again.']);
    }

    $usedStmt = $conn->prepare('UPDATE password_resets SET used = 1 WHERE customer_id = ?');
    $usedStmt->bind_param('i', $customerId);
    $usedStmt->execute();
    $usedStmt->close();

    kinz_forgot_reply(200, ['success' => true, 'message' => 'Your password has been updated. Please sign in with your new password.']);
}

kinz_forgot_reply(400, ['success' => false, 'message' => 'Unknown request.']);
