<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');

function kinz_account_reply(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function kinz_account_schema(mysqli $conn): void {
    $passwordColumn = $conn->query("SHOW COLUMNS FROM customers LIKE 'password'");
    if ($passwordColumn && $passwordColumn->num_rows === 0) {
        $conn->query('ALTER TABLE customers ADD COLUMN password VARCHAR(255) NULL AFTER email');
    }
    $dobColumn = $conn->query("SHOW COLUMNS FROM customers LIKE 'date_of_birth'");
    if ($dobColumn && $dobColumn->num_rows === 0) {
        $conn->query('ALTER TABLE customers ADD COLUMN date_of_birth DATE NULL AFTER phone');
    }

    $conn->query("CREATE TABLE IF NOT EXISTS customer_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        address_tag VARCHAR(40) NOT NULL DEFAULT 'Home',
        recipient_name VARCHAR(100) NOT NULL,
        address_line TEXT NOT NULL,
        city VARCHAR(100) NOT NULL,
        pincode VARCHAR(12) NOT NULL,
        phone VARCHAR(30) NOT NULL,
        is_default TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_customer_addresses_customer (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS customer_wishlist (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        product_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_customer_wishlist (customer_id, product_id),
        INDEX idx_customer_wishlist_customer (customer_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS customer_notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT NOT NULL,
        order_id INT NULL,
        notification_type VARCHAR(40) NOT NULL DEFAULT 'general',
        title VARCHAR(180) NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_customer_notifications_customer (customer_id),
        INDEX idx_customer_notifications_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

function kinz_account_csrf_check(): void {
    $provided = (string)($_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '');
    $stored = (string)($_SESSION['kinz_csrf_token'] ?? '');
    if ($provided === '' || $stored === '' || !hash_equals($stored, $provided)) {
        kinz_account_reply(419, ['success' => false, 'message' => 'Your session token expired. Refresh the page and try again.']);
    }
}

function kinz_account_image(string $path): string {
    $path = trim($path);
    if ($path === '') return 'assets/images/kinz_logo.png';
    if (preg_match('~^https?://~i', $path)) return $path;
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (strpos($path, 'uploads/') === 0) return '../admin/' . $path;
    return $path;
}

$action = (string)($_GET['action'] ?? $_POST['action'] ?? 'bootstrap');
$customerId = (int)($_SESSION['customer_id'] ?? 0);
$customerEmail = (string)($_SESSION['customer_email'] ?? '');

// The shared header badge only needs the authenticated customer ID.
// Do not require the email session value for this lightweight request.
if ($action === 'search_products') {
    $term = trim((string)($_GET['q'] ?? ''));
    if (mb_strlen($term) < 2) kinz_account_reply(200, ['success' => true, 'results' => []]);
    $like = '%' . addcslashes($term, '%_\\') . '%';
    $stmt = $conn->prepare("SELECT id, title, category, image, base_price, making_charges, discount_pct
        FROM products
        WHERE status = 'Active' AND (title LIKE ? OR category LIKE ? OR metal_type LIKE ? OR occasion LIKE ? OR description LIKE ?)
        ORDER BY (title LIKE ?) DESC, created_at DESC LIMIT 6");
    if (!$stmt) kinz_account_reply(500, ['success' => false, 'results' => []]);
    $stmt->bind_param('ssssss', $like, $like, $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
    $out = [];
    while ($row = $res->fetch_assoc()) {
        $price = ((float)$row['base_price'] + (float)$row['making_charges']) * (1 - ((int)$row['discount_pct']) / 100);
        $out[] = [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'category' => (string)$row['category'],
            'image' => kinz_account_image((string)$row['image']),
            'price' => round($price, 0)
        ];
    }
    $stmt->close();
    kinz_account_reply(200, ['success' => true, 'results' => $out]);
}

if (in_array($action, ['wishlist_count', 'notification_count', 'header_state'], true)) {
    if ($customerId <= 0) {
        kinz_account_reply(200, ['success' => true, 'count' => 0]);
    }
} elseif ($customerId <= 0 || $customerEmail === '') {
    kinz_account_reply(401, ['success' => false, 'message' => 'Please sign in to access your account.']);
}

kinz_account_schema($conn);
if (empty($_SESSION['kinz_csrf_token'])) {
    $_SESSION['kinz_csrf_token'] = bin2hex(random_bytes(32));
}

// Lightweight endpoint used by the shared customer header badge on every page.
if ($action === 'header_state') {
    $notificationCount = 0;
    $wishlistCount = 0;
    $wishlistIds = [];

    $notificationCountStmt = $conn->prepare('SELECT COUNT(*) AS total FROM customer_notifications WHERE customer_id = ? AND is_read = 0');
    if ($notificationCountStmt) {
        $notificationCountStmt->bind_param('i', $customerId);
        $notificationCountStmt->execute();
        $notificationCount = (int)($notificationCountStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $notificationCountStmt->close();
    }

    $wishlistCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM customer_wishlist w INNER JOIN products p ON p.id = w.product_id WHERE w.customer_id = ? AND p.status = 'Active'");
    if ($wishlistCountStmt) {
        $wishlistCountStmt->bind_param('i', $customerId);
        $wishlistCountStmt->execute();
        $wishlistCount = (int)($wishlistCountStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $wishlistCountStmt->close();
    }

    $wishlistIdsStmt = $conn->prepare("SELECT w.product_id FROM customer_wishlist w INNER JOIN products p ON p.id = w.product_id WHERE w.customer_id = ? AND p.status = 'Active'");
    if ($wishlistIdsStmt) {
        $wishlistIdsStmt->bind_param('i', $customerId);
        $wishlistIdsStmt->execute();
        $wishlistIdsResult = $wishlistIdsStmt->get_result();
        while ($wishlistIdsResult && ($wishlistIdRow = $wishlistIdsResult->fetch_assoc())) {
            $wishlistIds[] = (int)$wishlistIdRow['product_id'];
        }
        $wishlistIdsStmt->close();
    }

    kinz_account_reply(200, [
        'success' => true,
        'notification_count' => $notificationCount,
        'wishlist_count' => $wishlistCount,
        'wishlist_ids' => $wishlistIds,
        'csrf_token' => $_SESSION['kinz_csrf_token']
    ]);
}

if ($action === 'wishlist_count') {
    $countStmt = $conn->prepare("SELECT COUNT(*) AS total FROM customer_wishlist w INNER JOIN products p ON p.id = w.product_id WHERE w.customer_id = ? AND p.status = 'Active'");
    if (!$countStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to load wishlist count.']);
    $countStmt->bind_param('i', $customerId);
    $countStmt->execute();
    $countRow = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();
    kinz_account_reply(200, ['success' => true, 'count' => (int)($countRow['total'] ?? 0)]);
}

if ($action === 'notification_count') {
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM customer_notifications WHERE customer_id = ? AND is_read = 0');
    if (!$countStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to load notification count.']);
    $countStmt->bind_param('i', $customerId);
    $countStmt->execute();
    $countRow = $countStmt->get_result()->fetch_assoc();
    $countStmt->close();
    kinz_account_reply(200, ['success' => true, 'count' => (int)($countRow['total'] ?? 0)]);
}

if ($action === 'homepage_coupon') {
    $couponStmt = $conn->prepare("SELECT code, discount_type, value, min_order, applicable_to, expires_at
        FROM coupons
        WHERE LOWER(TRIM(status)) = 'active'
          AND (expires_at IS NULL OR expires_at >= NOW())
          AND (usage_limit IS NULL OR COALESCE(used_count, 0) < usage_limit)
        ORDER BY id DESC
        LIMIT 1");
    if (!$couponStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to load the current offer.']);
    if (!$couponStmt->execute()) {
        $couponStmt->close();
        kinz_account_reply(500, ['success' => false, 'message' => 'Unable to load the current offer.']);
    }
    $coupon = $couponStmt->get_result()->fetch_assoc();
    $couponStmt->close();
    if (!$coupon) kinz_account_reply(200, ['success' => true, 'coupon' => null]);

    $value = (float)$coupon['value'];
    $isPercentage = strcasecmp((string)$coupon['discount_type'], 'Percentage') === 0;
    $discountLabel = $isPercentage
        ? rtrim(rtrim(number_format($value, 2), '0'), '.') . '% OFF'
        : '₹ ' . number_format($value, 0) . ' OFF';
    kinz_account_reply(200, ['success' => true, 'coupon' => [
        'code' => (string)$coupon['code'],
        'discount_label' => $discountLabel,
        'discount_type' => (string)$coupon['discount_type'],
        'value' => $value,
        'min_order' => (float)($coupon['min_order'] ?? 0),
        'applicable_to' => (string)($coupon['applicable_to'] ?? 'All products'),
        'expires_at' => $coupon['expires_at'] ?? null
    ]]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kinz_account_csrf_check();
}

if ($action === 'bootstrap') {
    $profileStmt = $conn->prepare('SELECT id, name, email, phone, date_of_birth FROM customers WHERE id = ? LIMIT 1');
    if (!$profileStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to load your profile.']);
    $profileStmt->bind_param('i', $customerId);
    $profileStmt->execute();
    $profile = $profileStmt->get_result()->fetch_assoc();
    $profileStmt->close();
    if (!$profile) kinz_account_reply(404, ['success' => false, 'message' => 'Customer account was not found.']);

    $addressStmt = $conn->prepare('SELECT id, address_tag, recipient_name, address_line, city, pincode, phone, is_default FROM customer_addresses WHERE customer_id = ? ORDER BY is_default DESC, id DESC');
    $addresses = [];
    if ($addressStmt) {
        $addressStmt->bind_param('i', $customerId);
        $addressStmt->execute();
        $res = $addressStmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) $addresses[] = $row;
        $addressStmt->close();
    }

    $ordersStmt = $conn->prepare('SELECT id, order_code, order_date, items_count, total_amount, status, payment_method, tracking_number, shipping_address FROM orders WHERE customer_email = ? ORDER BY order_date DESC, id DESC LIMIT 30');
    $orders = [];
    if ($ordersStmt) {
        $ordersStmt->bind_param('s', $customerEmail);
        $ordersStmt->execute();
        $res = $ordersStmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) $orders[] = $row;
        $ordersStmt->close();
    }

    // Notifications are created when the admin changes an order. Do not recreate deleted rows here.

    $notificationStmt = $conn->prepare('SELECT id, order_id, notification_type, title, message, is_read, created_at FROM customer_notifications WHERE customer_id = ? ORDER BY created_at DESC, id DESC LIMIT 40');
    $notifications = [];
    if ($notificationStmt) {
        $notificationStmt->bind_param('i', $customerId);
        $notificationStmt->execute();
        $res = $notificationStmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) $notifications[] = $row;
        $notificationStmt->close();
    }

    $wishlistStmt = $conn->prepare('SELECT p.id AS product_id, p.title, p.category, p.image, p.base_price, p.making_charges, p.discount_pct, p.stock, COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id AND r.status = \'Approved\'), 0) AS average_rating, (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = \'Approved\') AS review_count FROM customer_wishlist w INNER JOIN products p ON p.id = w.product_id WHERE w.customer_id = ? AND p.status = \'Active\' ORDER BY w.created_at DESC');
    $wishlist = [];
    if ($wishlistStmt) {
        $wishlistStmt->bind_param('i', $customerId);
        $wishlistStmt->execute();
        $res = $wishlistStmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $gross = (float)$row['base_price'] + (float)$row['making_charges'];
            $discount = max(0, min(100, (float)$row['discount_pct']));
            $row['price'] = round($gross * (1 - $discount / 100), 2);
            $row['original_price'] = round($gross, 2);
            $row['image_url'] = kinz_account_image((string)$row['image']);
            $wishlist[] = $row;
        }
        $wishlistStmt->close();
    }

    $_SESSION['customer_name'] = (string)$profile['name'];
    kinz_account_reply(200, [
        'success' => true,
        'csrf_token' => $_SESSION['kinz_csrf_token'],
        'profile' => $profile,
        'addresses' => $addresses,
        'orders' => $orders,
        'notifications' => $notifications,
        'wishlist' => $wishlist
    ]);
}

if ($action === 'update_profile') {
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $dob = trim((string)($_POST['date_of_birth'] ?? ''));
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '') {
        kinz_account_reply(422, ['success' => false, 'message' => 'Enter a valid name, email address and phone number.']);
    }
    if ($dob !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
        kinz_account_reply(422, ['success' => false, 'message' => 'Enter a valid date of birth.']);
    }
    $duplicate = $conn->prepare('SELECT id FROM customers WHERE email = ? AND id <> ? LIMIT 1');
    if (!$duplicate) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to validate the email address.']);
    $duplicate->bind_param('si', $email, $customerId);
    $duplicate->execute();
    $exists = $duplicate->get_result()->num_rows > 0;
    $duplicate->close();
    if ($exists) kinz_account_reply(409, ['success' => false, 'message' => 'This email address is already used by another account.']);

    $oldEmailStmt = $conn->prepare('SELECT email FROM customers WHERE id = ? LIMIT 1');
    if (!$oldEmailStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to read your current account details.']);
    $oldEmailStmt->bind_param('i', $customerId);
    $oldEmailStmt->execute();
    $oldEmail = (string)($oldEmailStmt->get_result()->fetch_assoc()['email'] ?? '');
    $oldEmailStmt->close();

    $update = $conn->prepare('UPDATE customers SET name = ?, email = ?, phone = ?, date_of_birth = NULLIF(?, \'\') WHERE id = ?');
    if (!$update) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to prepare the profile update.']);
    $update->bind_param('ssssi', $name, $email, $phone, $dob, $customerId);
    if (!$update->execute()) {
        $update->close();
        kinz_account_reply(500, ['success' => false, 'message' => 'Your profile could not be updated.']);
    }
    $update->close();
    if ($oldEmail !== '') {
        $ordersUpdate = $conn->prepare('UPDATE orders SET customer_email = ?, customer_name = ?, customer_phone = ? WHERE customer_email = ?');
        if ($ordersUpdate) {
            $ordersUpdate->bind_param('ssss', $email, $name, $phone, $oldEmail);
            $ordersUpdate->execute();
            $ordersUpdate->close();
        }
    }
    $_SESSION['customer_name'] = $name;
    $_SESSION['customer_email'] = $email;
    kinz_account_reply(200, ['success' => true, 'message' => 'Profile updated successfully.', 'profile' => ['name' => $name, 'email' => $email, 'phone' => $phone, 'date_of_birth' => $dob]]);
}

if ($action === 'save_address') {
    $addressId = (int)($_POST['id'] ?? 0);
    $tag = trim((string)($_POST['address_tag'] ?? 'Home'));
    $recipient = trim((string)($_POST['recipient_name'] ?? ''));
    $line = trim((string)($_POST['address_line'] ?? ''));
    $city = trim((string)($_POST['city'] ?? ''));
    $pincode = trim((string)($_POST['pincode'] ?? ''));
    $phone = trim((string)($_POST['phone'] ?? ''));
    $makeDefault = (int)($_POST['is_default'] ?? 0) === 1;
    if (!in_array($tag, ['Home', 'Work', 'Other'], true)) $tag = 'Other';
    if ($recipient === '' || $line === '' || $city === '' || !preg_match('/^[A-Za-z0-9 -]{4,12}$/', $pincode) || $phone === '') {
        kinz_account_reply(422, ['success' => false, 'message' => 'Complete all address fields and enter a valid PIN code.']);
    }
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM customer_addresses WHERE customer_id = ?');
    $count = 0;
    if ($countStmt) {
        $countStmt->bind_param('i', $customerId);
        $countStmt->execute();
        $count = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
        $countStmt->close();
    }
    if ($addressId === 0 && $count >= 10) kinz_account_reply(422, ['success' => false, 'message' => 'You can save up to 10 addresses.']);
    $existingDefault = false;
    if ($addressId > 0) {
        $existingStmt = $conn->prepare('SELECT is_default FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1');
        if (!$existingStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to verify the address.']);
        $existingStmt->bind_param('ii', $addressId, $customerId);
        $existingStmt->execute();
        $existingAddress = $existingStmt->get_result()->fetch_assoc();
        $existingStmt->close();
        if (!$existingAddress) kinz_account_reply(404, ['success' => false, 'message' => 'Address not found.']);
        $existingDefault = (int)$existingAddress['is_default'] === 1;
    }
    $defaultValue = ($makeDefault || $existingDefault || ($addressId === 0 && $count === 0)) ? 1 : 0;
    $conn->begin_transaction();
    if ($defaultValue === 1) {
        $reset = $conn->prepare('UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?');
        if ($reset) { $reset->bind_param('i', $customerId); $reset->execute(); $reset->close(); }
    }
    if ($addressId > 0) {
        $save = $conn->prepare('UPDATE customer_addresses SET address_tag = ?, recipient_name = ?, address_line = ?, city = ?, pincode = ?, phone = ?, is_default = ? WHERE id = ? AND customer_id = ?');
        if (!$save) { $conn->rollback(); kinz_account_reply(500, ['success' => false, 'message' => 'Unable to prepare the address update.']); }
        $save->bind_param('ssssssiii', $tag, $recipient, $line, $city, $pincode, $phone, $defaultValue, $addressId, $customerId);
    } else {
        $save = $conn->prepare('INSERT INTO customer_addresses (customer_id, address_tag, recipient_name, address_line, city, pincode, phone, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$save) { $conn->rollback(); kinz_account_reply(500, ['success' => false, 'message' => 'Unable to prepare the new address.']); }
        $defaultValue = ($makeDefault || $count === 0) ? 1 : 0;
        $save->bind_param('issssssi', $customerId, $tag, $recipient, $line, $city, $pincode, $phone, $defaultValue);
    }
    $ok = $save->execute();
    $affected = $save->affected_rows;
    $save->close();
    if (!$ok) {
        $conn->rollback();
        kinz_account_reply(404, ['success' => false, 'message' => 'The address could not be saved. Refresh and try again.']);
    }
    $conn->commit();
    kinz_account_reply(200, ['success' => true, 'message' => 'Address saved successfully.']);
}

if ($action === 'delete_address') {
    $addressId = (int)($_POST['id'] ?? 0);
    $delete = $conn->prepare('DELETE FROM customer_addresses WHERE id = ? AND customer_id = ?');
    if (!$delete) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to prepare address deletion.']);
    $delete->bind_param('ii', $addressId, $customerId);
    $delete->execute();
    $deleted = $delete->affected_rows;
    $delete->close();
    if ($deleted < 1) kinz_account_reply(404, ['success' => false, 'message' => 'Address not found.']);
    $defaultCheck = $conn->prepare('SELECT id FROM customer_addresses WHERE customer_id = ? ORDER BY id ASC LIMIT 1');
    if ($defaultCheck) {
        $defaultCheck->bind_param('i', $customerId);
        $defaultCheck->execute();
        $first = $defaultCheck->get_result()->fetch_assoc();
        $defaultCheck->close();
        if ($first) {
            $make = $conn->prepare('UPDATE customer_addresses SET is_default = 1 WHERE id = ? AND customer_id = ?');
            if ($make) { $firstId = (int)$first['id']; $make->bind_param('ii', $firstId, $customerId); $make->execute(); $make->close(); }
        }
    }
    kinz_account_reply(200, ['success' => true, 'message' => 'Address deleted successfully.']);
}

if ($action === 'set_default_address') {
    $addressId = (int)($_POST['id'] ?? 0);
    $check = $conn->prepare('SELECT id FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1');
    if (!$check) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to verify the address.']);
    $check->bind_param('ii', $addressId, $customerId);
    $check->execute();
    $found = $check->get_result()->num_rows > 0;
    $check->close();
    if (!$found) kinz_account_reply(404, ['success' => false, 'message' => 'Address not found.']);
    $conn->begin_transaction();
    $reset = $conn->prepare('UPDATE customer_addresses SET is_default = 0 WHERE customer_id = ?');
    $set = $conn->prepare('UPDATE customer_addresses SET is_default = 1 WHERE id = ? AND customer_id = ?');
    if (!$reset || !$set) { $conn->rollback(); kinz_account_reply(500, ['success' => false, 'message' => 'Unable to update the default address.']); }
    $reset->bind_param('i', $customerId); $reset->execute(); $reset->close();
    $set->bind_param('ii', $addressId, $customerId); $ok = $set->execute(); $set->close();
    if (!$ok) { $conn->rollback(); kinz_account_reply(500, ['success' => false, 'message' => 'Unable to update the default address.']); }
    $conn->commit();
    kinz_account_reply(200, ['success' => true, 'message' => 'Default address updated.']);
}

if ($action === 'change_password') {
    $current = (string)($_POST['current_password'] ?? '');
    $new = (string)($_POST['new_password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');
    if (strlen($new) < 6) kinz_account_reply(422, ['success' => false, 'message' => 'Your new password must contain at least 6 characters.']);
    if ($new !== $confirm) kinz_account_reply(422, ['success' => false, 'message' => 'New password and confirmation do not match.']);
    $currentStmt = $conn->prepare('SELECT password FROM customers WHERE id = ? LIMIT 1');
    if (!$currentStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to verify your current password.']);
    $currentStmt->bind_param('i', $customerId);
    $currentStmt->execute();
    $record = $currentStmt->get_result()->fetch_assoc();
    $currentStmt->close();
    if (!$record || (string)$record['password'] !== $current) kinz_account_reply(422, ['success' => false, 'message' => 'Your current password is incorrect.']);
    $update = $conn->prepare('UPDATE customers SET password = ? WHERE id = ?');
    if (!$update) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to prepare the password update.']);
    $update->bind_param('si', $new, $customerId);
    $ok = $update->execute();
    $update->close();
    if (!$ok) kinz_account_reply(500, ['success' => false, 'message' => 'Password could not be updated.']);
    kinz_account_reply(200, ['success' => true, 'message' => 'Password changed successfully.']);
}

if ($action === 'wishlist_status') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $stmt = $conn->prepare('SELECT id FROM customer_wishlist WHERE customer_id = ? AND product_id = ? LIMIT 1');
    if (!$stmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to check your wishlist.']);
    $stmt->bind_param('ii', $customerId, $productId);
    $stmt->execute();
    $exists = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    kinz_account_reply(200, ['success' => true, 'saved' => $exists]);
}

if ($action === 'wishlist_toggle') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $productStmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND status = 'Active' LIMIT 1");
    if (!$productStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to verify this product.']);
    $productStmt->bind_param('i', $productId);
    $productStmt->execute();
    $productExists = $productStmt->get_result()->num_rows > 0;
    $productStmt->close();
    if (!$productExists) kinz_account_reply(404, ['success' => false, 'message' => 'This product is no longer available.']);
    $existsStmt = $conn->prepare('SELECT id FROM customer_wishlist WHERE customer_id = ? AND product_id = ? LIMIT 1');
    if (!$existsStmt) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to update your wishlist.']);
    $existsStmt->bind_param('ii', $customerId, $productId);
    $existsStmt->execute();
    $existing = $existsStmt->get_result()->fetch_assoc();
    $existsStmt->close();
    if ($existing) {
        $remove = $conn->prepare('DELETE FROM customer_wishlist WHERE id = ? AND customer_id = ?');
        if (!$remove) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to remove this product from your wishlist.']);
        $wishlistId = (int)$existing['id'];
        $remove->bind_param('ii', $wishlistId, $customerId);
        $ok = $remove->execute();
        $remove->close();
        kinz_account_reply($ok ? 200 : 500, ['success' => (bool)$ok, 'saved' => false, 'message' => $ok ? 'Removed from your wishlist.' : 'Unable to update your wishlist.']);
    }
    $insert = $conn->prepare('INSERT INTO customer_wishlist (customer_id, product_id) VALUES (?, ?)');
    if (!$insert) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to add this product to your wishlist.']);
    $insert->bind_param('ii', $customerId, $productId);
    $ok = $insert->execute();
    $insert->close();
    kinz_account_reply($ok ? 200 : 500, ['success' => (bool)$ok, 'saved' => (bool)$ok, 'message' => $ok ? 'Added to your wishlist.' : 'Unable to add this product to your wishlist.']);
}

if ($action === 'remove_wishlist') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $remove = $conn->prepare('DELETE FROM customer_wishlist WHERE customer_id = ? AND product_id = ?');
    if (!$remove) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to remove this product.']);
    $remove->bind_param('ii', $customerId, $productId);
    $remove->execute();
    $remove->close();
    kinz_account_reply(200, ['success' => true, 'message' => 'Product removed from wishlist.']);
}

if ($action === 'delete_notification') {
    $notificationId = (int)($_POST['id'] ?? 0);
    if ($notificationId < 1) {
        kinz_account_reply(422, ['success' => false, 'message' => 'Select a valid notification.']);
    }
    $delete = $conn->prepare('DELETE FROM customer_notifications WHERE id = ? AND customer_id = ?');
    if (!$delete) {
        kinz_account_reply(500, ['success' => false, 'message' => 'Unable to prepare notification deletion.']);
    }
    $delete->bind_param('ii', $notificationId, $customerId);
    $ok = $delete->execute();
    $deleted = $delete->affected_rows;
    $delete->close();
    if (!$ok || $deleted < 1) {
        kinz_account_reply(404, ['success' => false, 'message' => 'Notification was not found or has already been deleted.']);
    }
    $countStmt = $conn->prepare('SELECT COUNT(*) AS total FROM customer_notifications WHERE customer_id = ? AND is_read = 0');
    $unreadCount = 0;
    if ($countStmt) {
        $countStmt->bind_param('i', $customerId);
        $countStmt->execute();
        $countRow = $countStmt->get_result()->fetch_assoc();
        $unreadCount = (int)($countRow['total'] ?? 0);
        $countStmt->close();
    }
    kinz_account_reply(200, ['success' => true, 'deleted' => true, 'unread_count' => $unreadCount, 'message' => 'Notification deleted.']);
}

if ($action === 'mark_all_notifications_read') {
    $update = $conn->prepare('UPDATE customer_notifications SET is_read = 1 WHERE customer_id = ? AND is_read = 0');
    if (!$update) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to update notifications.']);
    $update->bind_param('i', $customerId);
    $ok = $update->execute();
    $affected = $update->affected_rows;
    $update->close();
    kinz_account_reply($ok ? 200 : 500, ['success' => (bool)$ok, 'updated' => (int)$affected, 'message' => $ok ? 'Notifications marked as read.' : 'Unable to update notifications.']);
}

if ($action === 'mark_notification_read') {
    $notificationId = (int)($_POST['id'] ?? 0);
    $update = $conn->prepare('UPDATE customer_notifications SET is_read = 1 WHERE id = ? AND customer_id = ?');
    if (!$update) kinz_account_reply(500, ['success' => false, 'message' => 'Unable to update this notification.']);
    $update->bind_param('ii', $notificationId, $customerId);
    $ok = $update->execute();
    $update->close();
    kinz_account_reply($ok ? 200 : 500, ['success' => (bool)$ok, 'message' => $ok ? 'Notification marked as read.' : 'Unable to update this notification.']);
}

kinz_account_reply(400, ['success' => false, 'message' => 'Unknown account action.']);
