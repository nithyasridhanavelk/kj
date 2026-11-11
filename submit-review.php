<?php
require_once __DIR__ . '/includes/customer_login_guard.php';
kinz_customer_ensure_review_schema();

if (empty($_SESSION['customer_id']) || empty($_SESSION['customer_email'])) {
    header('Location: login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-orders.php');
    exit;
}

$mandatoryReview = (string)($_POST['mandatory_review'] ?? '') === '1';
function kinz_review_redirect(string $message): void {
    global $mandatoryReview;
    $_SESSION['kinz_review_notice'] = ['message' => $message];
    header('Location: ' . ($mandatoryReview ? 'my-orders.php?review_required=1' : 'my-orders.php'));
    exit;
}

$postedToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['kinz_review_csrf'] ?? '');
if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    kinz_review_redirect('Your review form expired. Please refresh My Orders and try again.');
}

$orderItemId = (int)($_POST['order_item_id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$title = trim((string)($_POST['title'] ?? ''));
$reviewText = trim((string)($_POST['review_text'] ?? ''));
$title = function_exists('mb_substr') ? mb_substr($title, 0, 120) : substr($title, 0, 120);
$reviewText = function_exists('mb_substr') ? mb_substr($reviewText, 0, 1500) : substr($reviewText, 0, 1500);

if ($orderItemId < 1 || $rating < 1 || $rating > 5 || strlen($reviewText) < 5) {
    kinz_review_redirect('Please choose a rating from 1 to 5 stars and enter at least 5 characters of feedback.');
}

$email = (string)$_SESSION['customer_email'];
$eligibilityStmt = $conn->prepare("SELECT oi.id AS order_item_id, oi.product_id, oi.product_title, o.id AS order_id, o.order_code, o.customer_name, o.customer_email, o.status FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id WHERE oi.id = ? AND LOWER(TRIM(o.customer_email)) = LOWER(TRIM(?)) LIMIT 1");
if (!$eligibilityStmt) kinz_review_redirect('Unable to validate this purchase right now. Please try again.');
$eligibilityStmt->bind_param('is', $orderItemId, $email);
$eligibilityStmt->execute();
$purchase = $eligibilityStmt->get_result()->fetch_assoc();
$eligibilityStmt->close();

if (!$purchase || (int)$purchase['product_id'] < 1) {
    kinz_review_redirect('This product could not be linked to a valid order. Please contact KINZ support if you need help.');
}
if (strtolower(trim((string)$purchase['status'])) !== 'delivered') {
    kinz_review_redirect('You can review a product only after its order has been delivered.');
}

$existingStmt = $conn->prepare('SELECT id FROM reviews WHERE order_item_id = ? LIMIT 1');
if (!$existingStmt) kinz_review_redirect('Unable to check your previous review right now. Please try again.');
$existingStmt->bind_param('i', $orderItemId);
$existingStmt->execute();
$existing = $existingStmt->get_result()->fetch_assoc();
$existingStmt->close();
if ($existing) kinz_review_redirect('You have already submitted a review for this product in this order.');

$customerName = (string)($purchase['customer_name'] ?: ($_SESSION['customer_name'] ?? 'KINZ Customer'));
$customerEmail = (string)$purchase['customer_email'];
$productId = (int)$purchase['product_id'];
$orderId = (int)$purchase['order_id'];
$productName = (string)$purchase['product_title'];
$reviewCode = 'REV-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

$conn->begin_transaction();
try {
    $insertStmt = $conn->prepare("INSERT INTO reviews (review_code, customer_name, customer_email, product_id, order_id, order_item_id, verified_purchase, product_name, rating, title, review_text, status) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?, 'Approved')");
    if (!$insertStmt) throw new RuntimeException('Unable to prepare your review. ' . $conn->error);
    $insertStmt->bind_param('sssiiisiss', $reviewCode, $customerName, $customerEmail, $productId, $orderId, $orderItemId, $productName, $rating, $title, $reviewText);
    if (!$insertStmt->execute()) {
        $error = $insertStmt->error;
        $insertStmt->close();
        throw new RuntimeException($error ?: 'Your review could not be saved.');
    }
    $insertStmt->close();
    $conn->commit();
    $_SESSION['kinz_review_notice'] = ['message' => 'Thank you! Your verified review has been submitted.'];
    if ($mandatoryReview) {
        header('Location: my-orders.php?review_required=1#order-' . $orderId);
    } else {
        header('Location: my-orders.php#order-' . $orderId);
    }
    exit;
} catch (Throwable $error) {
    $conn->rollback();
    if (stripos($error->getMessage(), 'duplicate') !== false) {
        kinz_review_redirect('You have already submitted a review for this product in this order.');
    }
    kinz_review_redirect('Your review could not be saved. Please try again.');
}
