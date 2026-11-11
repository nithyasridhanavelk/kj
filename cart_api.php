<?php
require_once __DIR__ . '/includes/cart_helpers.php';
require_once __DIR__ . '/includes/razorpay_helper.php';
require_once __DIR__ . '/includes/order_expiry.php';

$action = (string)($_POST['action'] ?? $_GET['action'] ?? 'get_cart');
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
if ($isPost) {
    $token = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals((string)$_SESSION['kinz_csrf_token'], $token)) {
        kinz_cart_json_response(['success' => false, 'message' => 'Your session token expired. Refresh the page and try again.'], 419);
    }
}

if ($action === 'get_cart') {
    kinz_cart_json_response(['success' => true] + kinz_cart_payload($conn));
}

if ($action === 'suggestions') {
    $inCart = array_map('intval', array_keys(kinz_cart_get_quantities($conn)));
    $suggestions = [];
    $res = $conn->query("SELECT id, title, base_price, making_charges, discount_pct, image FROM products WHERE status = 'Active' AND stock > 0 ORDER BY sales_count DESC, id DESC LIMIT 12");
    while ($res && ($row = $res->fetch_assoc())) {
        if (in_array((int)$row['id'], $inCart, true)) continue;
        $gross = (float)$row['base_price'] + (float)$row['making_charges'];
        $discount = max(0, min(100, (float)$row['discount_pct']));
        $suggestions[] = [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'price' => round($gross * (1 - $discount / 100), 2),
            'image_url' => kinz_image_url($row['image'] ?? '')
        ];
        if (count($suggestions) >= 3) break;
    }
    kinz_cart_json_response(['success' => true, 'suggestions' => $suggestions]);
}

if ($action === 'add' || $action === 'update') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
    if ($productId < 1 || $quantity < 1 || $quantity > 99) {
        kinz_cart_json_response(['success' => false, 'message' => 'Please select a valid quantity.'], 422);
    }
    $stmt = $conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 'Active' LIMIT 1");
    $stmt->bind_param('i', $productId);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$product) kinz_cart_json_response(['success' => false, 'message' => 'This product is no longer available.'], 404);
    $stock = (int)$product['stock'];
    if ($stock < 1) kinz_cart_json_response(['success' => false, 'message' => 'This product is currently out of stock.'], 409);

    $currentCart = kinz_cart_get_quantities($conn);
    $current = (int)($currentCart[$productId] ?? 0);
    $newQuantity = $action === 'add' ? $current + $quantity : $quantity;
    if ($newQuantity > $stock) {
        kinz_cart_json_response(['success' => false, 'message' => 'Only ' . $stock . ' item(s) are currently available.'], 409);
    }
    if (!kinz_cart_set_quantity($conn, $productId, $newQuantity)) {
        kinz_cart_json_response(['success' => false, 'message' => 'Unable to save your shopping bag. Please try again.'], 500);
    }
    kinz_cart_json_response(['success' => true, 'message' => 'Shopping bag updated.'] + kinz_cart_payload($conn));
}

if ($action === 'remove') {
    kinz_cart_get_quantities($conn);
    $productId = (int)($_POST['product_id'] ?? 0);
    if ($productId > 0 && !kinz_cart_remove_item($conn, $productId)) {
        kinz_cart_json_response(['success' => false, 'message' => 'Unable to remove this item. Please try again.'], 500);
    }
    kinz_cart_json_response(['success' => true, 'message' => 'Item removed from your bag.'] + kinz_cart_payload($conn));
}

if ($action === 'apply_coupon') {
    $code = strtoupper(trim((string)($_POST['code'] ?? '')));
    if ($code === '') kinz_cart_json_response(['success' => false, 'message' => 'Enter a discount code.'], 422);
    $previous = $_SESSION['kinz_coupon_code'] ?? '';
    $_SESSION['kinz_coupon_code'] = $code;
    $payload = kinz_cart_payload($conn);
    if (empty($payload['summary']['coupon_code'])) {
        if ($previous !== '') $_SESSION['kinz_coupon_code'] = $previous;
        else unset($_SESSION['kinz_coupon_code']);
        kinz_cart_json_response(['success' => false, 'message' => 'This coupon is invalid, expired, fully used or does not meet the minimum order amount.'], 422);
    }
    kinz_cart_json_response(['success' => true, 'message' => 'Coupon applied successfully.'] + $payload);
}

if ($action === 'clear_coupon') {
    unset($_SESSION['kinz_coupon_code']);
    kinz_cart_json_response(['success' => true, 'message' => 'Coupon removed.'] + kinz_cart_payload($conn));
}

if ($action === 'checkout_data') {
    $customerId = kinz_cart_customer_id();
    if ($customerId < 1) kinz_cart_json_response(['success' => false, 'message' => 'Please sign in to continue to checkout.'], 401);
    $customerStmt = $conn->prepare('SELECT id, name, email, phone, primary_address FROM customers WHERE id = ? LIMIT 1');
    $customerStmt->bind_param('i', $customerId);
    $customerStmt->execute();
    $customer = $customerStmt->get_result()->fetch_assoc();
    $customerStmt->close();
    if (!$customer) kinz_cart_json_response(['success' => false, 'message' => 'Customer account not found.'], 401);
    $addressStmt = $conn->prepare('SELECT id, address_tag, recipient_name, address_line, city, pincode, phone, is_default FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC');
    $addressStmt->bind_param('i', $customerId);
    $addressStmt->execute();
    $addressResult = $addressStmt->get_result();
    $addresses = [];
    while ($address = $addressResult->fetch_assoc()) $addresses[] = $address;
    $addressStmt->close();
    kinz_cart_json_response(['success' => true, 'customer' => $customer, 'addresses' => $addresses] + kinz_cart_payload($conn));
}

if ($action === 'save_address') {
    $customerId = kinz_cart_customer_id();
    if ($customerId < 1) kinz_cart_json_response(['success' => false, 'message' => 'Please sign in to save a delivery address.'], 401);
    $tag = trim((string)($_POST['tag'] ?? 'Home'));
    $name = trim((string)($_POST['name'] ?? ''));
    $line = trim((string)($_POST['line'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $pincode = trim((string)($_POST['pincode'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    if ($name === '' || $line === '' || $city === '' || !preg_match('/^[0-9]{6}$/', $pincode) || $phone === '') {
        kinz_cart_json_response(['success' => false, 'message' => 'Enter all address details and a valid 6-digit pincode.'], 422);
    }
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM customer_addresses WHERE customer_id = ?');
    $countStmt->bind_param('i', $customerId);
    $countStmt->execute();
    $addressCount = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
    $countStmt->close();
    $isDefault = $addressCount === 0 ? 1 : 0;
    $insert = $conn->prepare('INSERT INTO customer_addresses (customer_id, address_tag, recipient_name, address_line, city, pincode, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $insert->bind_param('issssssi', $customerId, $tag, $name, $line, $city, $pincode, $phone, $isDefault);
    if (!$insert->execute()) kinz_cart_json_response(['success' => false, 'message' => 'Unable to save this address. Please try again.'], 500);
    $addressId = (int)$insert->insert_id;
    $insert->close();
    kinz_cart_json_response(['success' => true, 'message' => 'Delivery address saved.', 'address_id' => $addressId]);
}

if ($action === 'place_order') {
    $customerId = kinz_cart_customer_id();
    if ($customerId < 1) kinz_cart_json_response(['success' => false, 'message' => 'Please sign in before placing an order.'], 401);
    $addressId = (int)($_POST['address_id'] ?? 0);
    $shippingMethod = (string)($_POST['shipping_method'] ?? 'standard');
    $paymentMethod = (string)($_POST['payment_method'] ?? '');
    if (!in_array($shippingMethod, ['standard', 'express'], true)) $shippingMethod = 'standard';
    if ($paymentMethod !== 'Razorpay') {
        kinz_cart_json_response(['success' => false, 'message' => 'Please select a payment method.'], 422);
    }
    $addressStmt = $conn->prepare('SELECT id, address_tag, recipient_name, address_line, city, pincode, phone FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1');
    $addressStmt->bind_param('ii', $addressId, $customerId);
    $addressStmt->execute();
    $address = $addressStmt->get_result()->fetch_assoc();
    $addressStmt->close();
    if (!$address) kinz_cart_json_response(['success' => false, 'message' => 'Choose or add a delivery address before continuing.'], 422);

    $customerStmt = $conn->prepare('SELECT id, name, email, phone FROM customers WHERE id = ? AND status = \'Active\' AND is_blocked = 0 LIMIT 1');
    $customerStmt->bind_param('i', $customerId);
    $customerStmt->execute();
    $customer = $customerStmt->get_result()->fetch_assoc();
    $customerStmt->close();
    if (!$customer) kinz_cart_json_response(['success' => false, 'message' => 'Your customer account is not active.'], 403);

    try {
        $conn->begin_transaction();
        $items = kinz_cart_load_items($conn, true);
        if (!$items) throw new RuntimeException('Your shopping bag is empty.');
        foreach ($items as $item) {
            if ((int)$item['quantity'] > (int)$item['stock']) throw new RuntimeException('Stock changed for ' . $item['title'] . '. Please update your bag.');
        }
        $subtotal = round(array_sum(array_column($items, 'line_total')), 2);
        $coupon = kinz_cart_coupon($conn, $items);
        if (!empty($_SESSION['kinz_coupon_code']) && !$coupon) {
            unset($_SESSION['kinz_coupon_code']);
        }
        $discount = $coupon ? (float)$coupon['discount'] : 0.0;
        $shipping = $shippingMethod === 'express' ? 150.00 : 0.00;
        $tax = round(max(0, $subtotal - $discount) * 0.05, 2);
        $total = round($subtotal - $discount + $tax + $shipping, 2);
        $itemCount = (int)array_sum(array_column($items, 'quantity'));
        $orderCode = 'KJ-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $shippingAddress = $address['recipient_name'] . ', ' . $address['address_line'] . ', ' . $address['city'] . ' - ' . $address['pincode'] . ', Phone: ' . $address['phone'];
        $paymentLabel = 'Razorpay';
        $itemsJson = [];
        foreach ($items as $item) {
            $itemsJson[] = [
                'title' => $item['title'],
                'sku' => $item['sku'],
                'quantity' => (int)$item['quantity'],
                'price' => (float)$item['line_total'],
                'unit_price' => (float)$item['unit_price'],
                'image' => $item['image_url']
            ];
        }
        $itemsJsonString = json_encode($itemsJson, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $couponCode = $coupon['code'] ?? '';
        $orderStmt = $conn->prepare("INSERT INTO orders (order_code, customer_name, customer_email, customer_phone, order_date, items_count, subtotal, discount_amount, tax_amount, shipping_amount, coupon_code, total_amount, status, payment_method, tracking_number, shipping_address, items_json) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, '', ?, ?)");
        $orderStmt->bind_param('ssssiddddsdsss', $orderCode, $customer['name'], $customer['email'], $address['phone'], $itemCount, $subtotal, $discount, $tax, $shipping, $couponCode, $total, $paymentLabel, $shippingAddress, $itemsJsonString);
        if (!$orderStmt->execute()) throw new RuntimeException('Your order could not be saved. Please try again.');
        $orderId = (int)$orderStmt->insert_id;
        $orderStmt->close();

        $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, product_title, unit_price, qty, line_total) VALUES (?, ?, ?, ?, ?, ?)');
        if (!$itemStmt) throw new RuntimeException('Unable to prepare order item insert: ' . $conn->error);
        $stockStmt = $conn->prepare("UPDATE products SET stock = stock - ?, sales_count = sales_count + ?, revenue_generated = revenue_generated + ? WHERE id = ? AND stock >= ? AND status = 'Active'");
        if (!$stockStmt) throw new RuntimeException('Unable to prepare product stock update: ' . $conn->error);
        foreach ($items as $item) {
            $productId = (int)$item['id'];
            $quantity = (int)$item['quantity'];
            $title = (string)$item['title'];
            $unitPrice = (float)$item['unit_price'];
            $lineTotal = (float)$item['line_total'];
            $itemStmt->bind_param('iisdid', $orderId, $productId, $title, $unitPrice, $quantity, $lineTotal);
            if (!$itemStmt->execute()) throw new RuntimeException('Unable to save order items: ' . $itemStmt->error);
            $stockStmt->bind_param('iidii', $quantity, $quantity, $lineTotal, $productId, $quantity);
            if (!$stockStmt->execute() || $stockStmt->affected_rows !== 1) throw new RuntimeException('Stock changed while placing your order. Please try again.');
        }
        $itemStmt->close();
        $stockStmt->close();

        $customerUpdate = $conn->prepare('UPDATE customers SET total_spent = total_spent + ?, total_orders = total_orders + 1, primary_address = ? WHERE id = ?');
        $customerUpdate->bind_param('dsi', $total, $shippingAddress, $customerId);
        if (!$customerUpdate->execute()) throw new RuntimeException('Unable to update customer order history.');
        $customerUpdate->close();

        if ($coupon) {
            $couponUpdate = $conn->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ? AND used_count < usage_limit');
            $couponUpdate->bind_param('i', $coupon['id']);
            if (!$couponUpdate->execute() || $couponUpdate->affected_rows !== 1) throw new RuntimeException('The coupon is no longer available. Please try again without it.');
            $couponUpdate->close();
        }

        if (!kinz_cart_clear($conn)) throw new RuntimeException('Your order was created, but the shopping bag could not be cleared. Please contact support before placing another order.');
        $conn->commit();
        unset($_SESSION['kinz_coupon_code']);
        $_SESSION['kinz_last_order_code'] = $orderCode;
        kinz_cart_json_response(['success' => true, 'message' => 'Your order has been recorded.', 'order_code' => $orderCode, 'redirect' => 'order-confirmation.php?order_code=' . rawurlencode($orderCode)]);
    } catch (Throwable $error) {
        $conn->rollback();
        kinz_cart_json_response(['success' => false, 'message' => $error->getMessage()], 422);
    }
}

if ($action === 'razorpay_order') {
    kinz_expire_unpaid_orders($conn);
    $customerId = kinz_cart_customer_id();
    if ($customerId < 1) kinz_cart_json_response(['success' => false, 'message' => 'Please sign in to continue.'], 401);
    $orderCode = trim((string)($_POST['order_code'] ?? ''));
    $customerEmail = (string)($_SESSION['customer_email'] ?? '');
    $orderStmt = $conn->prepare('SELECT id, order_code, customer_name, customer_email, customer_phone, total_amount, status, payment_status FROM orders WHERE order_code = ? AND customer_email = ? LIMIT 1');
    $orderStmt->bind_param('ss', $orderCode, $customerEmail);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();
    if (!$order) kinz_cart_json_response(['success' => false, 'message' => 'This order was not found in your account.'], 404);
    if (strcasecmp((string)$order['payment_status'], 'Paid') === 0) {
        kinz_cart_json_response(['success' => false, 'message' => 'This order has already been paid.'], 409);
    }
    if (strcasecmp((string)$order['status'], 'Pending') !== 0) {
        kinz_cart_json_response(['success' => false, 'message' => 'This order can no longer be paid.'], 409);
    }
    try {
        $amountPaise = (int)round((float)$order['total_amount'] * 100);
        $razorpayOrder = kinz_razorpay_create_order($amountPaise, (string)$order['order_code'], ['order_code' => (string)$order['order_code']]);
    } catch (Throwable $error) {
        kinz_cart_json_response(['success' => false, 'message' => $error->getMessage()], 502);
    }
    $razorpayOrderId = (string)$razorpayOrder['id'];
    $saveStmt = $conn->prepare('UPDATE orders SET razorpay_order_id = ? WHERE id = ?');
    $orderId = (int)$order['id'];
    $saveStmt->bind_param('si', $razorpayOrderId, $orderId);
    if (!$saveStmt->execute()) kinz_cart_json_response(['success' => false, 'message' => 'Unable to start the payment. Please try again.'], 500);
    $saveStmt->close();
    kinz_cart_json_response([
        'success' => true,
        'key_id' => RAZORPAY_KEY_ID,
        'razorpay_order_id' => $razorpayOrderId,
        'amount' => $amountPaise,
        'currency' => 'INR',
        'order_code' => (string)$order['order_code'],
        'name' => (string)$order['customer_name'],
        'email' => (string)$order['customer_email'],
        'contact' => (string)$order['customer_phone']
    ]);
}

if ($action === 'razorpay_verify') {
    $customerId = kinz_cart_customer_id();
    if ($customerId < 1) kinz_cart_json_response(['success' => false, 'message' => 'Please sign in to continue.'], 401);
    $orderCode = trim((string)($_POST['order_code'] ?? ''));
    $razorpayOrderId = trim((string)($_POST['razorpay_order_id'] ?? ''));
    $paymentId = trim((string)($_POST['razorpay_payment_id'] ?? ''));
    $signature = trim((string)($_POST['razorpay_signature'] ?? ''));
    if ($orderCode === '' || $razorpayOrderId === '' || $paymentId === '' || $signature === '') {
        kinz_cart_json_response(['success' => false, 'message' => 'Payment details are incomplete.'], 422);
    }
    $customerEmail = (string)($_SESSION['customer_email'] ?? '');
    $orderStmt = $conn->prepare('SELECT id, razorpay_order_id, payment_status, status, total_amount FROM orders WHERE order_code = ? AND customer_email = ? LIMIT 1');
    $orderStmt->bind_param('ss', $orderCode, $customerEmail);
    $orderStmt->execute();
    $order = $orderStmt->get_result()->fetch_assoc();
    $orderStmt->close();
    if (!$order || !hash_equals((string)$order['razorpay_order_id'], $razorpayOrderId)) {
        kinz_cart_json_response(['success' => false, 'message' => 'Payment details do not match this order.'], 422);
    }
    if (!kinz_razorpay_signature_valid($razorpayOrderId, $paymentId, $signature)) {
        kinz_cart_json_response(['success' => false, 'message' => 'Payment verification failed. If money was deducted, contact KINZ support with payment ID ' . $paymentId . '.'], 422);
    }
    $orderId = (int)$order['id'];
    $paidStmt = $conn->prepare("UPDATE orders SET payment_status = 'Paid', status = 'Confirmed', razorpay_payment_id = ?, paid_at = NOW() WHERE id = ? AND payment_status <> 'Paid' AND status = 'Pending'");
    $paidStmt->bind_param('si', $paymentId, $orderId);
    $paidStmt->execute();
    $updated = $paidStmt->affected_rows === 1;
    $paidStmt->close();
    if (!$updated && strcasecmp((string)$order['payment_status'], 'Paid') !== 0) {
        if (strcasecmp((string)$order['status'], 'Cancelled') === 0) {
            try {
                $refund = kinz_razorpay_refund($paymentId, (int)round((float)$order['total_amount'] * 100), ['order_code' => $orderCode]);
                $refundId = (string)$refund['id'];
                $refundSave = $conn->prepare("UPDATE orders SET payment_status = 'Refunded', razorpay_payment_id = ?, razorpay_refund_id = ? WHERE id = ?");
                $refundSave->bind_param('ssi', $paymentId, $refundId, $orderId);
                $refundSave->execute();
                $refundSave->close();
                kinz_cart_json_response(['success' => false, 'message' => 'This order expired before the payment finished, so your payment has been refunded. Please place the order again.'], 409);
            } catch (RuntimeException $error) {
                kinz_cart_json_response(['success' => false, 'message' => 'This order expired before the payment finished. Contact KINZ support with payment ID ' . $paymentId . ' for a refund.'], 409);
            }
        }
        kinz_cart_json_response(['success' => false, 'message' => 'Payment was received but the order could not be updated. Contact KINZ support with payment ID ' . $paymentId . '.'], 409);
    }
    kinz_cart_json_response(['success' => true, 'message' => 'Payment successful.', 'redirect' => 'order-confirmation.php?order_code=' . rawurlencode($orderCode)]);
}

kinz_cart_json_response(['success' => false, 'message' => 'Unknown cart action.'], 404);
