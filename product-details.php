<?php
require_once __DIR__ . '/includes/store_data.php';
if (empty($_SESSION['kinz_csrf_token'])) { $_SESSION['kinz_csrf_token'] = bin2hex(random_bytes(32)); }

$productId = filter_input(INPUT_GET, 'product_id', FILTER_VALIDATE_INT);
if (!$productId || $productId < 1) {
    http_response_code(404);
    $product = null;
} else {
    $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND status = 'Active' LIMIT 1");
    if ($productStmt) {
        $productStmt->bind_param('i', $productId);
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        $product = $productResult ? $productResult->fetch_assoc() : null;
        $productStmt->close();
    } else {
        $product = null;
    }
    if (!$product) http_response_code(404);
}

if (!$product) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Product Not Found | KINZ</title>
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
      <link rel="stylesheet" href="assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <meta name="kinz-csrf" content="<?= htmlspecialchars($_SESSION['kinz_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
    

</head>
    <body>
      <main class="product-detail-container" style="min-height:60vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;padding:50px 20px;">
        <h1>Product Not Found</h1>
        <p>This product may no longer be available.</p>
        <a href="shop.php" class="btn-card-add" style="display:inline-block;margin-top:18px;">Continue Shopping</a>
      </main>
    
<script src="assets/js/cart-actions.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-actions.js') ?>"></script>
<script>
(function () {
  const productId = <?= (int)$productId ?>;
  const maxStock = <?= (int)$productStock ?>;
  const csrfToken = document.querySelector('meta[name="kinz-csrf"]')?.content || '';
  const quantityLabel = document.getElementById('qty-val');
  let quantity = 1;
  function setQuantity(next) {
    quantity = Math.max(1, Math.min(maxStock || 1, next));
    if (quantityLabel) quantityLabel.textContent = String(quantity);
  }
  document.getElementById('qty-minus')?.addEventListener('click', function () { setQuantity(quantity - 1); });
  document.getElementById('qty-plus')?.addEventListener('click', function () { setQuantity(quantity + 1); });
  async function addToBag(buyNow) {
    if (maxStock < 1) { alert('This product is currently out of stock.'); return; }
    const button = buyNow ? document.getElementById('btn-action-buynow') : document.getElementById('btn-action-addcart');
    if (button) button.disabled = true;
    try {
      const body = new URLSearchParams({ action: 'add', product_id: String(productId), quantity: String(quantity) });
      const response = await fetch('cart_api.php', { method: 'POST', credentials: 'same-origin', body: body, headers: { 'X-CSRF-Token': csrfToken } });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Unable to add this product.');
      document.querySelectorAll('.cart-badge-count').forEach(function (badge) {
        badge.textContent = String(data.count || 0);
        badge.style.display = data.count > 0 ? 'inline-flex' : 'none';
      });
      if (buyNow) window.location.href = 'checkout.php';
      else alert('Product added to your shopping bag.');
    } catch (error) {
      alert(error.message);
    } finally {
      if (button) button.disabled = false;
    }
  }
  document.getElementById('btn-action-addcart')?.addEventListener('click', function () { addToBag(false); });
  document.getElementById('btn-action-buynow')?.addEventListener('click', function () { addToBag(true); });
})();
</script>



</body>
    </html>
    <?php
    exit;
}

$productTitle = (string)($product['title'] ?? 'KINZ Product');
$productCategory = (string)($product['category'] ?? 'Jewellery');
$productSku = (string)($product['sku'] ?? '');
$productCode = (string)($product['prod_code'] ?? '');
$productMetalType = (string)($product['metal_type'] ?? '');
$productOccasion = (string)($product['occasion'] ?? 'Daily Wear');
$productGuarantee = (string)($product['guarantee'] ?? '');
$productStock = (int)($product['stock'] ?? 0);
$productDescription = trim((string)($product['description'] ?? ''));
$basePrice = (float)($product['base_price'] ?? 0) + (float)($product['making_charges'] ?? 0);
$discountPct = max(0, min(100, (int)($product['discount_pct'] ?? 0)));
$salePrice = $basePrice * (1 - ($discountPct / 100));

$productImages = [];
if (!empty($product['images'])) {
    $decodedImages = json_decode((string)$product['images'], true);
    if (is_array($decodedImages)) {
        foreach ($decodedImages as $imagePath) {
            $imagePath = trim((string)$imagePath);
            if ($imagePath !== '') $productImages[] = $imagePath;
        }
    }
}
$primaryImage = trim((string)($product['image'] ?? ''));
if ($primaryImage !== '' && !in_array($primaryImage, $productImages, true)) {
    array_unshift($productImages, $primaryImage);
}
$productImages = array_values(array_unique($productImages));
if (!$productImages) $productImages[] = 'assets/images/kinz_logo.png';
$productImageUrls = array_map('kinz_image_url', $productImages);
$mainImageUrl = $productImageUrls[0];

$averageRating = 0;
$reviewCount = 0;
$productReviews = [];
$reviewStmt = $conn->prepare("SELECT AVG(rating) AS average_rating, COUNT(*) AS review_count FROM reviews WHERE product_id = ? AND status = 'Approved'");
if ($reviewStmt) {
    $reviewStmt->bind_param('i', $productId);
    $reviewStmt->execute();
    $reviewData = $reviewStmt->get_result()->fetch_assoc();
    $averageRating = (float)($reviewData['average_rating'] ?? 0);
    $reviewCount = (int)($reviewData['review_count'] ?? 0);
    $reviewStmt->close();
}
$productReviewsStmt = $conn->prepare("SELECT customer_name, rating, title, review_text, created_at, verified_purchase FROM reviews WHERE product_id = ? AND status = 'Approved' ORDER BY created_at DESC, id DESC LIMIT 12");
if ($productReviewsStmt) {
    $productReviewsStmt->bind_param('i', $productId);
    $productReviewsStmt->execute();
    $productReviewsResult = $productReviewsStmt->get_result();
    while ($productReviewsResult && ($reviewRow = $productReviewsResult->fetch_assoc())) $productReviews[] = $reviewRow;
    $productReviewsStmt->close();
}

$relatedProducts = [];
$relatedStmt = $conn->prepare("SELECT p.*, COALESCE((SELECT AVG(r.rating) FROM reviews r WHERE r.product_id = p.id AND r.status = 'Approved'), 0) AS average_rating, (SELECT COUNT(*) FROM reviews r WHERE r.product_id = p.id AND r.status = 'Approved') AS review_count FROM products p WHERE p.status = 'Active' AND p.id <> ? ORDER BY (LOWER(p.category) = LOWER(?)) DESC, p.created_at DESC, p.id DESC LIMIT 4");
if ($relatedStmt) {
    $relatedStmt->bind_param('is', $productId, $productCategory);
    $relatedStmt->execute();
    $relatedResult = $relatedStmt->get_result();
    if ($relatedResult) while ($relatedRow = $relatedResult->fetch_assoc()) $relatedProducts[] = $relatedRow;
    $relatedStmt->close();
}

$formattedSalePrice = '₹ ' . number_format($salePrice, 0);
$formattedBasePrice = '₹ ' . number_format($basePrice, 0);
$formattedEmi = '₹ ' . number_format(max(1, ceil($salePrice / 24)), 0) . '/month';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= kinz_escape($productTitle) ?> | KINZ - A Hidden Treasure</title>
  <meta name="description" content="<?= kinz_escape($productDescription !== '' ? $productDescription : $productTitle) ?>">
  
  <link rel="icon" type="image/png" href="assets/images/kinz_logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= (int)@filemtime(__DIR__ . '/assets/css/style.css') ?>">
  <meta name="kinz-csrf" content="<?= htmlspecialchars($_SESSION['kinz_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
</head>
<body>
 
  <!-- Header -->
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
          <li><a href="shop.php" class="nav-link active" data-nav="shop" data-testid="nav-shop">Shop</a></li>
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
    <div class="product-detail-container">
      
      <!-- Breadcrumb (Home > Rings > Product) -->
      <nav class="breadcrumb-nav" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <a href="shop.php" id="bc-category-link"><?= kinz_escape($productCategory) ?></a>
        <i class="fa-solid fa-chevron-right" style="font-size: 0.65rem;"></i>
        <span id="bc-product-name"><?= kinz_escape($productTitle) ?></span>
      </nav>

      <!-- Main Product Detail Grid (Gallery Left + Info Right) -->
      <div class="product-detail-grid">
        
        <!-- Left: Image Gallery Showcase (Flipkart Vertical Thumbnails + Centered Display + Sticky Actions) -->
        <div class="gallery-showcase">
          
          <div class="gallery-media-row">
            <!-- Vertical Thumbnail Column (All Product Perspectives) -->
            <div class="gallery-thumbs-col" id="detail-gallery-thumbs">
              <?php foreach ($productImageUrls as $imageIndex => $imageUrl): ?>
                <button type="button" class="gallery-thumb-btn<?= $imageIndex === 0 ? ' active' : '' ?>" data-gallery-index="<?= (int)$imageIndex ?>" aria-label="View product image <?= (int)($imageIndex + 1) ?>">
                  <img src="<?= kinz_escape($imageUrl) ?>" alt="<?= kinz_escape($productTitle) ?> image <?= (int)($imageIndex + 1) ?>" loading="lazy">
                </button>
              <?php endforeach; ?>
            </div>

            <!-- Main Zoom Display with Angle Indicators & View Controls -->
            <div class="gallery-main-column">
              
              <div class="gallery-main-display" id="detail-main-display">
                <!-- Hanging Offer Tag on Main Zoom Display (Top-Left) -->
                <div class="hanging-offer-tag" id="detail-hanging-tag" style="<?= $discountPct > 0 ? '' : 'display:none;' ?>">
                  <div class="hanging-offer-tag-body" id="detail-hanging-tag-text">
                    <span class="tag-percent"><?= (int)$discountPct ?>%</span>
                    <span class="tag-off">OFF</span>
                  </div>
                </div>

                <!-- Wishlist Floating Round Button (Top-Right) -->
                <button class="gallery-wishlist-btn" id="btn-gallery-wishlist" type="button" title="Add to Wishlist" aria-label="Add to Wishlist">
                  <i class="fa-regular fa-heart"></i>
                </button>

                <!-- Real-time View Angle Indicator Badge (Bottom-Left) -->
                <div class="gallery-angle-indicator" id="gallery-angle-indicator">
                  <i class="fa-solid fa-camera"></i> <span id="gallery-angle-text">Image 1 of <?= count($productImageUrls) ?></span>
                </div>

                <!-- Inspect in Ultra-HD Trigger Button (Bottom-Right) -->
                <button class="btn-open-inspect-modal" id="btn-open-inspect-modal" type="button" title="Inspect in Ultra-HD">
                  <i class="fa-solid fa-expand"></i> Inspect in Ultra-HD
                </button>

                <!-- Main Full-Bleed Product Image -->
                <img src="<?= kinz_escape($mainImageUrl) ?>" alt="<?= kinz_escape($productTitle) ?>" class="gallery-main-img" id="detail-hero-img">

                <!-- Navigation Arrows (Both placed above image) -->
                <button class="gallery-nav-arrow prev" id="btn-gallery-prev" aria-label="Previous image">
                  <i class="fa-solid fa-chevron-left"></i>
                </button>

                <button class="gallery-nav-arrow next" id="btn-gallery-next" aria-label="Next image">
                  <i class="fa-solid fa-chevron-right"></i>
                </button>

              </div>

              <!-- Dynamic Perspective Caption -->
              <div class="gallery-view-caption" id="gallery-view-caption">
                <i class="fa-solid fa-circle-check" style="color: var(--antique-copper);"></i>
                <span id="gallery-caption-text"><?= kinz_escape($productTitle) ?></span>
              </div>

            </div>

          </div>

          <!-- PRIMARY ACTION BUTTONS: Placed DIRECTLY under the image gallery (Flipkart canonical layout) -->
          <div class="fk-actions-row gallery-actions-row">
            <button type="button" class="btn-fk-cart" id="btn-action-addcart" <?= $productStock < 1 ? 'disabled' : '' ?>>
              <i class="fa-solid fa-bag-shopping"></i> ADD TO CART
            </button>
            <button type="button" class="btn-fk-buy" id="btn-action-buynow" <?= $productStock < 1 ? 'disabled' : '' ?>>
              <i class="fa-solid fa-bolt"></i> BUY NOW
            </button>
          </div>

        </div>

        <!-- Right: Product Specifications & Details (Flipkart Standard Features) -->
        <div class="product-detail-info">
          
          <h1 class="detail-product-title" id="detail-title"><?= kinz_escape($productTitle) ?></h1>
          <p class="detail-product-subtitle" id="detail-subtitle" style="color: #666; font-size: 0.95rem; margin-top: -6px; margin-bottom: 12px;"><?= kinz_escape($productMetalType) ?></p>
          
          <!-- Flipkart Rating & Assured Badge Row -->
          <div class="fk-rating-row">
            <?php if ($reviewCount > 0): ?>
              <span class="fk-rating-badge" id="fk-rating-badge"><?= number_format($averageRating, 1) ?> <i class="fa-solid fa-star"></i></span>
              <span class="fk-ratings-count" id="detail-reviews-count">(<?= (int)$reviewCount ?> <?= $reviewCount === 1 ? 'review' : 'reviews' ?>)</span>
            <?php else: ?>
              <span class="fk-ratings-count" id="detail-reviews-count">No ratings yet</span>
            <?php endif; ?>
            <span class="fk-assured-badge"><i class="fa-solid fa-shield-halved"></i> KINZ Assured</span>
            <span style="font-size: 0.8rem; color: #999; margin-left: auto;" id="detail-sku">SKU: <?= kinz_escape($productSku) ?></span>
          </div>

          <!-- Flipkart Price Row -->
          <div class="fk-price-box">
            <span class="fk-price-sale" id="detail-price"><?= kinz_escape($formattedSalePrice) ?></span>
            <span class="fk-price-orig" id="detail-orig-price" style="<?= $discountPct > 0 ? '' : 'display:none;' ?>"><?= kinz_escape($formattedBasePrice) ?></span>
            <span class="fk-discount-callout" id="detail-badge-pill" style="<?= $discountPct > 0 ? '' : 'display:none;' ?>"><?= (int)$discountPct ?>% OFF</span>
          </div>

          <!-- Flipkart EMI Info Banner -->
          <div class="fk-emi-banner">
            <i class="fa-solid fa-credit-card"></i>
            <span><strong>No Cost EMI</strong> starting from <span id="fk-emi-amount"><?= kinz_escape($formattedEmi) ?></span> • <a href="#product-description" style="color: #2874f0; font-weight: 600;">View Plans</a></span>
          </div>

          <!-- Size & Quantity Selection Box -->
          <div class="fk-selection-card">
            <div class="detail-options-group" style="margin-bottom: 0;">
              <span class="option-group-label" style="display: block; margin-bottom: 6px;">Product Code:</span>
              <div class="size-pill-row" id="detail-sizes-row">
                <span class="size-pill-btn active"><?= kinz_escape($productCode) ?></span>
              </div>
            </div>

            <div class="qty-detail-row" style="margin-bottom: 0; margin-top: 12px;">
              <span class="option-group-label" style="margin-bottom: 0; margin-right: 12px;">Quantity:</span>
              <div class="qty-spinner">
                <button type="button" class="qty-btn" id="qty-minus">-</button>
                <span class="qty-value" id="qty-val">1</span>
                <button type="button" class="qty-btn" id="qty-plus">+</button>
              </div>
            </div>
            <div style="margin-top:10px;font-size:0.85rem;color:<?= $productStock > 0 ? '#388e3c' : '#c62828' ?>;" id="detail-stock-visible">
              <?= $productStock > 0 ? 'In stock - ' . (int)$productStock . ' available' : 'Currently out of stock' ?>
            </div>
          </div>

          <!-- Flipkart Available Offers Section -->
          <div class="fk-offers-card">
            <div class="fk-offers-title">
              <i class="fa-solid fa-tags" style="color: #388e3c;"></i> Available Offers
            </div>
            <div class="fk-offer-item">
              <i class="fa-solid fa-tag"></i>
              <div><strong>Bank Offer:</strong> 10% Instant Discount up to ₹1,500 on HDFC / ICICI Bank Credit Cards. <span class="fk-tc-link">T&C</span></div>
            </div>
            <div class="fk-offer-item">
              <i class="fa-solid fa-tag"></i>
              <div><strong>Special Price:</strong> Extra discount applied at checkout on festival purchase. <span class="fk-tc-link">T&C</span></div>
            </div>
            <div class="fk-offer-item">
              <i class="fa-solid fa-tag"></i>
              <div><strong>Exchange Offer:</strong> 100% Lifetime Anti-Tarnish Guarantee: Waterproof, Sweatproof &amp; Hypoallergenic. <span class="fk-tc-link">T&C</span></div>
            </div>
            <div class="fk-offer-item">
              <i class="fa-solid fa-tag"></i>
              <div><strong>Partner Privilege:</strong> Free insured express shipping with tamper-evident seal. <span class="fk-tc-link">T&C</span></div>
            </div>
          </div>

          <!-- Flipkart Delivery & Pincode Checker -->
          <div class="fk-delivery-card">
            <div class="fk-pincode-header">
              <span style="font-weight: 600; font-size: 0.9rem; color: #333;"><i class="fa-solid fa-location-dot" style="color: var(--emerald-800); margin-right: 6px;"></i> Delivery Options</span>
            </div>
            <div class="fk-pincode-input-row">
              <input type="text" class="fk-pincode-input" id="fk-pincode-input" placeholder="Enter Pincode" maxlength="6" value="600001">
              <button type="button" class="fk-pincode-btn" id="btn-check-pincode">Check</button>
            </div>
            <div class="fk-delivery-status" id="fk-delivery-status">
              <i class="fa-solid fa-truck-fast" style="color: #388e3c; margin-right: 6px;"></i>
              Delivery by <strong id="fk-delivery-date">Tomorrow, 5 PM</strong> | <span class="fk-delivery-free">FREE</span> <span style="text-decoration: line-through; color: #878787;">₹40</span><br>
              <small style="color: #666;"><i class="fa-regular fa-circle-check" style="color: #388e3c;"></i> Cash on Delivery available • 7 Days Replacement & Resizing Policy</small>
            </div>
          </div>

          <!-- Flipkart Highlights Grid -->
          <div class="fk-highlights-card">
            <div class="fk-highlights-title">Highlights</div>
            <div class="fk-highlights-grid">
              <div class="fk-hl-label">Metal Type</div>
              <div class="fk-hl-value" id="hl-material"><?= kinz_escape($productMetalType !== '' ? $productMetalType : 'Not specified') ?></div>
              <div class="fk-hl-label">Guarantee</div>
              <div class="fk-hl-value" id="hl-purity"><?= kinz_escape($productGuarantee !== '' ? $productGuarantee : 'Not specified') ?></div>
              <div class="fk-hl-label">Occasion</div>
              <div class="fk-hl-value" id="hl-occasion"><?= kinz_escape($productOccasion) ?></div>
              <div class="fk-hl-label">Category</div>
              <div class="fk-hl-value" id="hl-ideal"><?= kinz_escape($productCategory) ?></div>
              <div class="fk-hl-label">Product Code</div>
              <div class="fk-hl-value" id="hl-cert"><?= kinz_escape($productCode) ?></div>
              <div class="fk-hl-label">Stock</div>
              <div class="fk-hl-value"><?= $productStock > 0 ? (int)$productStock . ' available' : 'Out of stock' ?></div>
            </div>
          </div>

          <!-- Hidden spec elements to maintain full backward compatibility with any tests/scripts -->
          <div style="display:none;">
            <span id="spec-material"><?= kinz_escape($productMetalType) ?></span>
            <span id="spec-metal"><?= kinz_escape($productMetalType) ?></span>
            <span id="spec-purity"><?= kinz_escape($productGuarantee) ?></span>
            <span id="spec-occasion"><?= kinz_escape($productOccasion) ?></span>
            <span id="detail-stock-status"><?= $productStock > 0 ? 'In Stock' : 'Out of Stock' ?></span>
            <span id="detail-delivery-time">Delivery: 2–5 Days</span>
          </div>

          <!-- Wishlist Action -->
          <div style="display: flex; gap: 16px; margin-top: 8px;">
            <button class="btn-detail-wishlist" id="btn-action-wishlist" style="flex: 1;">
              <i class="fa-regular fa-heart"></i> Add to Wishlist
            </button>
          </div>

        </div>

      </div>

      <!-- Product Description Card -->
      <section class="kinz-description-card" id="product-description">
        <div class="kinz-description-heading">
          <span class="kinz-description-icon" aria-hidden="true"><i class="fa-regular fa-gem"></i></span>
          <h2>Product Description</h2>
        </div>
        <p class="kinz-description-text" id="detail-description-text"><?= nl2br(kinz_escape($productDescription !== '' ? $productDescription : 'Product description has not been added yet.')) ?></p>
      </section>

      <section class="kinz-customer-reviews" id="customer-reviews">
        <div class="kinz-reviews-heading">
          <div>
            <h2>Customer Reviews</h2>
            <p class="kinz-reviews-sub">Feedback from customers who purchased this product.</p>
          </div>
          <?php if ($reviewCount > 0): ?>
            <div class="kinz-reviews-score" aria-label="Average rating <?= number_format($averageRating, 1) ?> out of 5">
              <span class="kinz-reviews-score-num"><?= number_format($averageRating, 1) ?></span>
              <div class="kinz-reviews-score-meta">
                <span class="kinz-review-stars"><?= str_repeat('★', (int)round($averageRating)) . str_repeat('☆', 5 - (int)round($averageRating)) ?></span>
                <span class="kinz-reviews-count"><?= (int)$reviewCount ?> <?= $reviewCount === 1 ? 'review' : 'reviews' ?></span>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <?php if ($productReviews): ?>
          <div class="kinz-reviews-grid">
          <?php foreach ($productReviews as $customerReview):
            $customerRating = max(1, min(5, (int)$customerReview['rating']));
            $reviewDateLabel = !empty($customerReview['created_at']) ? date('d M Y', strtotime($customerReview['created_at'])) : '';
            $reviewerName = trim((string)$customerReview['customer_name']);
            $reviewerInitial = $reviewerName !== '' ? strtoupper(function_exists('mb_substr') ? mb_substr($reviewerName, 0, 1, 'UTF-8') : substr($reviewerName, 0, 1)) : 'K';
          ?>
            <article class="kinz-review-card">
              <header class="kinz-review-top">
                <div class="kinz-review-avatar" aria-hidden="true"><?= kinz_escape($reviewerInitial) ?></div>
                <div class="kinz-review-who">
                  <span class="kinz-review-customer"><?= kinz_escape($reviewerName !== '' ? $reviewerName : 'KINZ Customer') ?></span>
                  <?php if (!empty($customerReview['verified_purchase'])): ?><span class="kinz-verified-purchase"><i class="fa-solid fa-circle-check"></i> Verified Purchase</span><?php endif; ?>
                </div>
                <span class="kinz-review-meta"><?= kinz_escape($reviewDateLabel) ?></span>
              </header>
              <div class="kinz-review-stars" aria-label="<?= $customerRating ?> out of 5 stars"><?= str_repeat('★', $customerRating) . str_repeat('☆', 5 - $customerRating) ?></div>
              <?php if (trim((string)$customerReview['title']) !== ''): ?><h3 class="kinz-review-title"><?= kinz_escape($customerReview['title']) ?></h3><?php endif; ?>
              <?php if (trim((string)$customerReview['review_text']) !== ''): ?><p class="kinz-review-text"><?= kinz_escape($customerReview['review_text']) ?></p><?php endif; ?>
            </article>
          <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="kinz-no-reviews"><i class="fa-regular fa-star"></i> No customer reviews yet. After your order is delivered, you can share your rating and feedback here.</div>
        <?php endif; ?>
      </section>

      <!-- "You May Also Like" Carousel / Grid (Matching PDF Page 3) -->
      <section class="related-products-section" style="margin-top: 48px;">
        <div class="section-header-row">
          <h2 class="section-title">You May Also Like</h2>
          <a href="shop.php" class="view-all-link">View All <i class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="product-grid" id="detail-you-may-like-grid">
          <?php foreach ($relatedProducts as $relatedProduct) kinz_product_card($relatedProduct); ?>
          <?php if (empty($relatedProducts)): ?><p>No other products are available right now.</p><?php endif; ?>
        </div>
      </section>

    </div>
  </main>

  <!-- Footer (Matching PDF Page 1) -->
  <footer class="kinz-footer">
    <div class="footer-top-container">
      <div class="footer-brand-col">
        <a href="index.php" class="brand-logo" style="margin-bottom: 12px;" aria-label="KINZ Home">
          <img src="assets/images/kinz_logo.png" alt="KINZ Luxury Jewellery" class="brand-logo-img">
        </a>
        <p>Anti-tarnish gold plated and stainless steel plated jewellery—waterproof, skin-friendly and made for daily wear, festive and party looks.</p>
      </div>

      <div>
        <h4 class="footer-col-title">Shop</h4>
        <ul class="footer-links-list">
          <li><a href="shop.php">All Jewellery</a></li>
          <li><a href="shop.php?category=rings">Rings</a></li>
          <li><a href="shop.php?category=necklaces">Necklaces</a></li>
          <li><a href="shop.php?category=earrings">Earrings</a></li>
          <li><a href="shop.php?category=bracelets">Bracelets</a></li>
          <li><a href="shop.php?category=kids">Kids Accessories</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer-col-title">Categories</h4>
        <ul class="footer-links-list">
          <li><a href="shop.php?metal=Water%20Resistant">Water Resistant</a></li>
          <li><a href="shop.php?metal=Anti-Tarnish">Anti-Tarnish</a></li>
          <li><a href="shop.php?metal=Gold%20Plated">Gold Plated</a></li>
          <li><a href="shop.php?metal=Stainless%20Steel">Stainless Steel</a></li>
          <li><a href="shop.php?occasion=Party">Party Wear</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer-col-title">About</h4>
        <ul class="footer-links-list">
          <li><a href="about.html">Our Story</a></li>
          <li><a href="about.html#blog">Blog</a></li>
          <li><a href="about.html#careers">Careers</a></li>
        </ul>
      </div>

      <div>
        <h4 class="footer-col-title">Contact</h4>
        <div class="footer-contact-item"><i class="fa-regular fa-envelope"></i><a href="mailto:Kinzjewellery0@gmail.com" style="color:inherit; text-decoration:none;">Kinzjewellery0@gmail.com</a></div>
        <div class="footer-contact-item"><i class="fa-solid fa-phone"></i><a href="tel:9360660773" style="color:inherit;">+91 9360660773</a></div>
        <h4 class="footer-col-title" style="margin-top: 18px; margin-bottom: 10px;">Follow Us</h4>
        <div class="footer-social-row">
          <a href="https://www.instagram.com/kinz_jewellery?rpxt=NG04MGl6dmNjdmUz" target="_blank" rel="noopener noreferrer" class="social-icon-circle" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="social-icon-circle"><i class="fa-brands fa-facebook-f"></i></a>
          <a href="#" class="social-icon-circle"><i class="fa-brands fa-youtube"></i></a>
        </div>
      </div>
    </div>

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

  
  
  
  
  

  <!-- Ultra-HD Product Inspection Lightbox Modal -->
  <div class="product-inspection-modal" id="product-inspection-modal" role="dialog" aria-modal="true" aria-hidden="true">
    <div class="inspection-modal-backdrop" id="inspection-modal-backdrop"></div>
    <div class="inspection-modal-dialog">
      <div class="inspection-modal-header">
        <div class="inspection-modal-title-wrap">
          <span class="inspection-badge"><i class="fa-solid fa-gem"></i> Ultra-HD Inspector</span>
          <h3 class="inspection-title" id="inspect-modal-title">Product Inspection</h3>
        </div>
        <div class="inspection-zoom-controls">
          <button type="button" class="inspect-btn-zoom active" data-zoom="1" id="inspect-zoom-1x" title="1x Natural View">1x View</button>
          <button type="button" class="inspect-btn-zoom" data-zoom="2" id="inspect-zoom-2x" title="2x Close-up">2x Zoom</button>
          <button type="button" class="inspect-btn-zoom" data-zoom="3" id="inspect-zoom-3x" title="3x Finish Inspection">3x Zoom</button>
          <button type="button" class="inspect-btn-tool" id="inspect-zoom-in" title="Zoom In"><i class="fa-solid fa-plus"></i></button>
          <button type="button" class="inspect-btn-tool" id="inspect-zoom-out" title="Zoom Out"><i class="fa-solid fa-minus"></i></button>
          <button type="button" class="inspect-btn-tool" id="inspect-reset" title="Reset View"><i class="fa-solid fa-arrows-rotate"></i></button>
        </div>
        <button type="button" class="inspection-modal-close" id="btn-close-inspect-modal" aria-label="Close Inspector">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <div class="inspection-viewport" id="inspection-viewport">
        <img src="" alt="" class="inspection-zoom-img" id="inspection-zoom-img">
        <div class="inspection-hud">
          <span id="inspect-hud-scale"><i class="fa-solid fa-magnifying-glass"></i> 1.8x Magnification</span>
          <span class="inspect-hud-hint"><i class="fa-solid fa-hand"></i> Drag to Pan | Scroll wheel to Zoom</span>
        </div>
      </div>
    </div>
  </div>

<script>
(function () {
  const images = <?= json_encode($productImageUrls, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
  const hero = document.getElementById('detail-hero-img');
  const thumbs = Array.from(document.querySelectorAll('#detail-gallery-thumbs .gallery-thumb-btn'));
  const angleText = document.getElementById('gallery-angle-text');
  const captionText = document.getElementById('gallery-caption-text');
  const inspectImage = document.getElementById('inspection-zoom-img');
  const inspectTitle = document.getElementById('inspect-modal-title');
  let activeIndex = 0;

  function showImage(index) {
    if (!images.length || !hero) return;
    activeIndex = (index + images.length) % images.length;
    hero.src = images[activeIndex];
    hero.alt = <?= json_encode($productTitle, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    if (inspectImage) {
      inspectImage.src = images[activeIndex];
      inspectImage.alt = hero.alt;
    }
    if (inspectTitle) inspectTitle.textContent = hero.alt;
    thumbs.forEach((button, buttonIndex) => {
      button.classList.toggle('active', buttonIndex === activeIndex);
    });
    if (angleText) angleText.textContent = 'Image ' + (activeIndex + 1) + ' of ' + images.length;
    if (captionText) captionText.textContent = hero.alt + ' - Image ' + (activeIndex + 1);
  }

  thumbs.forEach((button) => {
    button.addEventListener('click', () => showImage(Number(button.dataset.galleryIndex || 0)));
  });
  const previous = document.getElementById('btn-gallery-prev');
  const next = document.getElementById('btn-gallery-next');
  if (previous) previous.addEventListener('click', () => showImage(activeIndex - 1));
  if (next) next.addEventListener('click', () => showImage(activeIndex + 1));
  showImage(0);
})();
</script>



<script>
(function () {
  const productId = <?= (int)$productId ?>;
  const maxStock = <?= (int)$productStock ?>;
  const csrfToken = document.querySelector('meta[name="kinz-csrf"]')?.content || '';
  const quantityLabel = document.getElementById('qty-val');
  let quantity = 1;
  function setQuantity(next) {
    quantity = Math.max(1, Math.min(maxStock || 1, next));
    if (quantityLabel) quantityLabel.textContent = String(quantity);
  }
  document.getElementById('qty-minus')?.addEventListener('click', function () { setQuantity(quantity - 1); });
  document.getElementById('qty-plus')?.addEventListener('click', function () { setQuantity(quantity + 1); });
  async function addToBag(buyNow) {
    if (maxStock < 1) { alert('This product is currently out of stock.'); return; }
    const button = buyNow ? document.getElementById('btn-action-buynow') : document.getElementById('btn-action-addcart');
    if (button) button.disabled = true;
    try {
      const body = new URLSearchParams({ action: 'add', product_id: String(productId), quantity: String(quantity) });
      const response = await fetch('cart_api.php', { method: 'POST', credentials: 'same-origin', body: body, headers: { 'X-CSRF-Token': csrfToken } });
      const data = await response.json();
      if (!response.ok || !data.success) throw new Error(data.message || 'Unable to add this product.');
      document.querySelectorAll('.cart-badge-count').forEach(function (badge) {
        badge.textContent = String(data.count || 0);
        badge.style.display = data.count > 0 ? 'inline-flex' : 'none';
      });
      if (buyNow) window.location.href = 'checkout.php';
      else alert('Product added to your shopping bag.');
    } catch (error) {
      alert(error.message);
    } finally {
      if (button) button.disabled = false;
    }
  }
  document.getElementById('btn-action-addcart')?.addEventListener('click', function () { addToBag(false); });
  document.getElementById('btn-action-buynow')?.addEventListener('click', function () { addToBag(true); });
})();
</script>


<script>
(function () {
  const productId = <?= (int)$productId ?>;
  const csrfToken = document.querySelector('meta[name="kinz-csrf"]')?.content || '';
  const buttons = [document.getElementById('btn-gallery-wishlist'), document.getElementById('btn-action-wishlist')].filter(Boolean);
  let saved = false;
  function paint() {
    buttons.forEach(button => {
      button.type = 'button';
      button.classList.toggle('active', saved);
      if (button.id === 'btn-action-wishlist') {
        button.innerHTML = saved ? '<i class="fa-solid fa-heart"></i> Remove from Wishlist' : '<i class="fa-regular fa-heart"></i> Add to Wishlist';
      } else {
        button.innerHTML = saved ? '<i class="fa-solid fa-heart"></i>' : '<i class="fa-regular fa-heart"></i>';
        button.setAttribute('aria-label', saved ? 'Remove from Wishlist' : 'Add to Wishlist');
      }
    });
  }
  async function checkStatus() {
    try {
      const response = await fetch('account_api.php?action=wishlist_status&product_id=' + encodeURIComponent(productId), { credentials: 'same-origin' });
      const data = await response.json();
      if (response.ok && data.success) { saved = !!data.saved; paint(); }
    } catch (_) {}
  }
  async function toggle() {
    try {
      const body = new URLSearchParams({ action: 'wishlist_toggle', product_id: String(productId), csrf_token: csrfToken });
      const response = await fetch('account_api.php', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrfToken }, body });
      const data = await response.json();
      if (response.status === 401) {
        alert('Please sign in to save products to your wishlist.');
        window.location.href = 'login.php';
        return;
      }
      if (!response.ok || !data.success) throw new Error(data.message || 'Unable to update wishlist.');
      saved = !!data.saved;
      paint();
      window.dispatchEvent(new Event('kinz:wishlist-updated'));
      window.KINZToast.show(data.message || 'Wishlist updated.', saved ? 'add' : 'remove');
    } catch (error) { window.KINZToast.show(error.message, 'error'); }
  }
  buttons.forEach(button => button.addEventListener('click', toggle));
  checkStatus();
})();
</script>

<script src="assets/js/cart-actions.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-actions.js') ?>"></script>
<script src="assets/js/cart-drawer.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-drawer.js') ?>"></script>
<script src="assets/js/kinz-ui.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/kinz-ui.js') ?>"></script>
</body>