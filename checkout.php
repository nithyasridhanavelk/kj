<?php
require_once __DIR__ . '/includes/cart_helpers.php';
if (empty($_SESSION['customer_id'])) {
    $_SESSION['redirect_after_login'] = 'checkout.php';
    header('Location: login.php');
    exit;
}

$checkoutItemsForCoupons = kinz_cart_load_items($conn);
$availableCoupons = kinz_cart_available_coupons($conn, $checkoutItemsForCoupons);
$appliedCouponCode = strtoupper(trim((string)($_SESSION['kinz_coupon_code'] ?? '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout | KINZ - A Hidden Treasure</title>
  <meta name="description" content="Secure checkout for your jewellery order at KINZ.">
  
  <link rel="icon" type="image/png" href="assets/images/kinz_logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <style>
    .available-coupons{margin:-5px 0 18px;padding:12px;background:#f8faf9;border:1px solid var(--border-light);border-radius:var(--radius-sm);}
    .available-coupons-heading{display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:10px;}
    .available-coupons-heading strong{font-size:.83rem;color:var(--text-dark);}
    .available-coupons-heading span{font-size:.7rem;color:var(--text-muted);}
    .available-coupons-list{display:flex;flex-direction:column;gap:8px;}
    .available-coupon-card{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center;gap:7px 12px;padding:10px;background:#fff;border:1px dashed var(--border-gold);border-radius:var(--radius-sm);}
    .available-coupon-code{font-size:.82rem;font-weight:800;letter-spacing:.08em;color:var(--emerald-900);}
    .available-coupon-offer{font-size:.78rem;font-weight:800;color:var(--emerald-800);text-align:right;}
    .available-coupon-terms{grid-column:1;grid-row:2;font-size:.7rem;line-height:1.5;color:var(--text-muted);min-width:0;}
    .available-coupon-use{grid-column:2;grid-row:2;align-self:center;justify-self:end;display:inline-flex;align-items:center;justify-content:center;min-width:94px;white-space:nowrap;border:1px solid var(--border-gold);background:#f8faf9;color:var(--emerald-900);border-radius:6px;padding:8px 11px;font-size:.72rem;font-weight:700;cursor:pointer;position:relative;z-index:1;}
    .available-coupon-use:hover:not(:disabled){background:var(--emerald-900);color:#fff;}
    .available-coupon-use:disabled{opacity:.5;cursor:not-allowed;}
    .available-coupon-status{display:block;margin-top:3px;font-size:.68rem;color:var(--text-muted);}
    .available-coupon-applied{color:#27824b;font-weight:700;}
    .coupon-applied-actions{display:flex;justify-content:flex-end;margin-top:8px;}
    .btn-remove-coupon{border:1px solid #b65b4b;background:#fff;color:#a14335;border-radius:6px;padding:7px 11px;font-size:.72rem;font-weight:700;cursor:pointer;}
    .btn-remove-coupon:hover{background:#fff4f1;}
    .btn-remove-coupon[hidden]{display:none!important;}
    .available-coupons-empty{font-size:.75rem;color:var(--text-muted);padding:5px 0;}
    @media(max-width:520px){.available-coupons-heading{align-items:flex-start;flex-direction:column;gap:2px;}}
  </style>
<meta name="kinz-csrf" content="<?= htmlspecialchars($_SESSION['kinz_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="checkout-fixed-page">

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
    <div class="checkout-page-container">
      
    
      <!-- Checkout Header -->
      <div class="checkout-header">
        <h1 class="checkout-page-title">Checkout</h1>
        <p class="checkout-page-subtitle">Complete your order securely and quickly</p>
      </div>

      <!-- Stepper Progress Bar (Matching PDF Page 5: 1 Address, 2 Shipping, 3 Payment, 4 Confirmation) -->
     
<!-- Checkout Progress Stepper -->
<div class="checkout-stepper" id="checkout-stepper"
     aria-label="Checkout Progress">

    <button type="button" class="step-indicator"
            id="step-ind-1" data-step="0">
        <span class="step-circle">1</span>
        <span class="step-label">Address</span>
    </button>

    <div class="step-connector-line"></div>

    <button type="button" class="step-indicator"
            id="step-ind-2" data-step="1">
        <span class="step-circle">2</span>
        <span class="step-label">Shipping</span>
    </button>

    <div class="step-connector-line"></div>

    <button type="button" class="step-indicator"
            id="step-ind-3" data-step="2">
        <span class="step-circle">3</span>
        <span class="step-label">Payment</span>
    </button>

    <div class="step-connector-line"></div>

    <button type="button" class="step-indicator"
            id="step-ind-4" data-step="3">
        <span class="step-circle">4</span>
        <span class="step-label">Confirmation</span>
    </button>

</div>


      <!-- Main Checkout Grid (Left: Address & Shipping | Right: Order Summary) -->
      <div class="checkout-main-grid">
        
        <!-- Left Column: Forms -->
        <div class="checkout-forms-column">
          
          <!-- Delivery Address Box (Matching PDF Page 5) -->
          <div class="checkout-card-box">
            <div class="checkout-box-title-row">
              <h2 class="checkout-box-title">
                <i class="fa-solid fa-location-dot" style="color: var(--emerald-800);"></i> Delivery Address
              </h2>
              <button class="btn-add-address" id="btn-open-add-addr">
                <i class="fa-solid fa-plus"></i> Add New Address
              </button>
            </div>

            <div class="address-options-list" id="checkout-address-list">
              <!-- Rendered via JS -->
            </div>
          </div>

          <!-- Shipping Method Box (Matching PDF Page 5) -->
          <div class="checkout-card-box">
            <div class="checkout-box-title-row">
              <h2 class="checkout-box-title">
                <i class="fa-solid fa-truck-fast" style="color: var(--emerald-800);"></i> Shipping Method
              </h2>
            </div>

            <label class="shipping-radio-card" id="ship-opt-std">
              <div class="shipping-left">
                <input type="radio" name="shipping_method" value="standard">
                <div>
                  <div class="shipping-method-title">Standard Delivery (3–5 days)</div>
                  <div class="shipping-method-desc">Reliable and safe delivery</div>
                </div>
              </div>
              <span class="shipping-price-tag" style="color: #27824b;">Free</span>
            </label>

            <label class="shipping-radio-card" id="ship-opt-exp">
              <div class="shipping-left">
                <input type="radio" name="shipping_method" value="express">
                <div>
                  <div class="shipping-method-title">Express Delivery (1–2 days)</div>
                  <div class="shipping-method-desc">Faster delivery to your doorstep</div>
                </div>
              </div>
              <span class="shipping-price-tag">₹ 150</span>
            </label>
          </div>

        </div>

        <!-- Right Column: Order Summary (Matching PDF Page 5) -->
        <aside class="order-summary-box" aria-label="Order Summary">
          <div class="checkout-box-title-row">
            <h2 class="checkout-box-title">
              <i class="fa-solid fa-bag-shopping" style="color: var(--emerald-800);"></i> Order Summary (<span id="summary-items-count">3</span> items)
            </h2>
          </div>

          <!-- Items list in summary -->
          <div class="summary-items-list" id="checkout-summary-items">
            <!-- Populated via JS -->
          </div>

          <!-- Discount code input box (Matching PDF Page 5) -->
          <div class="coupon-input-box">
            <input type="text" class="coupon-input" id="checkout-coupon-code" placeholder="Discount code" value="">
            <button class="btn-apply-coupon" id="btn-apply-coupon">Add code</button>
          </div>
          <div class="coupon-applied-actions">
            <button type="button" class="btn-remove-coupon" id="btn-remove-coupon" <?= $appliedCouponCode === '' ? 'hidden' : '' ?>>Remove applied coupon</button>
          </div>

          <div class="available-coupons" id="available-coupons">
            <div class="available-coupons-heading">
              <strong><i class="fa-solid fa-ticket" style="margin-right:5px;"></i> Available Coupons</strong>
              <span>Choose an offer to fill its code</span>
            </div>
            <?php if (!empty($availableCoupons)): ?>
              <div class="available-coupons-list">
                <?php foreach ($availableCoupons as $availableCoupon): ?>
                  <?php
                    $couponCode = (string)$availableCoupon['code'];
                    $couponType = (string)$availableCoupon['discount_type'];
                    $couponValue = (float)$availableCoupon['value'];
                    $couponOffer = strcasecmp($couponType, 'Percentage') === 0
                        ? rtrim(rtrim(number_format($couponValue, 2), '0'), '.') . '% OFF'
                        : '₹ ' . number_format($couponValue, 0) . ' OFF';
                    $couponMinOrder = (float)($availableCoupon['min_order'] ?? 0);
                    $couponScope = trim((string)($availableCoupon['applicable_to'] ?? 'All products'));
                    $couponExpiry = !empty($availableCoupon['expires_at'])
                        ? date('d M Y, h:i A', strtotime($availableCoupon['expires_at']))
                        : 'No expiry date';
                    $isEligible = !empty($availableCoupon['eligible']);
                    $isApplied = $appliedCouponCode !== '' && strcasecmp($appliedCouponCode, $couponCode) === 0;
                  ?>
                  <div class="available-coupon-card" data-coupon-card data-coupon-code="<?= htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8') ?>" data-coupon-eligible="<?= $isEligible ? '1' : '0' ?>">
                    <div class="available-coupon-code"><?= htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="available-coupon-offer"><?= htmlspecialchars($couponOffer, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="available-coupon-terms">
                      Min. order: ₹ <?= number_format($couponMinOrder, 0) ?>
                      · Applies to: <?= htmlspecialchars($couponScope, ENT_QUOTES, 'UTF-8') ?>
                      · Valid till: <?= htmlspecialchars($couponExpiry, ENT_QUOTES, 'UTF-8') ?>
                      <span class="available-coupon-status <?= $isApplied ? 'available-coupon-applied' : '' ?>"><?= $isApplied ? 'Applied to this order.' : ($isEligible ? 'Available for this order.' : 'Not eligible') ?></span>
                    </div>
                    <button type="button" class="available-coupon-use" data-coupon-code="<?= htmlspecialchars($couponCode, ENT_QUOTES, 'UTF-8') ?>" <?= (!$isEligible || $isApplied) ? 'disabled' : '' ?>><?= $isApplied ? 'Applied' : ($isEligible ? 'Use coupon' : 'Not eligible') ?></button>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="available-coupons-empty">There are no active coupons available right now.</div>
            <?php endif; ?>
          </div>

          <!-- Price Breakdown List (Matching PDF Page 5) -->
          <div class="price-breakdown-list">
            <div class="breakdown-row">
              <span>Subtotal (<span id="summary-subtotal-count">3</span> items)</span>
              <span id="summary-subtotal-val">₹ 1,59,997</span>
            </div>
            <div class="breakdown-row discount-row" id="summary-discount-row">
              <span>Discount</span>
              <span id="summary-discount-val">- ₹ 10,000</span>
            </div>
            <div class="breakdown-row">
              <span>Shipping</span>
              <span id="summary-shipping-val">Free</span>
            </div>
            <div class="breakdown-row">
              <span>Tax (GST 5%)</span>
              <span id="summary-tax-val">₹ 7,500</span>
            </div>
            <div class="breakdown-row total-row">
              <span>Total</span>
              <span id="summary-total-val">₹ 1,57,597</span>
            </div>
          </div>

          <!-- Preferred Payment Method Badge on Right Side -->
          <div class="checkout-selected-pay-badge" style="display: flex; align-items: center; justify-content: space-between; background: #f8faf9; border: 1.5px solid var(--border-gold); border-radius: var(--radius-sm); padding: 10px 14px; margin-bottom: 16px;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <div id="summary-pay-icon" style="width: 32px; height: 32px; border-radius: 6px; background: #ffffff; border: 1px solid var(--border-light); display: flex; align-items: center; justify-content: center; color: #27824b; font-size: 1rem;">
                <i class="fa-solid fa-mobile-screen-button"></i>
              </div>
              <div>
                <div style="font-size: 0.72rem; color: var(--text-muted); font-weight: 700; letter-spacing: 0.5px;">SELECTED PAYMENT METHOD</div>
                <div style="font-size: 0.88rem; font-weight: 700; color: var(--emerald-950);" id="summary-pay-method-label">No payment method selected</div>
              </div>
            </div>
            <button class="btn-change-pay" id="btn-summary-change-pay" style="background: none; border: none; color: var(--emerald-800); font-size: 0.82rem; font-weight: 700; cursor: pointer; text-decoration: underline;">Change</button>
          </div>

          <!-- Primary CTA Button (Matching PDF Page 5: Continue to Payment >) -->
          <button class="btn-checkout-proceed" id="btn-open-payment">
            <i class="fa-solid fa-lock"></i> Proceed to Pay <span id="summary-pay-btn-val">₹ 1,57,597</span> <i class="fa-solid fa-chevron-right"></i>
          </button>

          <div style="margin-top: 16px; text-align: center; font-size: 0.76rem; color: var(--text-muted);">
            <i class="fa-solid fa-shield-halved" style="color: var(--emerald-800);"></i> 100% Protected & Insured Transit
          </div>
        </aside>

      </div>
    </div>
  </main>

  <!-- Payment Method Modal / Slide-over (Matching PDF Page 6) -->
  <div class="payment-modal-backdrop" id="payment-modal" aria-hidden="true" role="dialog" aria-modal="true">
    <div class="payment-modal-card">
      
      <div class="payment-modal-header">
        <div>
          <h3>Payment Method</h3>
          <p style="font-size: 0.82rem; color: var(--text-muted);">Choose your preferred payment method</p>
        </div>
        <button class="cart-drawer-close" id="btn-close-payment">&times;</button>
      </div>

      <div class="payment-modal-body">
        <div class="payment-options-accordion">
          <div class="payment-option-item active selected" data-method="Razorpay">
            <div class="payment-option-header">
              <div class="payment-option-left">
                <input type="radio" name="pay_method" value="Razorpay" checked>
                <div class="payment-icon-pill" style="color: #1a42b8;"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                  <div style="font-weight: 700; font-size: 0.95rem;">Pay securely with Razorpay</div>
                  <div style="font-size: 0.78rem; color: var(--text-muted);">UPI, Credit / Debit Card, Net Banking and Wallets</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <p style="margin-top: 14px; font-size: 0.78rem; color: var(--text-muted); line-height: 1.5;">
          You will complete the payment in Razorpay's secure window. KINZ never sees or stores your card, UPI or bank details.
        </p>

      </div>

      <!-- Sticky Payment Modal Footer in Right-Side Panel -->
      <div class="payment-modal-footer">
        <!-- Order Total Preview inside Modal -->
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 14px;">
          <div>
            <div style="font-size: 0.78rem; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Order Total</div>
            <div style="font-size: 0.84rem; color: var(--text-dark);">Total Amount Payable</div>
          </div>
          <div style="font-size: 1.45rem; font-weight: 800; color: var(--emerald-950);" id="modal-pay-total">₹ 1,57,597</div>
        </div>

        <!-- Pay Now Button (Matching PDF Page 6) -->
        <button class="btn-pay-now" id="btn-submit-payment">
          <i class="fa-solid fa-lock"></i> Pay Now <span id="btn-pay-amount">₹ 1,57,597</span>
        </button>

        <div style="margin-top: 10px; text-align: center; font-size: 0.74rem; color: var(--text-muted);">
          <i class="fa-solid fa-shield-halved" style="color: #27824b;"></i> 256-Bit SSL Encrypted & Protected Payment
        </div>
      </div>

    </div>
  </div>

  <!-- Add New Address Modal -->
  <div class="payment-modal-backdrop" id="add-address-modal" aria-hidden="true" role="dialog">
    <div class="payment-modal-card">
      <div class="payment-modal-header">
        <h3>Add New Delivery Address</h3>
        <button class="cart-drawer-close" id="btn-close-add-addr">&times;</button>
      </div>
      <form class="payment-modal-body" id="new-address-form">
        <div style="display: flex; flex-direction: column; gap: 14px;">
          <div>
            <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Tag (e.g. Home, Office, Villa)</label>
            <input type="text" class="form-input-control" id="addr-tag" placeholder="Home" required>
          </div>
          <div>
            <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Recipient Full Name</label>
            <input type="text" class="form-input-control" id="addr-name" placeholder="Full name" required>
          </div>
          <div>
            <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Flat / Street Address</label>
            <input type="text" class="form-input-control" id="addr-line" placeholder="Flat / Building, Street address" required>
          </div>
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
            <div>
              <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">City</label>
              <input type="text" class="form-input-control" id="addr-city" placeholder="City / District" required>
            </div>
            <div>
              <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Pincode</label>
              <input type="text" class="form-input-control" id="addr-pincode" placeholder="6-digit Pincode" required>
            </div>
          </div>
          <div>
            <label style="font-size: 0.82rem; font-weight: 600; display: block; margin-bottom: 4px;">Phone Number</label>
            <input type="tel" class="form-input-control" id="addr-phone" placeholder="+91 9360660773" required>
          </div>
          <button type="submit" class="btn-save-profile" style="width: 100%; margin-top: 10px;">Save & Deliver Here</button>
        </div>
      </form>
    </div>
  </div>

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
<script src="assets/js/checkout.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/checkout.js') ?>"></script>
<script src="assets/js/cart-drawer.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-drawer.js') ?>"></script>
<script src="assets/js/checkout-payment-ui.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/checkout-payment-ui.js') ?>"></script>
<script src="assets/js/kinz-ui.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/kinz-ui.js') ?>"></script>
</body>
</html>
