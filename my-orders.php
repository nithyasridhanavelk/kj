<?php
require_once __DIR__ . '/includes/customer_login_guard.php';
require_once __DIR__ . '/includes/order_expiry.php';
kinz_customer_ensure_review_schema();

if (empty($_SESSION['customer_id']) || empty($_SESSION['customer_email'])) {
    header('Location: login.php');
    exit;
}

$customerEmail = (string)$_SESSION['customer_email'];
$customerName = (string)($_SESSION['customer_name'] ?? 'Customer');
kinz_expire_unpaid_orders($conn);
if (empty($_SESSION['kinz_csrf_token'])) {
    $_SESSION['kinz_csrf_token'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['kinz_order_cancel_csrf'])) {
    $_SESSION['kinz_order_cancel_csrf'] = bin2hex(random_bytes(32));
}
if (empty($_SESSION['kinz_review_csrf'])) {
    $_SESSION['kinz_review_csrf'] = bin2hex(random_bytes(32));
}
$reviewNotice = $_SESSION['kinz_review_notice'] ?? null;
unset($_SESSION['kinz_review_notice']);
$cancelNotice = $_SESSION['kinz_order_cancel_notice'] ?? null;
unset($_SESSION['kinz_order_cancel_notice']);

$orders = [];
$stmt = $conn->prepare('SELECT id, order_code, order_date, items_count, subtotal, discount_amount, tax_amount, shipping_amount, coupon_code, total_amount, status, payment_method, payment_status, tracking_number, shipping_address, items_json FROM orders WHERE customer_email = ? ORDER BY order_date DESC, id DESC');
if ($stmt) {
    $stmt->bind_param('s', $customerEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($result && ($row = $result->fetch_assoc())) $orders[] = $row;
    $stmt->close();
}

function my_orders_escape($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function my_orders_currency($value): string {
    return '₹' . number_format((float)$value, 2, '.', ',');
}

function my_orders_status_class($status): string {
    $status = strtolower((string)$status);
    if ($status === 'delivered') return 'delivered';
    if ($status === 'cancelled') return 'cancelled';
    if ($status === 'shipped') return 'shipped';
    if ($status === 'out for delivery') return 'outfordelivery';
    if ($status === 'processing') return 'processing';
    if ($status === 'confirmed') return 'confirmed';
    return 'pending';
}

function my_orders_timeline_index($status): int {
    $status = strtolower(trim((string)$status));
    if ($status === 'confirmed') return 0;
    if ($status === 'processing') return 1;
    if ($status === 'shipped' || $status === 'shipping') return 2;
    if ($status === 'out for delivery' || $status === 'out_for_delivery') return 3;
    if ($status === 'delivered') return 4;
    return -1;
}

function my_orders_image($path): string {
    $path = trim((string)$path);
    if ($path === '') return 'assets/images/kinz_logo.png';
    if (preg_match('~^https?://~i', $path)) return $path;
    $path = ltrim(str_replace('\\', '/', $path), '/');
    if (strpos($path, 'uploads/') === 0) return '../admin/' . $path;
    return $path;
}

$itemsStmt = $conn->prepare("SELECT oi.id AS order_item_id, oi.product_id, oi.product_title, oi.qty, oi.unit_price, oi.line_total, COALESCE(p.image, '') AS product_image, r.id AS review_id, r.rating AS review_rating, r.review_text AS review_text, r.status AS review_status FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id LEFT JOIN reviews r ON r.order_item_id = oi.id WHERE oi.order_id = ? ORDER BY oi.id ASC");

$pendingReviewItems = [];
$pendingReviewStmt = $conn->prepare("SELECT oi.id AS order_item_id, oi.order_id, oi.product_id, oi.product_title, oi.qty, COALESCE(p.image, '') AS product_image, o.order_code
    FROM order_items oi
    INNER JOIN orders o ON o.id = oi.order_id
    LEFT JOIN products p ON p.id = oi.product_id
    LEFT JOIN reviews r ON r.order_item_id = oi.id
    WHERE LOWER(TRIM(o.customer_email)) = LOWER(TRIM(?))
      AND LOWER(TRIM(o.status)) = 'delivered'
      AND oi.product_id IS NOT NULL AND oi.product_id > 0
      AND r.id IS NULL
    ORDER BY o.order_date DESC, o.id DESC, oi.id ASC");
if ($pendingReviewStmt) {
    $pendingReviewStmt->bind_param('s', $customerEmail);
    if ($pendingReviewStmt->execute()) {
        $pendingResult = $pendingReviewStmt->get_result();
        while ($pendingResult && ($pendingRow = $pendingResult->fetch_assoc())) $pendingReviewItems[] = $pendingRow;
    }
    $pendingReviewStmt->close();
}
$reviewGateRequested = (string)($_GET['review_required'] ?? '') === '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders | KINZ - A Hidden Treasure</title>
  <link rel="icon" type="image/png" href="assets/images/kinz_logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <style>
    .my-orders-page{max-width:1120px;margin:54px auto 70px;padding:0 22px;}
    .my-orders-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:20px;flex-wrap:wrap;margin-bottom:26px;}
    .my-orders-heading-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
    .my-orders-heading h1{margin:0 0 7px;font-size:clamp(1.8rem,3vw,2.5rem);color:#123d32;}
    .my-orders-heading p{margin:0;color:#6b7772;font-size:.94rem;}
    .order-history-card{background:#fff;border:1px solid #e7e0d3;border-radius:16px;padding:22px;margin-bottom:18px;box-shadow:0 8px 26px rgba(27,48,39,.06);}
    .order-history-top{display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;padding-bottom:16px;border-bottom:1px solid #eee8dc;}
    .order-history-code{font-weight:800;color:#173d32;font-size:1rem;}
    .order-history-date{font-size:.82rem;color:#7c8580;margin-top:4px;}
    .order-status-badge{display:inline-flex;align-items:center;padding:7px 12px;border-radius:30px;font-size:.76rem;font-weight:800;letter-spacing:.2px;background:#f5eddc;color:#8c651b;}
    .order-status-badge.confirmed{background:#e8f0ff;color:#2958a5;}.order-status-badge.processing{background:#fff0dc;color:#9a5b0a;}.order-status-badge.shipped{background:#e8edff;color:#4554a6;}.order-status-badge.outfordelivery{background:#e5f4ed;color:#237448;}.order-status-badge.delivered{background:#e7f5eb;color:#237443;}.order-status-badge.cancelled{background:#fbe9e8;color:#a43832;}
    .kinz-tracking-wrap{margin:21px 0 9px;padding:8px 2px 5px;}
    .kinz-tracking-timeline{position:relative;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));align-items:start;}
    .kinz-tracking-track{position:absolute;z-index:0;left:10%;top:15px;width:80%;height:4px;background:#e3e9e4;border-radius:99px;overflow:hidden;}
    .kinz-tracking-fill{height:100%;background:#27965d;border-radius:99px;}
    .kinz-tracking-step{position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;gap:8px;min-width:0;text-align:center;}
    .kinz-tracking-dot{width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:50%;background:#fff;border:2px solid #d8e0da;color:#9aa59d;font-size:.76rem;box-shadow:0 0 0 4px #fff;}
    .kinz-tracking-step.is-complete .kinz-tracking-dot,.kinz-tracking-step.is-current .kinz-tracking-dot{background:#27965d;border-color:#27965d;color:#fff;}
    .kinz-tracking-step.is-current .kinz-tracking-dot{box-shadow:0 0 0 4px #fff,0 0 0 6px rgba(39,150,93,.17);}
    .kinz-tracking-label{max-width:100%;color:#88938c;font-size:.72rem;font-weight:650;line-height:1.3;overflow-wrap:anywhere;}
    .kinz-tracking-step.is-complete .kinz-tracking-label,.kinz-tracking-step.is-current .kinz-tracking-label{color:#247448;}
    .kinz-tracking-state-note{margin-top:13px;padding:10px 12px;border-radius:9px;background:#f5f8f5;color:#607067;font-size:.8rem;line-height:1.5;}
    .kinz-tracking-cancelled{margin:15px 0;padding:12px 14px;border:1px solid #f0d2ce;border-radius:9px;background:#fff5f3;color:#a33d34;font-size:.85rem;font-weight:700;}
    .order-history-items{padding:8px 0 4px;}
    .order-history-item{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:12px 0;border-bottom:1px solid #f1ede5;}
    .order-history-item:last-child{border-bottom:0;}
    .order-history-item-main{display:flex;align-items:center;gap:12px;min-width:0;}
    .order-history-item img{width:52px;height:52px;border-radius:9px;object-fit:cover;background:#f5f0e7;border:1px solid #eadfc9;}
    .order-history-item-title{font-weight:700;color:#263d34;font-size:.88rem;}.order-history-item-meta{font-size:.76rem;color:#7b847f;margin-top:4px;}
    .order-history-item-price{font-weight:700;color:#173d32;white-space:nowrap;font-size:.88rem;}
    .order-history-summary{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px 22px;border-top:1px solid #eee8dc;padding-top:15px;margin-top:6px;font-size:.86rem;}
    .order-summary-line{display:flex;justify-content:space-between;gap:12px;color:#68746e;}.order-summary-line strong{color:#263d34;}
    .order-summary-total{grid-column:1/-1;display:flex;justify-content:space-between;border-top:1px dashed #ded6c7;padding-top:12px;margin-top:5px;font-size:1.02rem;color:#173d32;font-weight:800;}
    .order-delivery-info{margin-top:15px;padding:13px 15px;border-radius:10px;background:#f7f7f1;color:#56635c;font-size:.82rem;line-height:1.55;}
    .order-delivery-info strong{color:#23483b;}
    .order-history-empty{text-align:center;background:#fff;border:1px solid #e7e0d3;border-radius:16px;padding:50px 20px;color:#66736c;}
    .order-cancel-notice{margin:0 0 20px;padding:13px 16px;border-radius:10px;font-size:.9rem;font-weight:650;}
    .order-cancel-notice.success{background:#eaf6ed;color:#23623b;border:1px solid #cce8d3;}
    .order-cancel-notice.error{background:#fff0ed;color:#9b352b;border:1px solid #f0d0ca;}
    .order-history-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:15px;padding-top:14px;border-top:1px solid #eee8dc;}
    .order-cancel-help{font-size:.79rem;color:#79827d;line-height:1.45;}
    .order-cancel-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #b94d43;background:#fff;color:#a43d34;border-radius:8px;padding:10px 15px;font-weight:700;font-size:.84rem;font-family:inherit;cursor:pointer;transition:background .2s ease,color .2s ease,transform .2s ease;}
    .order-cancel-button:hover{background:#a43d34;color:#fff;transform:translateY(-1px);}
    .order-history-buttons{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
    .order-pay-button{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid #0A3D2E;background:#0A3D2E;color:#fff;border-radius:8px;padding:10px 15px;font-weight:700;font-size:.84rem;font-family:inherit;cursor:pointer;transition:background .2s ease,transform .2s ease;}
    .order-pay-button:hover:not(:disabled){background:#0D4A38;transform:translateY(-1px);}
    .order-pay-button:disabled{opacity:.6;cursor:not-allowed;}
    .order-payment-paid{color:#237443;font-weight:700;}
    .order-payment-unpaid{color:#a43832;font-weight:700;}
    .order-cancel-button:focus-visible{outline:3px solid rgba(164,61,52,.22);outline-offset:2px;}
    .order-history-empty i{font-size:2rem;color:#b58a42;margin-bottom:12px;}
    .my-orders-back{display:inline-flex;align-items:center;gap:8px;color:#805f28;font-weight:700;text-decoration:none;padding:10px 15px;border:1px solid #d9c7a4;border-radius:8px;}
    .order-review-area{margin-top:12px;padding:13px 14px;border:1px solid #eadfc9;border-radius:12px;background:#fffdf8;}
    .order-review-head{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;}
    .order-review-button{border:1px solid #174b3d;background:#174b3d;color:#fff;border-radius:8px;padding:9px 13px;font-family:inherit;font-size:.82rem;font-weight:700;cursor:pointer;}
    .order-review-button:hover{background:#0f382d;}
    .order-review-form{margin-top:12px;display:grid;gap:10px;}
    .order-review-form[hidden]{display:none!important;}
    .order-review-form label{font-size:.8rem;font-weight:700;color:#29483c;display:block;margin-bottom:5px;}
    .order-review-form select,.order-review-form input[type=text],.order-review-form textarea{width:100%;border:1px solid #dcd3c1;border-radius:8px;padding:10px 11px;background:#fff;font:inherit;font-size:.86rem;color:#243b32;}
    .order-review-form textarea{min-height:85px;resize:vertical;}
    .order-review-submit{border:0;border-radius:8px;background:#b98a42;color:#fff;padding:10px 16px;font-family:inherit;font-size:.85rem;font-weight:700;cursor:pointer;justify-self:start;}
    .order-review-stars{color:#d5a342;letter-spacing:2px;font-size:1rem;}
    .order-review-done{font-size:.81rem;color:#287246;font-weight:700;}
    .order-review-flash{margin:0 0 20px;padding:13px 16px;border-radius:10px;font-size:.9rem;font-weight:650;background:#eaf6ed;color:#23623b;border:1px solid #cce8d3;}
    .review-rating-options{display:flex;gap:6px;align-items:center;}
    .review-rating-options label{margin:0;cursor:pointer;}
    .review-rating-options input{position:absolute;opacity:0;pointer-events:none;}
    .review-rating-options span{display:inline-block;color:#d7d7d1;font-size:1.65rem;line-height:1;transition:color .15s ease;}
    .review-rating-options label.selected span,.review-rating-options label:hover span{color:#d5a342;}
    .kinz-review-gate{position:fixed;inset:0;z-index:100000;background:rgba(10,27,21,.72);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:20px;}
    .kinz-review-gate-card{width:min(560px,100%);max-height:min(92vh,850px);overflow:auto;background:#fffdf8;border:1px solid #d8c49e;border-radius:18px;box-shadow:0 24px 80px rgba(0,0,0,.3);padding:26px;}
    .kinz-review-gate-kicker{color:#a57a37;font-size:.74rem;font-weight:900;letter-spacing:1.2px;text-transform:uppercase;}
    .kinz-review-gate-card h2{color:#123d32;font-size:1.55rem;margin:7px 0 8px;}
    .kinz-review-gate-card p{color:#69766f;font-size:.9rem;line-height:1.6;margin:0 0 17px;}
    .kinz-review-gate-product{display:flex;align-items:center;gap:13px;padding:12px;background:#f7f3e9;border:1px solid #ece1cb;border-radius:12px;margin-bottom:16px;}
    .kinz-review-gate-product img{width:66px;height:66px;object-fit:cover;border-radius:9px;background:#eee;}
    .kinz-review-gate-product strong{display:block;color:#173d32;font-size:.9rem;line-height:1.4;}
    .kinz-review-gate-product span{display:block;color:#7a817b;font-size:.75rem;margin-top:4px;}
    .kinz-review-gate-form{display:grid;gap:12px;}
    .kinz-review-gate-form label{display:block;margin-bottom:5px;color:#29483c;font-size:.8rem;font-weight:800;}
    .kinz-review-gate-form textarea,.kinz-review-gate-form input[type=text]{width:100%;border:1px solid #dcd3c1;border-radius:8px;padding:11px;background:#fff;font:inherit;color:#243b32;}
    .kinz-review-gate-form textarea{min-height:100px;resize:vertical;}
    .kinz-review-gate-submit,.kinz-review-gate-continue{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:0;border-radius:9px;background:#174b3d;color:#fff;padding:12px 17px;font:700 .88rem Arial,sans-serif;text-decoration:none;cursor:pointer;}
    .kinz-review-gate-submit:hover,.kinz-review-gate-continue:hover{background:#0f382d;color:#fff;}
    .kinz-review-gate-count{display:inline-flex;padding:5px 9px;border-radius:20px;background:#f3e7ce;color:#856127;font-size:.72rem;font-weight:800;margin-bottom:12px;}
    body.kinz-review-gate-open{overflow:hidden;}
    @media(max-width:620px){.my-orders-page{margin-top:32px;padding:0 14px;}.order-history-card{padding:16px;}.order-history-summary{grid-template-columns:1fr;}.order-summary-total{grid-column:1;}.order-history-item{align-items:flex-start;}.order-history-item img{width:44px;height:44px;}.kinz-tracking-label{font-size:.62rem;}.kinz-tracking-dot{width:28px;height:28px;font-size:.68rem;}.kinz-tracking-track{top:13px;}.kinz-tracking-wrap{margin:17px -3px 7px;}}
  </style>
  <meta name="kinz-csrf" content="<?= my_orders_escape($_SESSION['kinz_csrf_token']) ?>">
</head>
<body>
<header class="kinz-header" id="main-header">
  <div class="header-inner">
    <a href="index.php" class="brand-logo" aria-label="KINZ Home"><img src="assets/images/kinz_logo.png" alt="KINZ Luxury Jewellery" class="brand-logo-img"></a>
    <nav aria-label="Main Navigation"><ul class="nav-menu" id="primary-nav">
      <li><a href="index.php" class="nav-link">Home</a></li><li><a href="shop.php" class="nav-link">Shop</a></li><li><a href="index.php#featured-collections" class="nav-link">Collections</a></li><li><a href="about.html" class="nav-link">About</a></li>
    </ul></nav>
    <div class="header-actions"><a href="account.html#notifications" class="action-btn notification-action-btn" title="Notifications" aria-label="Notifications"><i class="fa-regular fa-bell" aria-hidden="true"></i><span class="action-badge notification-badge-count" aria-live="polite" aria-label="Unread notifications" aria-hidden="true" style="display:none;">0</span></a>

        <a href="account.html" class="action-btn" title="My Account"><i class="fa-regular fa-user"></i></a></div>
  </div>
</header>

<main class="my-orders-page">
  <div class="my-orders-heading">
    <div><h1>My Orders</h1><p>Hello <?= my_orders_escape($customerName) ?>, view your order history and delivery updates here.</p></div>
    <div class="my-orders-heading-actions">
      <a class="my-orders-back" href="account.html"><i class="fa-solid fa-arrow-left"></i> Back to My Account</a>
      <a class="my-orders-back" href="shop.php"><i class="fa-solid fa-bag-shopping"></i> Continue Shopping</a>
    </div>
  </div>

  <?php if (is_array($cancelNotice) && !empty($cancelNotice['message'])): ?>
    <div class="order-cancel-notice <?= !empty($cancelNotice['success']) ? 'success' : 'error' ?>" role="status"><?= my_orders_escape($cancelNotice['message']) ?></div>
  <?php endif; ?>

  <?php if (is_array($reviewNotice) && !empty($reviewNotice['message'])): ?>
    <div class="order-review-flash" role="status"><?= my_orders_escape($reviewNotice['message']) ?></div>
  <?php endif; ?>

  <?php if (!$orders): ?>
    <div class="order-history-empty"><i class="fa-solid fa-box-open"></i><h2>No orders yet</h2><p>Your placed orders will appear here.</p><a class="my-orders-back" href="shop.php">Explore Jewellery</a></div>
  <?php else: ?>
    <?php foreach ($orders as $order):
      $status = (string)($order['status'] ?? 'Pending');
      $items = [];
      if ($itemsStmt) {
        $orderId = (int)$order['id'];
        $itemsStmt->bind_param('i', $orderId);
        if ($itemsStmt->execute()) {
          $itemResult = $itemsStmt->get_result();
          while ($itemResult && ($itemRow = $itemResult->fetch_assoc())) $items[] = $itemRow;
        }
      }
      if (!$items && !empty($order['items_json'])) {
        $decoded = json_decode((string)$order['items_json'], true);
        if (is_array($decoded)) $items = $decoded;
      }
      $dateLabel = !empty($order['order_date']) ? date('d M Y, h:i A', strtotime($order['order_date'])) : '—';
      $tracking = trim((string)($order['tracking_number'] ?? ''));
      $isPaid = strcasecmp((string)($order['payment_status'] ?? ''), 'Paid') === 0;
      $isRefunded = strcasecmp((string)($order['payment_status'] ?? ''), 'Refunded') === 0;
      $canPay = !$isPaid && strcasecmp(trim($status), 'Pending') === 0;
    ?>
      <article class="order-history-card" id="order-<?= (int)$order['id'] ?>">
        <div class="order-history-top">
          <div><div class="order-history-code">Order #<?= my_orders_escape($order['order_code']) ?></div><div class="order-history-date">Placed on <?= my_orders_escape($dateLabel) ?></div></div>
          <span class="order-status-badge <?= my_orders_status_class($status) ?>"><?= my_orders_escape($status) ?></span>
        </div>
        <?php
          $timelineIndex = my_orders_timeline_index($status);
          $timelineSteps = [
            ['Order Confirmed', 'fa-clipboard-check'],
            ['Processing', 'fa-box-open'],
            ['Shipping', 'fa-truck-fast'],
            ['Out for Delivery', 'fa-motorcycle'],
            ['Delivered', 'fa-house-circle-check']
          ];
        ?>
        <?php if (strtolower(trim($status)) === 'cancelled'): ?>
          <div class="kinz-tracking-cancelled"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> This order has been cancelled.</div>
        <?php else: ?>
          <div class="kinz-tracking-wrap">
            <div class="kinz-tracking-timeline" aria-label="Order delivery progress">
              <div class="kinz-tracking-track"><div class="kinz-tracking-fill" style="width:<?= $timelineIndex < 0 ? '0' : number_format(($timelineIndex / 4) * 80, 2, '.', '') ?>%;"></div></div>
              <?php foreach ($timelineSteps as $stepIndex => $step): ?>
                <div class="kinz-tracking-step<?= $timelineIndex >= 0 && $stepIndex < $timelineIndex ? ' is-complete' : '' ?><?= $timelineIndex === $stepIndex ? ' is-current' : '' ?>"<?= $timelineIndex === $stepIndex ? ' aria-current="step"' : '' ?>>
                  <div class="kinz-tracking-dot"><i class="fa-solid <?= my_orders_escape($step[1]) ?>" aria-hidden="true"></i></div>
                  <span class="kinz-tracking-label"><?= my_orders_escape($step[0]) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
            <div class="kinz-tracking-state-note">
              <?php if ($timelineIndex < 0): ?>Your order has been received and is waiting for confirmation from the KINZ team.<?php elseif ($timelineIndex === 4): ?>Your order has been delivered. Thank you for choosing KINZ.<?php else: ?>Current stage: <?= my_orders_escape($timelineSteps[$timelineIndex][0]) ?>. We will update this timeline as your order moves forward.<?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
        <div class="order-history-items">
          <?php foreach ($items as $item):
            $title = $item['title'] ?? $item['product_title'] ?? 'Jewellery Piece';
            $qty = max(1, (int)($item['quantity'] ?? $item['qty'] ?? 1));
            $unit = (float)($item['unit_price'] ?? 0);
            $line = (float)($item['line_total'] ?? $item['price'] ?? ($unit * $qty));
            $image = my_orders_image($item['product_image'] ?? ($item['image'] ?? ''));
            $orderItemId = (int)($item['order_item_id'] ?? 0);
            $itemProductId = (int)($item['product_id'] ?? 0);
            $itemReviewRating = (int)($item['review_rating'] ?? 0);
            $isDelivered = strtolower(trim($status)) === 'delivered';
            $reviewFormId = 'review-form-' . $orderItemId;
          ?>
            <div class="order-history-item">
              <div class="order-history-item-main"><img src="<?= my_orders_escape($image) ?>" alt="<?= my_orders_escape($title) ?>"><div><div class="order-history-item-title"><?= my_orders_escape($title) ?></div><div class="order-history-item-meta">Quantity: <?= $qty ?><?php if ($unit > 0): ?> · <?= my_orders_currency($unit) ?> each<?php endif; ?></div>
                <?php if ($isDelivered && $orderItemId > 0 && $itemProductId > 0): ?>
                  <div class="order-review-area">
                    <?php if ($itemReviewRating > 0 && strtolower((string)($item['review_status'] ?? '')) !== 'rejected'): ?>
                      <div class="order-review-head"><span class="order-review-done"><i class="fa-solid fa-circle-check"></i> Review submitted</span><span class="order-review-stars" aria-label="Rated <?= $itemReviewRating ?> out of 5"><?= str_repeat('★', max(0, min(5, $itemReviewRating))) . str_repeat('☆', 5 - max(0, min(5, $itemReviewRating))) ?></span></div>
                    <?php elseif (strtolower((string)($item['review_status'] ?? '')) === 'rejected'): ?>
                      <div class="order-review-head"><span style="font-size:.8rem;color:#9b4138;font-weight:700;">Your review was not approved. Please contact KINZ support.</span></div>
                    <?php else: ?>
                      <div class="order-review-head"><span style="font-size:.8rem;color:#6f786f;">How was this product?</span><button class="order-review-button" type="button" data-review-toggle="<?= my_orders_escape($reviewFormId) ?>"><i class="fa-regular fa-star"></i> Write a Review</button></div>
                      <form class="order-review-form" id="<?= my_orders_escape($reviewFormId) ?>" action="submit-review.php" method="POST" hidden>
                        <input type="hidden" name="csrf_token" value="<?= my_orders_escape($_SESSION['kinz_review_csrf']) ?>">
                        <input type="hidden" name="order_item_id" value="<?= $orderItemId ?>">
                        <div><label>Rating</label><div class="review-rating-options" data-star-picker>
                          <?php for ($starValue = 1; $starValue <= 5; $starValue++): ?>
                            <label><input type="radio" name="rating" value="<?= $starValue ?>" required><span aria-hidden="true">★</span></label>
                          <?php endfor; ?>
                        </div></div>
                        <div><label for="review-title-<?= $orderItemId ?>">Review Title (optional)</label><input id="review-title-<?= $orderItemId ?>" type="text" name="title" maxlength="120" placeholder="Give your review a title"></div>
                        <div><label for="review-text-<?= $orderItemId ?>">Your Feedback</label><textarea id="review-text-<?= $orderItemId ?>" name="review_text" maxlength="1500" minlength="5" required placeholder="Share your experience with this product..."></textarea></div>
                        <button type="submit" class="order-review-submit">Submit Review</button>
                      </form>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>
              </div></div>
              <div class="order-history-item-price"><?= my_orders_currency($line) ?></div>
            </div>
          <?php endforeach; ?>
          <?php if (!$items): ?><div class="order-history-item-title"><?= (int)$order['items_count'] ?> jewellery item(s)</div><?php endif; ?>
        </div>
        <div class="order-history-summary">
          <div class="order-summary-line"><span>Subtotal</span><strong><?= my_orders_currency($order['subtotal'] ?? $order['total_amount']) ?></strong></div>
          <?php if ((float)($order['discount_amount'] ?? 0) > 0): ?><div class="order-summary-line"><span>Coupon (<?= my_orders_escape($order['coupon_code']) ?>)</span><strong>−<?= my_orders_currency($order['discount_amount']) ?></strong></div><?php endif; ?>
          <div class="order-summary-line"><span>Tax</span><strong><?= my_orders_currency($order['tax_amount'] ?? 0) ?></strong></div>
          <div class="order-summary-line"><span>Shipping</span><strong><?= my_orders_currency($order['shipping_amount'] ?? 0) ?></strong></div>
          <div class="order-summary-total"><span>Order Total</span><span><?= my_orders_currency($order['total_amount']) ?></span></div>
        </div>
        <div class="order-delivery-info">
          <div><strong>Payment preference:</strong> <?= my_orders_escape($order['payment_method'] ?? '—') ?></div>
          <?php if (strcasecmp(trim($status), 'Cancelled') !== 0 || $isRefunded): ?><div><strong>Payment status:</strong> <span class="<?= ($isPaid || $isRefunded) ? 'order-payment-paid' : 'order-payment-unpaid' ?>"><?= $isRefunded ? 'Refunded' : ($isPaid ? 'Paid' : 'Payment pending') ?></span></div><?php endif; ?>
          <div><strong>Delivery address:</strong> <?= my_orders_escape($order['shipping_address'] ?? '—') ?></div>
          <?php if ($tracking !== ''): ?><div><strong>Courier tracking number:</strong> <?= my_orders_escape($tracking) ?></div><?php elseif ($status === 'Shipped'): ?><div><strong>Tracking:</strong> The courier tracking number will be added shortly.</div><?php endif; ?>
          <div style="margin-top:6px;">Order status is updated by the KINZ team. Refresh this page to see the latest status.</div>
        </div>
        <?php if (in_array(strtolower(trim($status)), ['pending', 'confirmed', 'processing'], true)): ?>
          <div class="order-history-actions">
            <div class="order-cancel-help"><?= $canPay ? 'Payment is pending. Pay within 30 minutes to confirm this order, or it will be cancelled automatically.' : ($isPaid ? 'You can cancel this order before it is shipped. Your payment will be refunded.' : 'You can cancel this order before it is shipped.') ?></div>
            <div class="order-history-buttons">
              <?php if ($canPay): ?>
                <button type="button" class="order-pay-button" data-pay-order="<?= my_orders_escape($order['order_code']) ?>"><i class="fa-solid fa-lock"></i> Pay Now <?= my_orders_currency($order['total_amount']) ?></button>
              <?php endif; ?>
              <form method="POST" action="cancel-order.php" onsubmit="return confirm('Are you sure you want to cancel order #<?= my_orders_escape($order['order_code']) ?>? <?= $isPaid ? 'Your payment will be refunded to the original payment method. ' : '' ?>This cannot be undone.');">
                <input type="hidden" name="csrf_token" value="<?= my_orders_escape($_SESSION['kinz_order_cancel_csrf']) ?>">
                <input type="hidden" name="order_id" value="<?= (int)$order['id'] ?>">
                <button type="submit" class="order-cancel-button"><i class="fa-solid fa-xmark"></i> Cancel Order</button>
              </form>
            </div>
          </div>
        <?php elseif (strtolower(trim($status)) === 'cancelled'): ?>
          <div class="order-history-actions"><div class="order-cancel-help">This order has been cancelled.<?= $isRefunded ? ' Your payment was refunded to the original payment method.' : '' ?></div></div>
        <?php endif; ?>
      </article>
    <?php endforeach; ?>
    <?php if ($itemsStmt) $itemsStmt->close(); ?>
  <?php endif; ?>
</main>

<?php if ($reviewGateRequested): ?>
  <div class="kinz-review-gate" role="dialog" aria-modal="true" aria-labelledby="kinz-review-gate-title">
    <section class="kinz-review-gate-card">
      <?php if (!empty($pendingReviewItems)):
        $mandatoryReview = $pendingReviewItems[0];
        $mandatoryImage = my_orders_image($mandatoryReview['product_image'] ?? '');
      ?>
        <div class="kinz-review-gate-kicker">KINZ verified purchase</div>
        <h2 id="kinz-review-gate-title">Your delivered jewellery deserves a review</h2>
        <p>Please rate the delivered product below before continuing to shop. You have <?= count($pendingReviewItems) ?> delivered product<?= count($pendingReviewItems) === 1 ? '' : 's' ?> waiting for feedback.</p>
        <span class="kinz-review-gate-count">Review <?= count($pendingReviewItems) ?> pending</span>
        <div class="kinz-review-gate-product">
          <img src="<?= my_orders_escape($mandatoryImage) ?>" alt="<?= my_orders_escape($mandatoryReview['product_title']) ?>">
          <div><strong><?= my_orders_escape($mandatoryReview['product_title']) ?></strong><span>Order #<?= my_orders_escape($mandatoryReview['order_code']) ?> · Qty <?= (int)$mandatoryReview['qty'] ?></span></div>
        </div>
        <form class="kinz-review-gate-form" action="submit-review.php" method="POST">
          <input type="hidden" name="csrf_token" value="<?= my_orders_escape($_SESSION['kinz_review_csrf']) ?>">
          <input type="hidden" name="order_item_id" value="<?= (int)$mandatoryReview['order_item_id'] ?>">
          <input type="hidden" name="mandatory_review" value="1">
          <div><label>Your rating</label><div class="review-rating-options" data-star-picker>
            <?php for ($starValue = 1; $starValue <= 5; $starValue++): ?>
              <label><input type="radio" name="rating" value="<?= $starValue ?>" required><span aria-hidden="true">★</span></label>
            <?php endfor; ?>
          </div></div>
          <div><label for="mandatory-review-title">Review title (optional)</label><input id="mandatory-review-title" type="text" name="title" maxlength="120" placeholder="Give your review a title"></div>
          <div><label for="mandatory-review-text">Your feedback</label><textarea id="mandatory-review-text" name="review_text" minlength="5" maxlength="1500" required placeholder="Tell us what you think about this jewellery piece..."></textarea></div>
          <button class="kinz-review-gate-submit" type="submit"><i class="fa-solid fa-star"></i> Submit Review & Continue</button>
        </form>
      <?php else: ?>
        <div class="kinz-review-gate-kicker">Thank you</div>
        <h2 id="kinz-review-gate-title">All delivered products reviewed</h2>
        <p>Thank you for rating your KINZ purchases. You can now continue shopping.</p>
        <a class="kinz-review-gate-continue" href="shop.php"><i class="fa-solid fa-bag-shopping"></i> Continue Shopping</a>
      <?php endif; ?>
    </section>
  </div>
  <script>document.body.classList.add('kinz-review-gate-open');</script>
<?php endif; ?>

<footer class="kinz-footer"><div class="footer-bottom-bar"><div class="footer-bottom-inner"><div>&copy; 2026 KINZ. All rights reserved. | <span style="color:var(--gold-400);">A Hidden Treasure.</span></div></div></div></footer>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="assets/js/cart-actions.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-actions.js') ?>"></script>
<script src="assets/js/razorpay-pay.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/razorpay-pay.js') ?>"></script>
<script>
(function () {
  document.querySelectorAll('[data-pay-order]').forEach(function (button) {
    button.addEventListener('click', async function () {
      button.disabled = true;
      try {
        const payment = await window.KINZPayOrder(button.dataset.payOrder);
        if (payment.paid) window.location.href = payment.redirect;
        else button.disabled = false;
      } catch (error) {
        alert(error.message);
        button.disabled = false;
      }
    });
  });
})();
</script>

<script>
(function () {
  document.querySelectorAll('[data-review-toggle]').forEach(function (button) {
    button.addEventListener('click', function () {
      const form = document.getElementById(button.dataset.reviewToggle);
      if (!form) return;
      form.hidden = !form.hidden;
      button.innerHTML = form.hidden ? '<i class="fa-regular fa-star"></i> Write a Review' : '<i class="fa-solid fa-minus"></i> Close Review';
    });
  });
  document.querySelectorAll('[data-star-picker]').forEach(function (picker) {
    const labels = Array.from(picker.querySelectorAll('label'));
    labels.forEach(function (label) {
      const input = label.querySelector('input');
      input.addEventListener('change', function () {
        const rating = Number(input.value);
        labels.forEach(function (entry) {
          entry.classList.toggle('selected', Number(entry.querySelector('input').value) <= rating);
        });
      });
    });
  });
})();
</script>
<?php if ($reviewGateRequested && !empty($pendingReviewItems)): ?>
<script>
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && document.querySelector('.kinz-review-gate')) event.preventDefault();
  });
  document.querySelector('.kinz-review-gate-form')?.querySelector('input[name=rating]')?.focus();
</script>
<?php endif; ?>

<script src="assets/js/cart-drawer.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-drawer.js') ?>"></script>
<script src="assets/js/kinz-ui.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/kinz-ui.js') ?>"></script>
</body>
</html>
