<?php
require_once __DIR__ . '/includes/cart_helpers.php';
$orderCode = trim((string)($_GET['order_code'] ?? $_SESSION['kinz_last_order_code'] ?? ''));
$customerEmail = (string)($_SESSION['customer_email'] ?? '');
$checkOrder = $conn->prepare('SELECT id, status, tracking_number, payment_status FROM orders WHERE order_code = ? AND customer_email = ? LIMIT 1');
$checkOrder->bind_param('ss', $orderCode, $customerEmail);
$checkOrder->execute();
$orderRecord = $checkOrder->get_result()->fetch_assoc();
$checkOrder->close();
if (!$orderRecord) {
    header('Location: shop.php');
    exit;
}
$orderStatus = (string)($orderRecord['status'] ?? 'Pending');
$orderTracking = (string)($orderRecord['tracking_number'] ?? '');
$isPaid = strcasecmp((string)($orderRecord['payment_status'] ?? ''), 'Paid') === 0;
$isCancelled = strcasecmp($orderStatus, 'Cancelled') === 0;
$canPay = !$isPaid && strcasecmp($orderStatus, 'Pending') === 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed | KINZ - A Hidden Treasure</title>
  <meta name="description" content="Thank you for shopping with KINZ. Your jewellery order has been confirmed.">
  
  <link rel="icon" type="image/png" href="assets/images/kinz_logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <meta name="kinz-csrf" content="<?= htmlspecialchars($_SESSION['kinz_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>

  <!-- Navigation Header with Enlarged Logo -->
  <header class="kinz-header" id="main-header">
    <div class="header-inner">
      <!-- Brand Logo (Pure Regal Emblem - Name text removed per specification) -->
      <a href="index.php" class="brand-logo" id="header-brand-logo" aria-label="KINZ Home" data-testid="header-logo-link">
        <img src="assets/images/kinz_logo.png" alt="KINZ Luxury Jewellery" class="brand-logo-img" data-testid="header-logo-img">
      </a>

      <!-- Navigation Menu with Traveling Underline Indicator -->
      <nav aria-label="Main Navigation" data-testid="primary-navigation">
        <ul class="nav-menu" id="primary-nav">
          <li><a href="index.php" class="nav-link" data-nav="home" data-testid="nav-home">Home</a></li>
          <li><a href="shop.php" class="nav-link" data-nav="shop" data-testid="nav-shop">Shop</a></li>
          <li><a href="index.php#featured-collections" class="nav-link" data-nav="collections" data-testid="nav-collections">Collections</a></li>
          <li><a href="index.php#bestsellers" class="nav-link" data-nav="bestsellers" data-testid="nav-bestsellers">Best Sellers</a></li>
          <li><a href="index.php#new-arrivals" class="nav-link" data-nav="new-arrivals" data-testid="nav-new-arrivals">New Arrivals</a></li>
          <li><a href="about.html" class="nav-link" data-nav="about" data-testid="nav-about">About</a></li>
          <li><a href="contact.html" class="nav-link" data-nav="contact" data-testid="nav-contact">Contact</a></li>
          <li class="nav-indicator" id="nav-indicator" aria-hidden="true"></li>
        </ul>
      </nav>

      <!-- Search Input with Autocomplete -->
      <div class="header-search">
        <div class="search-input-wrapper">
          <i class="fa-solid fa-magnifying-glass search-icon"></i>
          <input type="text" class="search-input" id="site-search-input" placeholder="Search for jewellery..." autocomplete="off">
        </div>
        <div class="search-results-dropdown" id="search-dropdown"></div>
      </div>

      <!-- Header Action Icons -->
      <div class="header-actions">
        <a href="account.html#wishlist" class="action-btn" title="Wishlist" aria-label="Wishlist">
          <i class="fa-regular fa-heart"></i>
          <span class="action-badge wishlist-badge-count" style="display:none;">0</span>
        </a>

        <button class="action-btn open-cart-drawer-btn" title="Shopping Bag" aria-label="Shopping Bag">
          <i class="fa-solid fa-bag-shopping"></i>
          <span class="action-badge cart-badge-count" style="display:none;">0</span>
        </button>

        <a href="account.html#notifications" class="action-btn notification-action-btn" title="Notifications" aria-label="Notifications"><i class="fa-regular fa-bell" aria-hidden="true"></i><span class="action-badge notification-badge-count" aria-live="polite" aria-label="Unread notifications" aria-hidden="true" style="display:none;">0</span></a>

        <a href="account.html" class="action-btn" title="My Account" aria-label="My Account">
          <i class="fa-regular fa-user"></i></a>

        <!-- Mobile Menu Toggle Button -->
        <button class="action-btn menu-toggle-btn" aria-label="Toggle Navigation Menu">
          <i class="fa-solid fa-bars"></i>
        </button>
      </div>
    </div>
  </header>

  <main>
    <div class="confirmation-page-container">
      
      <!-- Center Confirmation Card (Matching PDF Page 7) -->
      <div class="confirmation-card">
        
        <!-- Animated Luxury Success Tick Symbol -->
        <div class="confirmation-animated-tick-wrapper" aria-label="Order Placed Successfully">
          <div class="tick-pulse-ring"></div>
          <svg class="confirmation-checkmark-svg" viewBox="0 0 80 80">
            <circle class="checkmark-circle" cx="40" cy="40" r="36" fill="none" />
            <path class="checkmark-check" fill="none" d="M24 41 l11 11 l22 -22" />
          </svg>
        </div>

        <h1 class="confirmation-title"><?= $isPaid ? 'Payment Successful!' : ($isCancelled ? 'Order Cancelled' : 'Order Received!') ?></h1>
        <p class="confirmation-subtitle">
          Thank you for shopping with KINZ.<br>
          <?php if ($isPaid): ?>
            Your payment was received and your order is confirmed.
          <?php elseif ($isCancelled): ?>
            This order has been cancelled.
          <?php else: ?>
            Your order has been recorded. Complete the payment within 30 minutes to confirm it, or it will be cancelled automatically.
          <?php endif; ?>
        </p>

        <!-- Order ID Box with Copy Button (Matching PDF Page 7: Order ID #KINZ482739) -->
        <div class="order-id-box">
          <span>Order ID <strong id="conf-order-id">#<?= htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8') ?></strong></span>
          <button class="btn-copy-order-id" id="btn-copy-id" title="Copy Order ID">
            <i class="fa-regular fa-copy"></i>
          </button>
        </div>

        <?php if ($canPay): ?>
        <button type="button" class="btn-pay-now" id="btn-retry-payment" data-order-code="<?= htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8') ?>" style="margin-bottom: 16px;">
          <i class="fa-solid fa-lock"></i> Pay Now
        </button>
        <?php endif; ?>

        <!-- Action Buttons (Matching PDF Page 7: Track Order > | View Orders >) -->
        <div class="confirmation-actions-col">
          <a href="my-orders.php" class="btn-conf-track" id="btn-conf-track">
            Track Order <i class="fa-solid fa-chevron-right" style="margin-left: 4px; font-size: 0.8rem;"></i>
          </a>
          <a href="my-orders.php" class="btn-conf-orders" id="btn-conf-orders">
            View Orders <i class="fa-solid fa-chevron-right" style="margin-left: 4px; font-size: 0.8rem;"></i>
          </a>
        </div>

        <!-- Shipping Status Notification (Matching PDF Page 7) -->
        <div class="conf-shipping-alert">
          <i class="fa-solid fa-truck" style="font-size: 1.4rem; color: var(--emerald-800);"></i>
          <div>
            <strong><?= $isPaid ? 'Your order is being prepared!' : ($isCancelled ? 'This order was cancelled.' : 'Waiting for your payment.') ?></strong><br>
            <span style="color: var(--text-muted); font-size: 0.8rem;">Your order is currently <?= htmlspecialchars($orderStatus, ENT_QUOTES, 'UTF-8') ?>. We will update this status as your order progresses.<?php if ($orderTracking !== ''): ?> Tracking number: <?= htmlspecialchars($orderTracking, ENT_QUOTES, 'UTF-8') ?>.<?php endif; ?></span>
          </div>
        </div>

        <!-- Customer Care Help Link (Matching PDF Page 7) -->
        <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 14px;">
          <a href="contact.html" style="display: inline-flex; align-items: center; gap: 8px; color: var(--emerald-800); font-weight: 600;">
            <i class="fa-solid fa-headset"></i> Need help? Contact our support team
          </a>
        </div>

      </div>

    </div>
  </main>

  <footer class="kinz-footer">
    <div class="footer-bottom-bar">
      <div class="footer-bottom-inner">
        <div>&copy; 2026 KINZ. All rights reserved. | <span style="color: var(--gold-400);">A Hidden Treasure.</span></div>
        <div class="footer-policy-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms & Conditions</a>
          <a href="#">Shipping Policy</a>
        </div>
      </div>
    </div>
  </footer>

  
  
  
  
  
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script src="assets/js/cart-actions.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-actions.js') ?>"></script>
<script src="assets/js/razorpay-pay.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/razorpay-pay.js') ?>"></script>
<script>
  const payButton = document.getElementById('btn-retry-payment');
  if (payButton) payButton.addEventListener('click', async function () {
    payButton.disabled = true;
    try {
      const payment = await window.KINZPayOrder(payButton.dataset.orderCode);
      if (payment.paid) window.location.href = payment.redirect;
      else payButton.disabled = false;
    } catch (error) {
      alert(error.message);
      payButton.disabled = false;
    }
  });
  const copyButton = document.getElementById('btn-copy-id');
  if (copyButton) copyButton.addEventListener('click', async function () {
    const code = document.getElementById('conf-order-id').textContent.replace(/^#/, '');
    try { await navigator.clipboard.writeText(code); alert('Order ID copied.'); }
    catch (error) { alert('Order ID: ' + code); }
  });
</script>
<script src="assets/js/cart-drawer.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-drawer.js') ?>"></script>
<script src="assets/js/kinz-ui.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/kinz-ui.js') ?>"></script>
</body>
</html>
