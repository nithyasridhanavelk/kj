<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/cart_helpers.php';
require_once __DIR__ . '/includes/google_helper.php';

function kinz_google_fail(string $message): void {
    $_SESSION['google_auth_error'] = $message;
    header('Location: login.php');
    exit;
}

if (isset($_GET['error'])) kinz_google_fail('Google sign-in was cancelled.');

$state = (string)($_GET['state'] ?? '');
$expectedState = (string)($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state']);
if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    kinz_google_fail('Google sign-in could not be verified. Please try again.');
}

$code = (string)($_GET['code'] ?? '');
if ($code === '') kinz_google_fail('Google sign-in did not complete. Please try again.');

try {
    $token = kinz_google_request('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => kinz_google_redirect_uri(),
        'grant_type' => 'authorization_code'
    ]);
    $profile = kinz_google_request('https://openidconnect.googleapis.com/v1/userinfo', null, (string)($token['access_token'] ?? ''));
} catch (Throwable $error) {
    kinz_google_fail('Google sign-in failed: ' . $error->getMessage());
}

$email = strtolower(trim((string)($profile['email'] ?? '')));
$emailVerified = $profile['email_verified'] ?? false;
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !($emailVerified === true || $emailVerified === 'true')) {
    kinz_google_fail('Your Google account does not have a verified email address.');
}

$findStmt = $conn->prepare('SELECT id, cust_code, name, email, is_blocked, status FROM customers WHERE email = ? LIMIT 1');
$findStmt->bind_param('s', $email);
$findStmt->execute();
$customerRecord = $findStmt->get_result()->fetch_assoc();
$findStmt->close();

if (!$customerRecord) {
    $name = trim((string)($profile['name'] ?? ''));
    if ($name === '') $name = ucfirst((string)strstr($email, '@', true));
    $custCode = 'CUST-' . strtoupper(bin2hex(random_bytes(4)));
    $regCode = 'KINZ-REG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $randomPassword = bin2hex(random_bytes(16));
    $registeredDate = date('Y-m-d');
    $phone = '';
    $tier = 'Member';
    $status = 'Active';
    $method = 'Google';
    $insert = $conn->prepare('INSERT INTO customers (cust_code, name, email, password, phone, tier, status, is_blocked, is_signed_in, total_spent, total_orders, registered_date, registration_method, reg_code) VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0, 0.00, 0, ?, ?, ?)');
    $insert->bind_param('ssssssssss', $custCode, $name, $email, $randomPassword, $phone, $tier, $status, $registeredDate, $method, $regCode);
    if (!$insert->execute()) kinz_google_fail('Your account could not be created. Please try again.');
    $insert->close();

    $findStmt = $conn->prepare('SELECT id, cust_code, name, email, is_blocked, status FROM customers WHERE email = ? LIMIT 1');
    $findStmt->bind_param('s', $email);
    $findStmt->execute();
    $customerRecord = $findStmt->get_result()->fetch_assoc();
    $findStmt->close();
    if (!$customerRecord) kinz_google_fail('Your account could not be created. Please try again.');
}

if ((int)$customerRecord['is_blocked'] === 1 || $customerRecord['status'] !== 'Active') {
    kinz_google_fail('Your account is currently blocked or inactive. Please contact KINZ support.');
}

session_regenerate_id(true);
$_SESSION['customer_id'] = (int)$customerRecord['id'];
$_SESSION['customer_code'] = $customerRecord['cust_code'];
$_SESSION['customer_name'] = $customerRecord['name'];
$_SESSION['customer_email'] = $customerRecord['email'];
kinz_cart_merge_session_into_customer($conn, (int)$customerRecord['id']);

$update = $conn->prepare('UPDATE customers SET is_signed_in = 1 WHERE id = ?');
$update->bind_param('i', $customerRecord['id']);
$update->execute();
$update->close();

$redirectAfterLogin = $_SESSION['redirect_after_login'] ?? 'afterloginindex.html';
unset($_SESSION['redirect_after_login']);
if (!in_array($redirectAfterLogin, ['checkout.php', 'cart.php', 'afterloginindex.html'], true)) {
    $redirectAfterLogin = 'afterloginindex.html';
}

$pendingReviewStmt = $conn->prepare("SELECT COUNT(*) AS pending_count
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    LEFT JOIN reviews r ON r.order_item_id = oi.id
    WHERE LOWER(TRIM(o.customer_email)) = LOWER(TRIM(?))
      AND LOWER(TRIM(o.status)) = 'delivered'
      AND oi.product_id IS NOT NULL AND oi.product_id > 0
      AND r.id IS NULL");
if ($pendingReviewStmt) {
    $pendingReviewStmt->bind_param('s', $customerRecord['email']);
    $pendingReviewStmt->execute();
    $pendingReviewCount = (int)($pendingReviewStmt->get_result()->fetch_assoc()['pending_count'] ?? 0);
    $pendingReviewStmt->close();
    if ($pendingReviewCount > 0) $redirectAfterLogin = 'my-orders.php?review_required=1';
}

header('Location: ' . $redirectAfterLogin);
exit;
