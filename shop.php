<?php
require_once __DIR__ . '/includes/store_data.php';
$kinzCategories = kinz_get_categories($conn);
$selectedSlug = trim($_GET['category'] ?? '');
$selectedCategory = '';
foreach ($kinzCategories as $category) {
    if (strcasecmp($category['slug'], $selectedSlug) === 0) { $selectedCategory = $category['name']; break; }
}
$searchQuery = trim((string)($_GET['q'] ?? ''));
$sort = $_GET['sort'] ?? 'recommended';
$allowedSorts = ['recommended', 'newest', 'ordered', 'discount', 'price-asc', 'price-desc', 'rating'];
if (!in_array($sort, $allowedSorts, true)) $sort = 'recommended';
$kinzProducts = kinz_get_products($conn, '', 0, 'newest');
$kinzCollectionIds = kinz_get_collection_product_ids($conn);
$categorySlugMap = [];
foreach ($kinzCategories as $category) {
    $categorySlugMap[kinz_normalize_category_key($category['name'])] = $category['slug'];
    $categorySlugMap[kinz_normalize_category_key($category['slug'])] = $category['slug'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Shop All Jewellery | KINZ - A Hidden Treasure</title>
  <meta name="description" content="Explore anti-tarnish gold plated and stainless steel plated rings, necklaces, bangles and earrings for daily wear, festive and party wear at KINZ.">
  
  <link rel="icon" type="image/png" href="assets/images/kinz_logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) @filemtime(__DIR__ . '/assets/css/style.css') ?>">
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
          <input type="text" class="search-input" id="site-search-input" placeholder="Search for jewellery..." autocomplete="off" value="<?= htmlspecialchars($searchQuery, ENT_QUOTES, 'UTF-8') ?>">
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
    <!-- Shop Showcase Carousel Section -->
    <section class="shop-carousel-section" id="shop-carousel" aria-label="Shop Collection Showcase">
      <div class="shop-carousel-track">
        
        <!-- Slide 1: Royal Anti-Tarnish Luxury Collection -->
        <div class="shop-carousel-slide active" style="background-image:url('assets/images/hero_carousel_1.jpg');">
          <div class="shop-slide-content">
            <span class="shop-slide-eyebrow"><i class="fa-solid fa-crown" style="color: var(--antique-copper);"></i> ANTI-TARNISH LUXURY VAULT</span>
            <h1 class="shop-slide-title">Anti-Tarnish Jewellery Collection</h1>
            <p class="shop-slide-subtitle">Waterproof necklaces, anti-tarnish filigree kadas and sparkling statement pieces in gold plated and stainless steel plated finishes.</p>
            <div class="shop-slide-cta-row">
              <button type="button" class="btn-royal-gold" onclick="document.querySelector('.metal-pill[data-metal=\'Anti-Tarnish\']').click(); scrollToCatalogSection();">Explore Anti-Tarnish <i class="fa-solid fa-arrow-right"></i></button>
              <button type="button" class="btn-royal-outline" onclick="const chk=document.querySelector('input[name=\'cat\'][value=\'necklaces\']'); if(chk){chk.checked=true; chk.dispatchEvent(new Event('change')); scrollToCatalogSection();}">Temple Necklaces</button>
            </div>
          </div>
        </div>

        <!-- Slide 2: Festive & Party Wear -->
        <div class="shop-carousel-slide" style="background-image:url('assets/images/hero_carousel_2.jpg');">
          <div class="shop-slide-content">
            <span class="shop-slide-eyebrow"><i class="fa-solid fa-gem" style="color: var(--antique-copper);"></i> FESTIVE &amp; PARTY WEAR</span>
            <h2 class="shop-slide-title">Festive &amp; Party Wear Jewellery</h2>
            <p class="shop-slide-subtitle">Explore sparkling gold plated and stainless steel plated pieces made to shine at celebrations and parties.</p>
            <div class="shop-slide-cta-row">
              <button type="button" class="btn-royal-gold" onclick="scrollToCatalogSection();">Party Wear <i class="fa-solid fa-arrow-right"></i></button>
              <button type="button" class="btn-royal-outline" onclick="document.querySelector('.metal-pill[data-metal=\'Gold Plated\']').click(); scrollToCatalogSection();">Gold Plated</button>
            </div>
          </div>
        </div>

        <!-- Slide 3: Autumn 2026 New Arrivals & Festive Offers -->
        <div class="shop-carousel-slide" style="background-image:url('assets/images/hero_carousel_3.jpg');">
          <div class="shop-slide-content">
            <span class="shop-slide-eyebrow"><i class="fa-solid fa-sparkles" style="color: var(--antique-copper);"></i> AUTUMN 2026 UNVEILING</span>
            <h2 class="shop-slide-title">Fresh New Arrivals &amp; Festive Privileges</h2>
            <p class="shop-slide-subtitle">Unveiling our newest 20 handcrafted creations with up to 29% off festive privilege discounts.</p>
            <div class="shop-slide-cta-row">
              <button type="button" class="btn-royal-gold" onclick="const chk=document.querySelector('input[name=\'collection\'][value=\'new\']'); if(chk){chk.checked=true; chk.dispatchEvent(new Event('change')); scrollToCatalogSection();}">New Arrivals (20) <i class="fa-solid fa-arrow-right"></i></button>
              <button type="button" class="btn-royal-outline" onclick="const chk=document.querySelector('input[name=\'collection\'][value=\'offers\']'); if(chk){chk.checked=true; chk.dispatchEvent(new Event('change')); scrollToCatalogSection();}">Festive Offers</button>
            </div>
          </div>
        </div>

      </div>

      <!-- Navigation Arrows -->
      <button class="shop-carousel-arrow prev" id="shop-carousel-prev" aria-label="Previous Slide"><i class="fa-solid fa-chevron-left"></i></button>
      <button class="shop-carousel-arrow next" id="shop-carousel-next" aria-label="Next Slide"><i class="fa-solid fa-chevron-right"></i></button>

      <!-- Carousel Indicators / Dots -->
      <div class="shop-carousel-dots" id="shop-carousel-dots">
        <button class="shop-dot active" data-slide="0" aria-label="Slide 1"></button>
        <button class="shop-dot" data-slide="1" aria-label="Slide 2"></button>
        <button class="shop-dot" data-slide="2" aria-label="Slide 3"></button>
      </div>
    </section>

    <!-- Main Shop Layout: Sidebar + Catalog Grid -->
    <div class="shop-main-layout">
      
      <!-- Filters Sidebar (Matching PDF Page 2) -->
      <aside class="filters-sidebar" id="catalog-filters" aria-label="Product Filters">
        <div class="filters-header">
          <h3>Filters</h3>
          <button class="btn-clear-filters" id="btn-clear-all-filters">Clear All</button>
        </div>

        <!-- Collections & Highlights Filter -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Collections</span>
            <i class="fa-solid fa-chevron-up"></i>
          </div>
          <div class="filter-options-list" id="collection-filter-list">
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="collection" value="new"> New Arrivals
              </span>
              <span class="filter-count">(<?= count($kinzCollectionIds['new']) ?>)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="collection" value="bestseller"> Best Sellers
              </span>
              <span class="filter-count">(<?= count($kinzCollectionIds['bestseller']) ?>)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="collection" value="offers"> Festive Offers
              </span>
              <span class="filter-count">(<?= count($kinzCollectionIds['offers']) ?>)</span>
            </label>
          </div>
        </div>

        <!-- Category Filter -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Category</span>
            <i class="fa-solid fa-chevron-up"></i>
          </div>
          <div class="filter-options-list" id="category-filter-list">
            <?php foreach ($kinzCategories as $category): ?>
              <label class="filter-checkbox-label">
                <span class="filter-checkbox-left">
                  <input type="checkbox" name="cat" value="<?= kinz_escape($category['name']) ?>" data-category-slug="<?= kinz_escape($category['slug']) ?>" <?= strcasecmp($category['slug'], $selectedSlug) === 0 ? 'checked' : '' ?>>
                  <?= kinz_escape(ucwords(strtolower($category['name']))) ?>
                </span>
                <span class="filter-count">(<?= (int)$category['actual_count'] ?>)</span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Price Range Slider -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Price Range</span>
            <i class="fa-solid fa-chevron-up"></i>
          </div>
          <div class="price-slider-wrapper">
            <input type="range" class="price-range-slider" id="price-slider" min="0" max="5000" step="100" value="5000">
            <div class="price-range-labels">
              <span>₹ 0</span>
              <span id="price-slider-val">₹ 5,000</span>
            </div>
          </div>
        </div>

        <!-- Metal Type Filter -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Metal Type</span>
            <i class="fa-solid fa-chevron-up"></i>
          </div>
          <div class="filter-options-list">
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="metal" value="Anti-Tarnish"> Anti-Tarnish
              </span>
              <span class="filter-count">(321)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="metal" value="Water Resistant"> Water Resistant
              </span>
              <span class="filter-count">(0)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="metal" value="Gold Plated"> Gold Plated
              </span>
              <span class="filter-count">(18)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="metal" value="Stainless Steel"> Stainless Steel
              </span>
              <span class="filter-count">(0)</span>
            </label>
          </div>
        </div>

        <!-- Occasion Filter -->
        <div class="filter-group">
          <div class="filter-group-title">
            <span>Occasion</span>
            <i class="fa-solid fa-chevron-up"></i>
          </div>
          <div class="filter-options-list">
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="occasion" value="Wedding"> Wedding
              </span>
              <span class="filter-count">(112)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="occasion" value="Festive"> Festive
              </span>
              <span class="filter-count">(66)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="occasion" value="Party"> Party
              </span>
              <span class="filter-count">(88)</span>
            </label>
            <label class="filter-checkbox-label">
              <span class="filter-checkbox-left">
                <input type="checkbox" name="occasion" value="Daily Wear"> Daily Wear
              </span>
              <span class="filter-count">(104)</span>
            </label>
          </div>
        </div>

      </aside>

      <!-- Right Catalog Content Area -->
      <section class="catalog-content-section" aria-label="Products Catalog">
       

        <!-- Catalog Header Text -->
        <div class="catalog-header-text">
          <h2 id="catalog-title-heading"><?= $selectedCategory !== '' ? kinz_escape(ucwords(strtolower($selectedCategory))) : 'All Jewellery' ?></h2>
          <p id="catalog-title-desc">Discover our exquisite collection of handcrafted jewellery</p>
        </div>

        <!-- Single Line Quick Controls Bar: Metal Filters on Left, Sort & Count on Right -->
        <div class="catalog-single-line-bar">
          <div class="metal-quick-filter-bar" id="metal-quick-filters">
            <button type="button" class="metal-pill" data-metal="all">All Metals</button>
            <button type="button" class="metal-pill" data-metal="Anti-Tarnish"><i class="fa-solid fa-crown" style="color: var(--antique-copper);"></i> Anti-Tarnish</button>
            <button type="button" class="metal-pill" data-metal="Water Resistant"><i class="fa-solid fa-droplet" style="color: #4b91b8;"></i> Water Resistant</button>
            <button type="button" class="metal-pill" data-metal="Gold Plated"><i class="fa-solid fa-gem" style="color: var(--antique-copper);"></i> Gold Plated</button>
            <button type="button" class="metal-pill" data-metal="Stainless Steel"><i class="fa-solid fa-shield-halved" style="color: #64748b;"></i> Stainless Steel</button>
          </div>

          <div class="catalog-controls">
            <span style="font-size: 0.85rem; color: var(--text-muted);" id="showing-count-text">Showing <?= count($kinzProducts) ?> products</span>
            
            <div class="sort-control-group" style="display: flex; align-items: center; gap: 8px;">
              <label for="sort-select" style="font-size: 0.85rem; color: var(--text-muted);">Sort by:</label>
              <select id="sort-select" class="sort-select">
                <option value="recommended" <?= $sort === 'recommended' ? 'selected' : '' ?>>Recommended</option>
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
                <option value="ordered" <?= $sort === 'ordered' ? 'selected' : '' ?>>Most Ordered</option>
                <option value="discount" <?= $sort === 'discount' ? 'selected' : '' ?>>Highest Offer</option>
                <option value="price-asc" <?= $sort === 'price-asc' ? 'selected' : '' ?>>Price: Low to High</option>
                <option value="price-desc" <?= $sort === 'price-desc' ? 'selected' : '' ?>>Price: High to Low</option>
                <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Rating</option>
              </select>
            </div>

            <div style="display: flex; gap: 4px;">
             </div>
          </div>
        </div>

        <!-- Active Filter Chips Display -->
        <div class="active-filter-chips-bar" id="active-filter-chips-bar" style="display: none;">
          <span class="active-chips-label"><i class="fa-solid fa-filter"></i> Active Filters:</span>
          <div class="active-chips-container" id="active-chips-container"></div>
          <button type="button" class="clear-all-chips-btn" id="btn-clear-chips">Clear All</button>
        </div>

        <!-- Products Grid (Matching PDF Page 2) -->
        <div class="product-grid" id="catalog-products-grid">
          <?php foreach ($kinzProducts as $product): ?>
            <?php
              $productId = (int)$product['id'];
              $collectionFlags = [
                  'new' => isset($kinzCollectionIds['new'][$productId]),
                  'bestseller' => isset($kinzCollectionIds['bestseller'][$productId]),
                  'offers' => isset($kinzCollectionIds['offers'][$productId])
              ];
              kinz_product_card($product, true, $categorySlugMap, $collectionFlags);
            ?>
          <?php endforeach; ?>
          <?php if (empty($kinzProducts)): ?><p>No active products found in this category.</p><?php endif; ?>
        </div>

        <!-- Pagination (Matching PDF Page 2: < 1 2 3 4 5 >) -->
        <div class="pagination-container">
          <button class="pagination-btn" id="page-prev" title="Previous"><i class="fa-solid fa-chevron-left"></i></button>
          <button class="pagination-btn active" data-page="1">1</button>
          <button class="pagination-btn" data-page="2">2</button>
          <button class="pagination-btn" data-page="3">3</button>
          <button class="pagination-btn" data-page="4">4</button>
          <button class="pagination-btn" data-page="5">5</button>
          <button class="pagination-btn" id="page-next" title="Next"><i class="fa-solid fa-chevron-right"></i></button>
        </div>

      </section>

    </div>
  </main>

  <!-- Footer (Matching PDF Page 1) -->
  <footer class="kinz-footer">
    
    <!-- Top Trust Pillars & Guarantees Strip -->
    <div class="footer-guarantee-strip">
      <div class="trust-bar-badge" style="text-align: center; margin-bottom: 14px; font-weight: 700; color: #dfc168; font-size: 0.85rem; letter-spacing: 1px; text-transform: uppercase;">
        <i class="fa-solid fa-crown" style="margin-right: 6px;"></i> THE KINZ ROYAL ASSURANCE PROMISE &bull; Free Shipping &bull; Secure Payments &bull; Easy Returns
      </div>
      <div class="guarantee-strip-inner">
        <div class="guarantee-strip-item">
          <i class="fa-solid fa-certificate guarantee-strip-icon"></i>
          <div class="guarantee-strip-text">
            <h5> 100% Anti-Tarnish</h5>
            <p>Waterproof &amp; Hypoallergenic</p>
          </div>
        </div>

        <div class="guarantee-strip-item">
          <i class="fa-solid fa-gem guarantee-strip-icon"></i>
          <div class="guarantee-strip-text">
            <h5>Gold &amp; Steel Plated</h5>
            <p>Rich, Lasting Finish</p>
          </div>
        </div>

        <div class="guarantee-strip-item">
          <i class="fa-solid fa-shield-heart guarantee-strip-icon"></i>
          <div class="guarantee-strip-text">
            <h5>Easy Exchange</h5>
            <p>7-Day Return &amp; Exchange</p>
          </div>
        </div>

        <div class="guarantee-strip-item">
          <i class="fa-solid fa-truck-fast guarantee-strip-icon"></i>
          <div class="guarantee-strip-text">
            <h5>Insured Delivery</h5>
            <p>Doorstep Safe Transit</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Main 5-Column Footer Grid -->
    <div class="footer-top-container">
      
      <!-- Column 1: Brand Identity & Mission -->
      <div class="footer-brand-col">
        <a href="index.php" class="brand-logo" style="margin-bottom: 14px;" aria-label="KINZ Home">
          <img src="assets/images/kinz_logo.png" alt="KINZ Luxury Jewellery" class="brand-logo-img">
        </a>
        <p>Anti-tarnish gold plated and stainless steel plated jewellery—waterproof, skin-friendly and made for daily wear, festive and party looks.</p>
        
        <div class="footer-social-row">
          <a href="https://www.instagram.com/kinz_jewellery?rpxt=NG04MGl6dmNjdmUz" target="_blank" rel="noopener noreferrer" class="social-icon-circle" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a>
          <a href="#" class="social-icon-circle" aria-label="Youtube"><i class="fa-brands fa-youtube"></i></a>
          <a href="https://wa.me/919360660773" target="_blank" rel="noopener noreferrer" class="social-icon-circle" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
        </div>
      </div>
      <!-- Column 2: Shop by Category -->
      <div>
        <h4 class="footer-col-title">Shop Jewellery</h4>
        <ul class="footer-links-list">
          <li><a href="shop.php">All Jewellery</a></li>
          <li><a href="shop.php?category=rings">Rings &amp; Bands</a></li>
          <li><a href="shop.php?category=necklaces">Necklaces &amp; Chokers</a></li>
          <li><a href="shop.php?category=earrings">Jhumkas &amp; Earrings</a></li>
          <li><a href="shop.php?category=bangles">Bangles &amp; Kadas</a></li>
          <li><a href="shop.php?category=kids">Kids Accessories</a></li>
          <li><a href="shop.php?metal=Anti-Tarnish">Anti-Tarnish Collection</a></li>
          <li><a href="shop.php?occasion=Wedding">Wedding &amp; Festive Edit</a></li>
        </ul>
      </div>

      <!-- Column 3: Policies & Guarantees (Explicitly Requested) -->
      <div>
        <h4 class="footer-col-title">Policies &amp; Trust</h4>
        <ul class="footer-links-list">
          <li><span class="footer-static-item"><i class="fa-solid fa-shield-check" style="color:var(--gold-400); margin-right:4px;"></i> Anti-Tarnish Quality Guarantee</span></li>
          <li><span class="footer-static-item"><i class="fa-solid fa-gem" style="color:var(--gold-400); margin-right:4px;"></i> Plating &amp; Material Details</span></li>
          <li><span class="footer-static-item"><i class="fa-solid fa-arrow-rotate-left" style="color:var(--gold-400); margin-right:4px;"></i> 7-Day Return &amp; Exchange Policy</span></li>
          <li><span class="footer-static-item"><i class="fa-solid fa-handshake" style="color:var(--gold-400); margin-right:4px;"></i> Exchange &amp; Replacement</span></li>
          <li><span class="footer-static-item"><i class="fa-solid fa-truck-fast" style="color:var(--gold-400); margin-right:4px;"></i> Insured Shipping &amp; Delivery</span></li>
          <li><span class="footer-static-item"><i class="fa-solid fa-lock" style="color:var(--gold-400); margin-right:4px;"></i> Privacy &amp; Data Security</span></li>
          <li><span class="footer-static-item"><i class="fa-solid fa-file-contract" style="color:var(--gold-400); margin-right:4px;"></i> Terms &amp; Conditions</span></li>
        </ul>
      </div>

      <!-- Column 4: Contact Information & Concierge -->
      <div>
        <h4 class="footer-col-title">Client Care &amp; Contact</h4>

        <div class="footer-contact-item">
          <i class="fa-regular fa-envelope"></i>
          <a href="mailto:Kinzjewellery0@gmail.com" style="color:inherit; text-decoration:none;">Kinzjewellery0@gmail.com</a>
        </div>
        <div class="footer-contact-item">
          <i class="fa-solid fa-phone"></i>
          <a href="tel:9360660773" style="color:inherit; text-decoration:none;">+91 9360660773</a>
        </div>
        <div class="footer-contact-item">
          <i class="fa-brands fa-whatsapp"></i>
          <a href="https://wa.me/919360660773" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">WhatsApp Support</a>
        </div>
        <div class="footer-contact-item">
          <i class="fa-regular fa-clock"></i>
          <span>10:00 AM – 8:30 PM (All 7 Days)</span>
        </div>
      </div>

    </div>

    <!-- Bottom Copyright Bar -->
    <div class="footer-bottom-bar">
      <div class="footer-bottom-inner">
        <div>
          &copy; 2026 KINZ Jewellery. All rights reserved. | <span style="color: var(--gold-400);">A Hidden Treasure.</span>
        </div>
        <div class="footer-policy-links">
          <a href="#">Privacy Policy</a>
          <a href="#">Terms &amp; Conditions</a>
          <a href="#">Shipping Policy</a>
          <a href="#">Jewellery Care Guide</a>
          <a href="#">Sitemap</a>
        </div>
      </div>
    </div>
  </footer>

  
  
  
  
  



<script src="assets/js/cart-actions.js?v=<?= (int) @filemtime(__DIR__ . '/assets/js/cart-actions.js') ?>"></script>
<script src="assets/js/shop-filters.js?v=<?= (int)@filemtime(__DIR__ . "/assets/js/shop-filters.js") ?>"></script>
<script src="assets/js/cart-drawer.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/cart-drawer.js') ?>"></script>
<script src="assets/js/kinz-ui.js?v=<?= (int)@filemtime(__DIR__ . '/assets/js/kinz-ui.js') ?>"></script>
</body>
</html>
