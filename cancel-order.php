<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/razorpay_helper.php';

function kinz_cancel_redirect(bool $success, string $message): void {
    $_SESSION['kinz_order_cancel_notice'] = [
        'success' => $success,
        'message' => $message
    ];
    header('Location: my-orders.php');
    exit;
}

if (empty($_SESSION['customer_id']) || empty($_SESSION['customer_email'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: my-orders.php');
    exit;
}

$postedToken = (string)($_POST['csrf_token'] ?? '');
$sessionToken = (string)($_SESSION['kinz_order_cancel_csrf'] ?? '');
if ($postedToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $postedToken)) {
    kinz_cancel_redirect(false, 'Your session expired. Refresh My Orders and try again.');
}

$orderId = (int)($_POST['order_id'] ?? 0);
$customerEmail = (string)$_SESSION['customer_email'];
if ($orderId < 1) {
    kinz_cancel_redirect(false, 'Please select a valid order.');
}

try {
    $conn->begin_transaction();

    $orderStmt = $conn->prepare('SELECT id, order_code, status, total_amount, coupon_code, payment_status, razorpay_payment_id FROM orders WHERE id = ? AND customer_email = ? LIMIT 1 FOR UPDATE');
    if (!$orderStmt) {
        throw new RuntimeException('Unable to verify this order. Please try again.');
    }
    $orderStmt->bind_param('is', $orderId, $customerEmail);
    if (!$orderStmt->execute()) {
        throw new RuntimeException('Unable to verify this order. Please try again.');
    }
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();

    if (!$order) {
        throw new RuntimeException('This order was not found in your account.');
    }

    $currentStatus = strtolower(trim((string)$order['status']));
    if ($currentStatus === 'cancelled') {
        throw new RuntimeException('This order has already been cancelled.');
    }
    if (!in_array($currentStatus, ['pending', 'confirmed', 'processing'], true)) {
        throw new RuntimeException('This order can no longer be cancelled because it has been shipped or completed.');
    }

    $isPaid = strcasecmp((string)$order['payment_status'], 'Paid') === 0;
    if ($isPaid && trim((string)$order['razorpay_payment_id']) === '') {
        throw new RuntimeException('The payment for this order could not be found. Please contact KINZ support.');
    }

    $itemsStmt = $conn->prepare('SELECT product_id, qty, line_total FROM order_items WHERE order_id = ? FOR UPDATE');
    if (!$itemsStmt) {
        throw new RuntimeException('Unable to load the products in this order.');
    }
    $itemsStmt->bind_param('i', $orderId);
    if (!$itemsStmt->execute()) {
        throw new RuntimeException('Unable to load the products in this order.');
    }
    $itemsResult = $itemsStmt->get_result();

    $stockStmt = $conn->prepare('UPDATE products SET stock = stock + ?, sales_count = GREATEST(IFNULL(sales_count, 0) - ?, 0), revenue_generated = GREATEST(IFNULL(revenue_generated, 0) - ?, 0) WHERE id = ?');
    if (!$stockStmt) {
        $itemsStmt->close();
        throw new RuntimeException('Unable to prepare the stock restoration.');
    }

    while ($item = $itemsResult->fetch_assoc()) {
        $productId = (int)($item['product_id'] ?? 0);
        $quantity = max(0, (int)($item['qty'] ?? 0));
        $lineTotal = max(0, (float)($item['line_total'] ?? 0));
        if ($productId < 1 || $quantity < 1) {
            continue;
        }
        $stockStmt->bind_param('iidi', $quantity, $quantity, $lineTotal, $productId);
        if (!$stockStmt->execute()) {
            $stockStmt->close();
            $itemsStmt->close();
            throw new RuntimeException('Unable to restore product stock. Please contact KINZ support.');
        }
    }
    $stockStmt->close();
    $itemsStmt->close();

    $customerUpdate = $conn->prepare('UPDATE customers SET total_spent = GREATEST(IFNULL(total_spent, 0) - ?, 0), total_orders = GREATEST(IFNULL(total_orders, 0) - 1, 0) WHERE email = ?');
    if (!$customerUpdate) {
        throw new RuntimeException('Unable to update customer order totals.');
    }
    $orderTotal = (float)$order['total_amount'];
    $customerUpdate->bind_param('ds', $orderTotal, $customerEmail);
    if (!$customerUpdate->execute()) {
        $customerUpdate->close();
        throw new RuntimeException('Unable to update customer order totals.');
    }
    $customerUpdate->close();

    $couponCode = trim((string)($order['coupon_code'] ?? ''));
    if ($couponCode !== '') {
        $couponUpdate = $conn->prepare('UPDATE coupons SET used_count = GREATEST(IFNULL(used_count, 0) - 1, 0) WHERE code = ?');
        if (!$couponUpdate) {
            throw new RuntimeException('Unable to prepare the coupon usage update.');
        }
        $couponUpdate->bind_param('s', $couponCode);
        if (!$couponUpdate->execute()) {
            $couponUpdate->close();
            throw new RuntimeException('Unable to release the coupon usage. Please contact KINZ support.');
        }
        $couponUpdate->close();
    }

    $newPaymentStatus = $isPaid ? 'Refunded' : (string)$order['payment_status'];
    $cancelStmt = $conn->prepare("UPDATE orders SET status = 'Cancelled', payment_status = ? WHERE id = ? AND customer_email = ? AND status IN ('Pending', 'Confirmed', 'Processing')");
    if (!$cancelStmt) {
        throw new RuntimeException('Unable to cancel this order. Please try again.');
    }
    $cancelStmt->bind_param('sis', $newPaymentStatus, $orderId, $customerEmail);
    if (!$cancelStmt->execute() || $cancelStmt->affected_rows !== 1) {
        $cancelStmt->close();
        throw new RuntimeException('The order status changed before cancellation. Refresh your order history and try again.');
    }
    $cancelStmt->close();

    $refundId = '';
    if ($isPaid) {
        $refund = kinz_razorpay_refund((string)$order['razorpay_payment_id'], (int)round($orderTotal * 100), ['order_code' => (string)$order['order_code']]);
        $refundId = (string)$refund['id'];
        $refundStmt = $conn->prepare('UPDATE orders SET razorpay_refund_id = ? WHERE id = ?');
        if ($refundStmt) {
            $refundStmt->bind_param('si', $refundId, $orderId);
            $refundStmt->execute();
            $refundStmt->close();
        }
    }

    $conn->commit();
    if ($isPaid) {
        kinz_cancel_redirect(true, 'Order #' . (string)$order['order_code'] . ' has been cancelled. Your refund of ₹' . number_format($orderTotal, 2) . ' has been started and will reach your original payment method in 5-7 working days.');
    }
    kinz_cancel_redirect(true, 'Order #' . (string)$order['order_code'] . ' has been cancelled successfully. Product stock has been restored.');
} catch (Throwable $error) {
    $conn->rollback();
    kinz_cancel_redirect(false, $error->getMessage());
}
