<?php
require_once __DIR__ . '/includes/cart_helpers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shopping Cart - KINZ Luxury Jewellery</title>
  <meta name="description" content="Review your anti-tarnish gold plated and stainless steel plated jewellery in your KINZ shopping cart. Enjoy insured delivery and easy returns.">
  <link rel="icon" type="image/png" href="assets/images/kinz_logo.png">
  <!-- Google Fonts & FontAwesome -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com">
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700;800;900&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,500;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <style>
    
/* Hide scrollbar on Cart page */
html,
body {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Older Edge */
}

html::-webkit-scrollbar,
body::-webkit-scrollbar {
    display: none; /* Chrome */
}

    .cart-page-container {
      max-width: 1240px;
      margin: 0 auto;
      padding: 36px 24px 64px;
    }
    .cart-page-header {
      margin-bottom: 28px;
    }
    .cart-page-title {
      font-family: var(--font-serif);
      font-size: 2.2rem;
      color: var(--emerald-950);
      margin-bottom: 4px;
    }
    .cart-main-grid {
      display: grid;
      grid-template-columns: 1.35fr 1fr;
      gap: 36px;
    }
    .cart-items-card {
      background: #ffffff;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 24px;
      box-shadow: var(--shadow-sm);
    }
    .cart-empty-view {
      text-align: center;
      padding: 48px 24px;
    }
    .cart-empty-icon {
      font-size: 3.5rem;
      color: var(--emerald-800);
      margin-bottom: 16px;
      opacity: 0.8;
    }
    .cart-summary-card {
      background: #ffffff;
      border: 1px solid var(--border-light);
      border-radius: var(--radius-md);
      padding: 24px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
      border-top: 3px solid var(--gold-500);
      height: fit-content;
      position: sticky;
      top: 90px;
    }
    @media (max-width: 860px) {
      .cart-main-grid {
        grid-template-columns: 1fr;
      }
    }
  


/* Cart item removal confirmation */
.cart-remove-modal {
  position: fixed;
  inset: 0;
  z-index: 9999;
  display: none;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(10, 24, 20, 0.58);
  backdrop-filter: blur(4px);
}
.cart-remove-modal.is-open { display: flex; }
.cart-remove-dialog {
  position: relative;
  width: min(100%, 470px);
  background: #fff;
  border: 1px solid var(--border-gold, #d6bd8c);
  border-radius: 16px;
  box-shadow: 0 24px 70px rgba(0, 0, 0, 0.24);
  padding: 30px;
  animation: cartRemoveEnter .18s ease-out;
}
@keyframes cartRemoveEnter {
  from { opacity: 0; transform: translateY(10px) scale(.985); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
.cart-remove-close {
  position: absolute;
  top: 13px;
  right: 14px;
  width: 34px;
  height: 34px;
  border: 0;
  border-radius: 50%;
  background: #f4f5f2;
  color: var(--emerald-950, #173b32);
  cursor: pointer;
  font-size: 15px;
}
.cart-remove-heading {
  margin: 0 28px 7px 0;
  color: var(--emerald-950, #173b32);
  font-family: var(--font-serif, Georgia, serif);
  font-size: 1.55rem;
  font-weight: 600;
}
.cart-remove-subtitle {
  margin: 0 0 20px;
  color: var(--text-muted, #68716c);
  font-size: .91rem;
  line-height: 1.55;
}
.cart-remove-product {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 12px;
  margin-bottom: 20px;
  border: 1px solid #ece7dc;
  border-radius: 11px;
  background: #fbfaf7;
}
.cart-remove-product img {
  width: 66px;
  height: 66px;
  flex: 0 0 66px;
  object-fit: cover;
  border-radius: 8px;
  background: #f0eee8;
}
.cart-remove-product-name {
  color: var(--emerald-950, #173b32);
  font-size: .92rem;
  font-weight: 700;
  line-height: 1.4;
}
.cart-remove-product-price {
  margin-top: 4px;
  color: var(--emerald-800, #315f50);
  font-size: .88rem;
  font-weight: 700;
}
.cart-remove-actions { display: grid; gap: 10px; }
.cart-remove-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  width: 100%;
  min-height: 45px;
  padding: 11px 16px;
  border-radius: 8px;
  cursor: pointer;
  font: inherit;
  font-size: .9rem;
  font-weight: 700;
  transition: transform .15s ease, opacity .15s ease, background .15s ease;
}
.cart-remove-action:hover { transform: translateY(-1px); }
.cart-remove-action:disabled { opacity: .58; cursor: wait; transform: none; }
.cart-remove-move {
  border: 1px solid var(--emerald-900, #1d493c);
  background: var(--emerald-900, #1d493c);
  color: #fff;
}
.cart-remove-delete {
  border: 1px solid #ead7d3;
  background: #fff;
  color: #9d4036;
}
.cart-remove-cancel {
  min-height: 38px;
  padding: 7px 12px;
  border: 0;
  background: transparent;
  color: var(--text-muted, #68716c);
  font-weight: 600;
}
.cart-remove-feedback {
  display: none;
  margin: 13px 0 0;
  padding: 10px 12px;
  border-radius: 8px;
  background: #fff7e8;
  color: #79521a;
  font-size: .83rem;
  line-height: 1.45;
}
.cart-remove-feedback.is-visible { display: block; }
.cart-remove-signin {
  display: none;
  margin-top: 9px;
  color: var(--emerald-800, #315f50);
  font-size: .88rem;
  font-weight: 700;
  text-decoration: underline;
}
.cart-remove-signin.is-visible { display: inline-block; }
.cart-page-toast {
  position: fixed;
  right: 22px;
  bottom: 22px;
  z-index: 10000;
  max-width: min(390px, calc(100vw - 32px));
  padding: 13px 17px;
  border-radius: 9px;
  background: #173b32;
  color: #fff;
  box-shadow: 0 12px 32px rgba(0,0,0,.18);
  font-size: .88rem;
  line-height: 1.45;
  opacity: 0;
  pointer-events: none;
  transform: translateY(8px);
  transition: opacity .18s ease, transform .18s ease;
}
.cart-page-toast.is-visible { opacity: 1; transform: translateY(0); }
@media (max-width: 520px) {
  .cart-remove-dialog { padding: 25px 20px 20px; }
  .cart-remove-heading { font-size: 1.35rem; }
}

</style>
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

  <main class="cart-page-container">
    <div class="cart-page-header">
      <div style="font-size: 0.82rem; color: var(--text-muted); margin-bottom: 6px;">
        <a href="index.php" style="color: var(--emerald-800);">Home</a> / <span>Shopping Bag</span>
      </div>
      <h1 class="cart-page-title">Your Shopping Bag</h1>
      <p style="font-size: 0.9rem; color: var(--text-muted);">Review your chosen jewellery in the bag panel, then proceed to secure checkout.</p>
      <p style="margin-top:18px;"><a href="shop.php" class="btn-royal-gold" style="display:inline-flex;padding:12px 28px;text-decoration:none;">Continue Shopping</a></p>
    </div>
  </main>

  <!-- Luxury Footer -->
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

  
  
  
  
  
<script src="assets/js/cart-actions.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-actions.js') ?>"></script>
<script>window.KINZ_CART_PAGE = true;</script>
<script src="assets/js/cart-drawer.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-drawer.js') ?>"></script>
<script src="assets/js/kinz-ui.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/kinz-ui.js') ?>"></script>
</body>
</html>
