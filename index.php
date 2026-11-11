<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
check_auth();

$loggedAdmin = get_logged_admin();
$activeView = $_GET['view'] ?? 'dashboard';
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

$kinzExpiryFile = __DIR__ . '/../customer/includes/order_expiry.php';
if (file_exists($kinzExpiryFile)) {
    require_once $kinzExpiryFile;
    kinz_expire_unpaid_orders($conn);
}

// 1. Fetch KPI Statistics from database
// Total Sales: Sum of all non-cancelled orders (real dynamic calculation)
$totalSalesRow = db_fetch_one("SELECT SUM(total_amount) AS total_sales FROM orders WHERE status != 'Cancelled' AND (payment_method <> 'Razorpay' OR payment_status = 'Paid')");
$totalSales = (float)($totalSalesRow['total_sales'] ?? 0);

// Today's Sales: Real-time calculation for today's non-cancelled orders
$todaySalesRow = db_fetch_one("SELECT SUM(total_amount) AS today_sales FROM orders WHERE DATE(order_date) = CURDATE() AND status != 'Cancelled' AND (payment_method <> 'Razorpay' OR payment_status = 'Paid')");
$todaySales = (float)($todaySalesRow['today_sales'] ?? 0);

$totalOrdersCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM orders")['c'] ?? 0);
$pendingOrdersCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM orders WHERE status = 'Pending'")['c'] ?? 0);
$completedOrdersCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM orders WHERE status = 'Delivered'")['c'] ?? 0);
$cancelledOrdersCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM orders WHERE status = 'Cancelled'")['c'] ?? 0);

$totalProductsCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM products")['c'] ?? 0);
$lowStockCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM products WHERE stock <= reorder_level AND stock > 0")['c'] ?? 0);

$totalCustomersCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM customers")['c'] ?? 0);
$customerStats = [
    'signed_in' => (int)(db_fetch_one("SELECT COUNT(*) AS c FROM customers WHERE is_signed_in = 1")['c'] ?? 0),
    'total' => (int)(db_fetch_one("SELECT COUNT(*) AS c FROM customers")['c'] ?? 0),
    'active' => (int)(db_fetch_one("SELECT COUNT(*) AS c FROM customers WHERE status = 'Active'")['c'] ?? 0),
    'pending_delivery' => (int)(db_fetch_one("SELECT COUNT(DISTINCT customer_email) AS c FROM orders WHERE status IN ('Pending','Processing','Shipped','Out for Delivery')")['c'] ?? 0),
    'total_ltv' => (float)(db_fetch_one("SELECT SUM(total_spent) AS s FROM customers")['s'] ?? 0),
    'new_2026' => (int)(db_fetch_one("SELECT COUNT(*) AS c FROM customers WHERE YEAR(registered_date) >= YEAR(CURDATE())")['c'] ?? 0),
    'vip' => (int)(db_fetch_one("SELECT COUNT(*) AS c FROM customers WHERE tier LIKE '%VIP%' OR tier LIKE '%Platinum%'")['c'] ?? 0)
];

// 2. Fetch Products, Categories, Orders, Customers, Coupons, Admin Users, Reviews
$bestSellers = db_fetch_all("SELECT * FROM products WHERE sales_count > 0 ORDER BY sales_count DESC LIMIT 4");
$recentOrders = db_fetch_all("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5");

$allProducts = db_fetch_all("SELECT * FROM products ORDER BY id ASC");
$allCategories = db_fetch_all("SELECT * FROM categories ORDER BY id ASC");
$allOrders = db_fetch_all("SELECT * FROM orders ORDER BY order_date DESC");
$orderItemsByOrder = [];
$orderItemRows = db_fetch_all("SELECT order_id, product_id, product_title, qty, unit_price, line_total FROM order_items ORDER BY id ASC");
foreach ($orderItemRows as $orderItemRow) {
    $orderIdForItem = (int)$orderItemRow['order_id'];
    if (!isset($orderItemsByOrder[$orderIdForItem])) $orderItemsByOrder[$orderIdForItem] = [];
    $orderItemsByOrder[$orderIdForItem][] = $orderItemRow;
}
foreach ($allOrders as &$orderRow) {
    $orderRow['line_items'] = $orderItemsByOrder[(int)$orderRow['id']] ?? [];
}
unset($orderRow);
$allCustomers = db_fetch_all("SELECT * FROM customers ORDER BY id ASC");
$allCoupons = db_fetch_all("SELECT * FROM coupons ORDER BY id ASC");
$allAdminUsers = db_fetch_all("SELECT * FROM admin_users ORDER BY id ASC");

// Reviews
$allReviews = db_fetch_all("SELECT * FROM reviews ORDER BY created_at DESC");
$reviewStats = [
    'total'      => (int)(db_fetch_one("SELECT COUNT(*) AS c FROM reviews")['c'] ?? 0),
    'avg_rating' => (float)(db_fetch_one("SELECT AVG(rating) AS r FROM reviews")['r'] ?? 0),
];

// 3. Store Settings
$storeSettingsRows = db_fetch_all("SELECT setting_key, setting_value FROM settings");
$settings = [];
foreach ($storeSettingsRows as $sr) {
    $settings[$sr['setting_key']] = $sr['setting_value'];
}
$storeName = $settings['store_name'] ?? 'Kinz Guarantee Jewellery Store';
$storeCurrency = $settings['currency'] ?? 'INR';
$storeCurrencySymbol = $settings['currency_symbol'] ?? '₹';
$storeTax = $settings['tax_rate'] ?? '5.0';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Kinz Jewellery - Gold Plated & Stainless Steel Plated Jewellery E-Commerce Admin Module and Stocks Management System">
  <title>Kinz Jewellery | Admin Dashboard</title>
  <link rel="icon" type="image/jpeg" href="assets/kinz_logo.jpg">
  <link rel="apple-touch-icon" href="assets/kinz_logo.jpg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="styles/theme.css">
  <link rel="stylesheet" href="styles/admin.css?v=<?= time() ?>">
<script>
  window.DB = {
    products: <?= json_encode($allProducts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    categories: <?= json_encode($allCategories, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    orders: <?= json_encode($allOrders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    customers: <?= json_encode($allCustomers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    coupons: <?= json_encode($allCoupons, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    adminUsers: <?= json_encode($allAdminUsers, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
    reviews: <?= json_encode($allReviews, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
  };
</script>
</head>
<body>

  <div class="app-container">
    <!-- ======================================================================
         Sidebar Navigation
         ====================================================================== -->
    <aside class="sidebar" id="sidebar">
      <div class="sidebar-header">
        <div class="brand-badge" title="Kinz Jewellery - A Hidden Treasure">
          <div class="brand-logo-wrapper">
            <img src="assets/kinz_logo.jpg" alt="Kinz Jewellery Logo" class="brand-logo-img-sidebar">
          </div>
        </div>
        <button type="button" class="sidebar-mobile-close-btn" id="sidebar-close-btn" aria-label="Close Sidebar" title="Close Navigation">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section-title">Core Operations</div>
        
        <a class="nav-item active" data-view="dashboard" id="nav-dashboard">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
            <span>Dashboard</span>
          </div>
        </a>

        <a class="nav-item" data-view="products" id="nav-products">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9"/><polygon points="12 2 15 8.5 22 9.5 17 14.5 18.5 21.5 12 18 5.5 21.5 7 14.5 2 9.5 9 8.5 12 2"/></svg>
            <span>Products</span>
          </div>
          <span class="nav-badge badge-forest" id="badge-prod-count"><?= $totalProductsCount ?></span>
        </a>

        <a class="nav-item" data-view="categories" id="nav-categories">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16M4 12h16M4 18h7"/></svg>
            <span>Categories</span>
          </div>
        </a>

        <a class="nav-item" data-view="inventory" id="nav-inventory">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
            <span>Inventory</span>
          </div>
          <span class="nav-badge badge-copper" id="badge-stock-count"><?= $lowStockCount ?></span>
        </a>

        <a class="nav-item" data-view="orders" id="nav-orders">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>Orders</span>
          </div>
          <span class="nav-badge badge-gold" id="badge-orders-count"><?= $pendingOrdersCount ?></span>
        </a>

        <div class="nav-section-title">Customers & Sales</div>

        <a class="nav-item" data-view="customers" id="nav-customers">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span>Customers</span>
          </div>
        </a>

        <a class="nav-item" data-view="reviews" id="nav-reviews">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            <span>Reviews</span>
          </div>
          <span class="nav-badge badge-forest" id="badge-reviews-count"><?= $reviewStats['total'] ?></span>
        </a>

        <a class="nav-item" data-view="coupons" id="nav-coupons">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
            <span>Coupons & Offers</span>
          </div>
        </a>

        <a class="nav-item" data-view="reports" id="nav-reports">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
            <span>Reports & Analytics</span>
          </div>
        </a>

        <div class="nav-section-title">System & Security</div>

        <a class="nav-item" data-view="users" id="nav-users">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <span>Admin Users</span>
          </div>
        </a>

        <a class="nav-item" data-view="settings" id="nav-settings">
          <div class="nav-item-content">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
            <span>Settings</span>
          </div>
        </a>
      </nav>

      <!-- Sidebar User Profile -->
      <div class="sidebar-footer">
        <div class="user-profile-preview">
          <?php $initials = strtoupper(substr($loggedAdmin['name'], 0, 1) . substr(strstr($loggedAdmin['name'], ' ') ?: ' ', 1, 1)); ?><div class="user-avatar" id="sidebar-user-avatar"><?= $initials ?></div>
          <div class="user-details">
            <div class="name" id="sidebar-user-name"><?= htmlspecialchars($loggedAdmin['name']) ?></div>
            <div class="role" id="sidebar-user-role"><?= htmlspecialchars($loggedAdmin['role']) ?></div>
          </div>
          <a href="actions/logout.php" class="btn-sidebar-logout" title="Sign Out">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18.36 6.64a9 9 0 1 1-12.73 0"/><line x1="12" y1="2" x2="12" y2="12"/></svg>
          </a>
        </div>
      </div>
    </aside>

    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop" id="sidebar-backdrop" aria-hidden="true"></div>

    <!-- ======================================================================
         Main Application Wrapper
         ====================================================================== -->
    <main class="main-wrapper">
      <!-- Top Navigation Header -->
      <header class="top-header">
        <div class="header-left">
          <button type="button" class="sidebar-toggle-btn" id="sidebar-toggle" aria-label="Toggle Navigation" title="Toggle Navigation Menu">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
          </button>
          <div class="header-search">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" id="global-search-input" placeholder="Search guarantee jewellery, client orders...">
          </div>
        </div>

        <div class="header-right">
          <button class="header-icon-btn" onclick="showToast('No new notifications', 'info')" title="Notifications">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
            <div class="badge-dot"></div>
          </button>
        </div>
      </header>

      <!-- Content Views Area -->
      <div class="content-area">

        <!-- ==================================================================
             VIEW 1: DASHBOARD
             ================================================================== -->
        <section class="page-view active-view" id="view-dashboard">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Dashboard</h2>
              <p>Overview of sales, orders, and products</p>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
              <button class="btn-secondary" onclick="window.location.href='actions/export_csv.php?type=orders'">
                Export Orders CSV
              </button>
              <button class="btn-copper" onclick="navigateTo('inventory')">
                Manage Stock
              </button>
            </div>
          </div>

          <!-- KPI Cards Grid (8 Cards with icons removed) -->
          <div class="dashboard-kpi-grid">
            <!-- Total Sales -->
            <div class="kpi-card kpi-highlight">
              <div class="kpi-top">
                <span class="kpi-label">Total Sales</span>
              </div>
              <div class="kpi-value" id="kpi-total-sales"><?= format_currency($totalSales) ?></div>
            </div>

            <!-- Today's Sales -->
            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Today's Sales</span>
              </div>
              <div class="kpi-value" id="kpi-today-sales"><?= format_currency($todaySales) ?></div>
            </div>

            <!-- Total Orders -->
            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Total Orders</span>
              </div>
              <div class="kpi-value" id="kpi-total-orders"><?= number_format($totalOrdersCount) ?></div>
            </div>

            <!-- Pending Orders -->
            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Pending Orders</span>
              </div>
              <div class="kpi-value" id="kpi-pending-orders" style="color:var(--antique-copper);"><?= number_format($pendingOrdersCount) ?></div>
            </div>

            <!-- Completed Orders -->
            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Completed Orders</span>
              </div>
              <div class="kpi-value" id="kpi-completed-orders"><?= number_format($completedOrdersCount) ?></div>
            </div>

            <!-- Cancelled Orders -->
            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Cancelled Orders</span>
              </div>
              <div class="kpi-value" id="kpi-cancelled-orders" style="color:var(--status-danger);"><?= number_format($cancelledOrdersCount) ?></div>
            </div>

            <!-- Total Customers -->
            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Total Customers</span>
              </div>
              <div class="kpi-value" id="kpi-total-customers"><?= number_format($totalCustomersCount) ?></div>
            </div>

            <!-- Low-Stock Alert -->
            <div class="kpi-card" style="border-left: 4px solid var(--antique-copper);">
              <div class="kpi-top">
                <span class="kpi-label">Low-Stock Items</span>
              </div>
              <div class="kpi-value" id="kpi-low-stock" style="color:var(--antique-copper);"><?= number_format($lowStockCount) ?></div>
            </div>
          </div>

          <!-- Charts Layout -->
          <div class="dashboard-main-grid">
            <!-- Revenue & Orders Spline Chart -->
            <div class="dashboard-panel">
              <div class="panel-header">
                <div>
                  <h3>Revenue & Sales</h3>
                  <p style="font-size:0.8rem; color:var(--color-text-muted);">Overview of revenue and orders</p>
                </div>
                <div class="chart-controls">
                  <button class="chart-tab-btn chart-metric-btn active" data-metric="revenue">Revenue (₹)</button>
                  <button class="chart-tab-btn chart-metric-btn" data-metric="orders">Orders</button>
                  <span style="border-left:1px solid var(--border-beige); height:18px; margin:0 4px;"></span>
                  <button class="chart-tab-btn chart-period-btn active" data-period="7days">7 Days</button>
                  <button class="chart-tab-btn chart-period-btn" data-period="30days">30 Days</button>
                  <button class="chart-tab-btn chart-period-btn" data-period="1year">1 Year</button>
                </div>
              </div>
              <div class="canvas-container">
                <canvas id="revenueChartCanvas"></canvas>
              </div>
            </div>
          </div>

          <!-- Bottom Showcase: Best Sellers & Recent Orders -->
          <div class="dashboard-bottom-grid">
            <!-- Best Selling Products -->
            <div class="dashboard-panel">
              <div class="panel-header">
                <h3>Best-Selling Products</h3>
                <button class="btn-secondary" style="font-size:0.75rem; padding:4px 8px;" onclick="navigateTo('products')">View All</button>
              </div>
              <div class="best-sellers-list" id="dashboard-best-sellers">

<?php if (!empty($bestSellers)): ?>
  <?php foreach ($bestSellers as $item): ?>
    <div class="best-seller-row">
      <div class="item-thumb-details">
        <?php if (!empty($item['image'])): ?>
          <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['title']) ?>" class="jewel-thumbnail" style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:1px solid rgba(200, 121, 39, 0.3); flex-shrink:0;">
        <?php else: ?>
          <div class="jewel-thumbnail blank-image-slot" style="width:52px; height:52px; border-radius:8px; background:var(--champagne-beige); border:1px solid rgba(200, 121, 39, 0.3); flex-shrink:0;"></div>
        <?php endif; ?>
        <div class="item-text">
          <h4><?= htmlspecialchars($item['title']) ?></h4>
          <div class="item-meta">
            <span class="finish-badge"><?= htmlspecialchars($item['metal_type'] ?: 'Gold Plated') ?></span>
            <span class="guarantee-badge"><?= htmlspecialchars($item['guarantee'] ?: '1 Year Guarantee') ?></span>
          </div>
        </div>
      </div>
      <div class="item-sales-stat">
        <div class="stat-revenue"><?= format_currency($item['revenue_generated'] ?: ($item['base_price'] * $item['sales_count'])) ?></div>
        <div class="stat-count"><?= (int)$item['sales_count'] ?> sold</div>
      </div>
    </div>
  <?php endforeach; ?>
<?php else: ?>
  <div style="text-align:center; padding:36px 16px; color:var(--color-text-muted);">
    <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" style="margin-bottom:8px; opacity:0.45;"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
    <div style="font-size:0.86rem; font-weight:600; color:var(--deep-charcoal-green);">No Sales Recorded Yet</div>
    <div style="font-size:0.75rem; margin-top:3px;">Best-selling products will appear automatically as orders are placed.</div>
  </div>
<?php endif; ?>

              </div>
            </div>

            <!-- Recent Orders -->
            <div class="dashboard-panel">
              <div class="panel-header">
                <h3>Recent Orders</h3>
                <button class="btn-secondary" style="font-size:0.75rem; padding:4px 8px;" onclick="navigateTo('orders')">Manage Orders</button>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>Customer</th>
                      <th>Date</th>
                      <th>Total</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="dashboard-recent-orders-tbody">

<?php if (!empty($recentOrders)): ?>
  <?php foreach ($recentOrders as $o): ?>
    <tr>
      <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
      <td><?= htmlspecialchars($o['customer_name']) ?></td>
      <td><?= htmlspecialchars(explode(' ', $o['order_date'])[0]) ?></td>
      <td><strong><?= format_currency($o['total_amount']) ?></strong></td>
      <td><span class="status-pill status-<?= strtolower(str_replace(' ', '', $o['status'])) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
      <td>
        <button class="action-icon-btn" onclick="openOrderDrawerManual('<?= htmlspecialchars($o['order_code']) ?>')" title="View Order">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
        </button>
      </td>
    </tr>
  <?php endforeach; ?>
<?php else: ?>
  <tr>
    <td colspan="6" style="text-align:center; padding:36px 16px; color:var(--color-text-muted);">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" style="margin-bottom:8px; opacity:0.45;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      <div style="font-size:0.86rem; font-weight:600; color:var(--deep-charcoal-green);">No Orders Placed Yet</div>
      <div style="font-size:0.74rem; margin-top:3px;">Real incoming customer purchases will be displayed here.</div>
    </td>
  </tr>
<?php endif; ?>

                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </section>

        <!-- ==================================================================
             VIEW 2: PRODUCT MANAGEMENT
             ================================================================== -->
        <section class="page-view" id="view-products">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Products</h2>
              <p>Add, edit, and manage products and stock</p>
            </div>
            <div style="display:flex; gap:10px;">
              <button class="btn-secondary" onclick="window.location.href='actions/export_csv.php?type=products'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export CSV
              </button>
              <button class="btn-luxury-primary" onclick="openAddProductModal()">
                <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Product
              </button>
            </div>
          </div>

          <!-- Product Table Filter Card -->
          <div class="table-card">
            <div class="table-toolbar">
              <div class="toolbar-filters">
                <input type="text" id="filter-product-search" class="filter-input" placeholder="Search by products" oninput="renderProductsTable()">
                <select id="filter-product-category" class="filter-select" onchange="renderProductsTable()">
                  <option value="">All Categories</option>
                  <?php foreach ($allCategories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <select id="filter-product-stock" class="filter-select" onchange="renderProductsTable()">
                  <option value="">All Stock Levels</option>
                  <option value="low">Low Stock (≤ 5)</option>
                  <option value="out">Out of Stock</option>
                  <option value="in">Healthy Stock</option>
                </select>
                <select id="filter-product-status" class="filter-select" onchange="renderProductsTable()">
                  <option value="">All Status</option>
                  <option value="Active">Enabled Only</option>
                  <option value="Disabled">Disabled Only</option>
                </select>
              </div>
            </div>

            <div class="table-responsive">
              <table class="luxury-table">
                <thead>
                  <tr>
                    <th>Product & SKU</th>
                    <th>Category</th>
                    <th>Metal Type / Occasion</th>
                    <th>Price</th>
                    <th>Stocks</th>
                    <th>Status</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="products-table-tbody">

<?php foreach ($allProducts as $p): 
  $stockClass = ($p['stock'] == 0) ? 'status-out-of-stock' : (($p['stock'] <= $p['reorder_level']) ? 'status-low-stock' : 'status-optimal');
  $stockLabel = ($p['stock'] == 0) ? 'Out of Stock' : (($p['stock'] <= $p['reorder_level']) ? 'Low (' . $p['stock'] . ')' : $p['stock'] . ' in Stock');

  // Resolve all photos for this product from images (JSON) or image column
  $pImages = [];
  if (!empty($p['images'])) {
    $decoded = json_decode($p['images'], true);
    if (is_array($decoded)) {
      $pImages = array_values(array_filter(array_map('trim', $decoded)));
    }
  }
  if (empty($pImages) && !empty($p['image'])) {
    $pImages[] = trim($p['image']);
  }
  $coverImg = !empty($pImages) ? $pImages[0] : '';
  $imgCount = count($pImages);
?>
  <tr data-product-id="<?= $p['id'] ?>">
    <td>
      <div class="jewel-item-cell">
        <div class="jewel-thumb-wrapper" onclick="openProductGalleryModal('<?= $p['id'] ?>')" style="cursor:pointer;" title="<?= $imgCount > 0 ? 'Touch to view all ' . $imgCount . ' photo(s)' : 'Touch to upload photo' ?>">
          <?php if (!empty($coverImg)): ?>
            <img src="<?= htmlspecialchars($coverImg) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="jewel-thumbnail" onerror="this.onerror=null; this.src='assets/ring.jpg';">
            <?php if ($imgCount > 1): ?>
              <span class="thumb-count-badge" title="<?= $imgCount ?> photos available">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <?= $imgCount ?>
              </span>
            <?php endif; ?>
            <div class="jewel-thumb-hover-overlay">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
              <span>View</span>
            </div>
          <?php else: ?>
            <div class="jewel-thumbnail blank-image-slot" style="width:52px; height:52px; border-radius:8px; background:var(--champagne-beige); border:1px solid rgba(200, 121, 39, 0.3); display:flex; flex-direction:column; align-items:center; justify-content:center; gap:2px;" title="No photo uploaded - Touch to add">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--antique-copper)" stroke-width="2" style="opacity:0.75;"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
              <span style="font-size:0.56rem; color:var(--antique-copper); font-weight:700;">+ Photos</span>
            </div>
          <?php endif; ?>
        </div>
        <div class="jewel-item-info">
          <div class="jewel-item-title-link" onclick="openProductGalleryModal('<?= $p['id'] ?>')" style="cursor:pointer;" title="Touch to view photos & details">
            <?= htmlspecialchars($p['title']) ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-text-muted); font-family:monospace; margin-top:2px;">
            <?= htmlspecialchars($p['sku']) ?>
          </div>
          <?php if ($imgCount > 1): ?>
            <div class="jewel-micro-thumbs-strip" style="margin-top:4px;">
              <?php foreach (array_slice($pImages, 0, 4) as $mIdx => $mUrl): ?>
                <img src="<?= htmlspecialchars($mUrl) ?>" alt="Photo <?= $mIdx + 1 ?>" class="micro-thumb <?= $mIdx === 0 ? 'micro-thumb-active' : '' ?>" onclick="event.stopPropagation(); openProductGalleryModal('<?= $p['id'] ?>', <?= $mIdx ?>);" title="View photo <?= $mIdx + 1 ?>">
              <?php endforeach; ?>
              <?php if ($imgCount > 4): ?>
                <span class="micro-thumb-more" onclick="event.stopPropagation(); openProductGalleryModal('<?= $p['id'] ?>');">+<?= $imgCount - 4 ?></span>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </td>
    <td><strong><?= htmlspecialchars($p['category']) ?></strong></td>
    <td>
      <div style="font-size:0.82rem; color:var(--deep-charcoal-green); font-weight:600;"><?= htmlspecialchars($p['metal_type'] ?? 'Gold Plated') ?></div>
      <div style="font-size:0.72rem; color:var(--color-text-muted);">Occasion: <?= htmlspecialchars($p['occasion'] ?? 'Daily Wear') ?></div>
    </td>
    <td>
      <strong><?= format_currency($p['base_price']) ?></strong>
      <?php if ($p['discount_pct'] > 0): ?>
        <span style="font-size:0.72rem; color:var(--antique-copper);">(-<?= (int)$p['discount_pct'] ?>%)</span>
      <?php endif; ?>
    </td>
    <td><span class="status-pill <?= $stockClass ?>"><?= $stockLabel ?></span></td>
    <td>
      <a href="actions/product_action.php?action=toggle_status&id=<?= $p['id'] ?>" class="status-toggle-pill <?= ($p['status'] === 'Active') ? 'status-enabled' : 'status-disabled' ?>" title="Click to toggle status" style="text-decoration:none;">
        <span class="status-indicator-dot"></span>
        <span><?= ($p['status'] === 'Active') ? 'Enabled' : 'Disabled' ?></span>
      </a>
    </td>
    <td>
      <div class="action-btn-group">
        <button class="action-icon-btn btn-view-gallery" onclick="openProductGalleryModal('<?= $p['id'] ?>')" title="View All Photos">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
        </button>
        <button class="action-icon-btn" onclick="openEditProductModalManual('<?= $p['id'] ?>')" title="Edit Piece">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <a href="actions/product_action.php?action=delete&id=<?= $p['id'] ?>" class="action-icon-btn btn-delete" onclick="return confirm('Are you sure you want to delete <?= htmlspecialchars(addslashes($p['title'])) ?>?');" title="Delete Piece">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </a>
      </div>
    </td>
  </tr>
<?php endforeach; ?>

                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==================================================================
             VIEW 3: CATEGORY MANAGEMENT
             ================================================================== -->
        <section class="page-view" id="view-categories">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Product Categories</h2>
              <p>Manage product categories, banner images, and descriptions</p>
            </div>
            <button class="btn-luxury-primary" onclick="openAddCategoryModal()">
              <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Create Category
            </button>
          </div>

          <div class="categories-grid" id="categories-grid-container">

<?php foreach ($allCategories as $c): 
  $catProdsCount = (int)(db_fetch_one("SELECT COUNT(*) AS c FROM products WHERE LOWER(category) = LOWER('" . addslashes($c['name']) . "')")['c'] ?? 0);
?>
  <div class="category-card" onclick="navigateTo('products'); document.getElementById('filter-product-category').value = '<?= htmlspecialchars(addslashes($c['name'])) ?>'; renderProductsTable();" style="cursor:pointer;" title="Filter products by <?= htmlspecialchars($c['name']) ?>">
    <div class="category-img-banner" style="background:var(--champagne-beige); display:flex; align-items:center; justify-content:center; position:relative; overflow:hidden;">
      <?php if (!empty($c['image'])): ?>
        <img src="<?= htmlspecialchars($c['image']) ?>" alt="<?= htmlspecialchars($c['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
      <?php else: ?>
        <div class="blank-image-banner" style="width:100%; height:100%; background:var(--champagne-beige);"></div>
      <?php endif; ?>
      <span class="category-product-badge"><?= $catProdsCount ?> Pieces</span>
    </div>
    <div class="category-body">
      <div>
        <h3 class="category-title"><?= htmlspecialchars($c['name']) ?></h3>
        <p class="category-desc"><?= htmlspecialchars($c['seo_description']) ?></p>
        <div class="category-seo-tag"><strong>Title:</strong> <?= htmlspecialchars($c['seo_title']) ?></div>
      </div>
      <div class="category-actions">
        <span class="status-pill status-active"><?= htmlspecialchars($c['status']) ?></span>
        <div class="action-btn-group">
          <button class="action-icon-btn" onclick="event.stopPropagation(); openEditCategoryModal(<?= (int)$c['id'] ?>);" title="Edit Category Settings">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
          </button>
          <a href="actions/category_action.php?action=delete&id=<?= $c['id'] ?>" class="action-icon-btn btn-delete" onclick="event.stopPropagation(); return confirm('Delete category <?= htmlspecialchars(addslashes($c['name'])) ?>?');" title="Delete Category">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>

            </div>
        </section>

        <!-- ==================================================================
             VIEW 4: INVENTORY MANAGEMENT
             ================================================================== -->
        <section class="page-view" id="view-inventory">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Inventory Management</h2>
              <p>Track stock levels, reorder alerts, and update inventory</p>
            </div>
          </div>

          <div class="table-card" style="margin-bottom: 28px;">
            <div class="table-toolbar">
              <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green); margin:0;">Stocks</h3>
              <div class="toolbar-filters">
                <input type="text" id="filter-inventory-search" class="filter-input" placeholder="Search product..." oninput="renderInventoryTable()">
                <select id="filter-inventory-category" class="filter-select" onchange="renderInventoryTable()">
                  <option value="">All Categories</option>
                  <?php foreach ($allCategories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <select id="filter-inventory-stock" class="filter-select" onchange="renderInventoryTable()">
                  <option value="">All Stocks</option>
                  <option value="instock">In Stock</option>
                  <option value="lowstock">Low Stock</option>
                  <option value="outofstock">Out of Stock</option>
                </select>
              </div>
            </div>
            <div class="table-responsive">
              <table class="luxury-table">
                <thead>
                  <tr>
                    <th>Product & SKU</th>
                    <th>Category</th>
                    <th>Stocks</th>
                    <th>Reorder Trigger</th>
                    <th>Stock Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="inventory-table-tbody">

<?php foreach ($allProducts as $p): 
  $isOut = ($p['stock'] == 0);
  $isLow = ($p['stock'] <= $p['reorder_level'] && !$isOut);
  $badgeClass = $isOut ? 'status-out-of-stock' : ($isLow ? 'status-low-stock' : 'status-optimal');
  $badgeText = $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'In Stock');
?>
  <tr>
    <td>
      <div style="display:flex; align-items:center; gap:12px;">
        <?php if (!empty($p['image'])): ?>
          <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="jewel-thumbnail" style="width:44px; height:44px; border-radius:8px; object-fit:cover; border:1px solid var(--border-gold); flex-shrink:0;">
        <?php else: ?>
          <div class="jewel-thumbnail blank-image-slot" style="width:44px; height:44px; border-radius:8px; background:var(--champagne-beige); border:1px solid var(--border-gold); flex-shrink:0;"></div>
        <?php endif; ?>
        <div>
          <div style="font-weight:600; color:var(--deep-charcoal-green);"><?= htmlspecialchars($p['title']) ?></div>
          <div style="font-size:0.75rem; color:var(--color-text-muted); font-family:monospace; margin-top:2px;"><?= htmlspecialchars($p['sku']) ?></div>
        </div>
      </div>
    </td>
    <td><strong><?= htmlspecialchars($p['category']) ?></strong></td>
    <td><strong style="font-size:1.05rem;"><?= (int)$p['stock'] ?></strong> units</td>
    <td><?= (int)$p['reorder_level'] ?> units</td>
    <td><span class="status-pill <?= $badgeClass ?>"><?= $badgeText ?></span></td>
    <td>
      <button class="btn-secondary" style="padding:4px 12px; font-size:0.78rem;" onclick="openStockAdjustModal('<?= htmlspecialchars($p['sku']) ?>')">
        Adjust Stock
      </button>
    </td>
  </tr>
<?php endforeach; ?>

                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==================================================================
             VIEW 5: ORDER MANAGEMENT
             ================================================================== -->
        <section class="page-view" id="view-orders">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Orders</h2>
              <p>Track customer orders, payments, packaging, and deliveries</p>
            </div>
            <button class="btn-secondary" onclick="exportOrdersCSV()">Export All Orders CSV</button>
          </div>

          <div class="table-card">
            <div class="table-toolbar">
              <div class="toolbar-filters">
                <input type="text" id="filter-order-search" class="filter-input" placeholder="Search order ID, client name..." oninput="renderOrdersTable()">
                <select id="filter-order-status" class="filter-select" onchange="renderOrdersTable()">
                  <option value="">All Status</option>
                  <option value="Pending">Pending</option>
                  <option value="Confirmed">Confirmed</option>
                  <option value="Processing">Processing</option>
                  <option value="Shipped">Shipped</option>
                  <option value="Out for Delivery">Out for Delivery</option>
                  <option value="Delivered">Delivered</option>
                  <option value="Cancelled">Cancelled</option>
                </select>
                <select id="filter-order-payment" class="filter-select" onchange="renderOrdersTable()">
                  <option value="">All Payments</option>
                  <option value="Paid">Paid</option>
                  <option value="Unpaid">Unpaid</option>
                  <option value="Refunded">Refunded</option>
                </select>
                <select id="filter-order-date-sort" class="filter-select" onchange="renderOrdersTable()" title="Sort order list date-wise">
                  <option value="desc">Date: Newest First</option>
                  <option value="asc">Date: Oldest First</option>
                </select>
                <div style="display:flex; align-items:center; gap:6px;">
                  <span style="font-size:0.78rem; font-weight:600; color:var(--forest-green); white-space:nowrap;">Date Filter:</span>
                  <input type="date" id="filter-order-date" class="filter-input" style="padding:6px 10px; font-size:0.82rem;" onchange="renderOrdersTable()">
                  <button class="btn-secondary" style="padding:6px 10px; font-size:0.75rem;" onclick="clearOrderDateFilter()" title="Clear date selection">Clear</button>
                </div>
              </div>
            </div>

            <div class="table-responsive">
              <table class="luxury-table">
                <thead>
                  <tr>
                    <th>Order #</th>
                    <th>Customer Name</th>
                    <th>Order Date</th>
                    <th>Items</th>
                    <th>Total Value</th>
                    <th>Status</th>
                    <th>Payment</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody id="orders-table-tbody">

<?php foreach ($allOrders as $o): ?>
  <tr>
    <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
    <td>
      <div style="font-weight:600;"><?= htmlspecialchars($o['customer_name']) ?></div>
      <div style="font-size:0.75rem; color:var(--color-text-muted);"><?= htmlspecialchars($o['customer_email']) ?></div>
    </td>
    <td><?= htmlspecialchars($o['order_date']) ?></td>
    <td><?= (int)$o['items_count'] ?> piece(s)</td>
    <td><strong><?= format_currency($o['total_amount']) ?></strong></td>
    <td><span class="status-pill status-<?= strtolower(str_replace(' ', '', $o['status'])) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
    <?php $paymentStatus = (string)($o['payment_status'] ?? 'Unpaid'); $paymentClass = $paymentStatus === 'Paid' ? 'completed' : ($paymentStatus === 'Refunded' ? 'processing' : 'pending'); ?>
    <td><span class="status-pill status-<?= $paymentClass ?>"><?= htmlspecialchars($paymentStatus) ?></span></td>
    <td>
      <button class="btn-secondary" style="padding:5px 12px; font-size:0.8rem;" onclick="openOrderDrawerManual('<?= htmlspecialchars($o['order_code']) ?>')">
        View Details
      </button>
    </td>
  </tr>
<?php endforeach; ?>

                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==================================================================
             VIEW 6: CUSTOMER MANAGEMENT
             ================================================================== -->
        <section class="page-view" id="view-customers">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Customer Management</h2>
              <p>Customer profiles, order history &amp; account details</p>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
              <button class="btn-luxury-primary" onclick="openAddCustomerModal()">
                <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Customer
              </button>
            </div>
          </div>

          <!-- Customer Management Search & Filter Toolbar -->
          <div class="customer-toolbar-wrapper">
            <div class="customer-filters-row">
              <div class="customer-search-box">
                <svg class="customer-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input type="text" id="cust-search-input" placeholder="Search customers by name, email, phone, city..." oninput="handleCustomerSearch(this.value)">
              </div>

              <div class="customer-filter-group">
                <select id="cust-filter-status" onchange="handleCustomerFilterChange()">
                  <option value="all">All Registered Customers</option>
                  <option value="active">Active Accounts</option>
                </select>

                <select id="cust-filter-delivery" onchange="handleCustomerFilterChange()">
                  <option value="all">All Delivery States</option>
                  <option value="pending">Awaiting Delivery</option>
                  <option value="fulfilled">All Fulfilled</option>
                </select>

                <select id="cust-filter-sort" onchange="handleCustomerFilterChange()">
                  <option value="registered_desc">Sort: Newest Registered</option>
                  <option value="spend_desc">Sort: Highest Spend</option>
                  <option value="orders_desc">Sort: Most Orders</option>
                  <option value="name_asc">Sort: Name (A &rarr; Z)</option>
                </select>

                <button class="btn-secondary" onclick="resetCustomerFilters()" style="padding:8px 12px; font-size:0.8rem;" title="Reset all customer search and filters">
                  Reset
                </button>
              </div>
            </div>
          </div>

          <div class="table-card">
            <div class="table-responsive">
              <table class="luxury-table">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Account Status</th>
                    <th>Registered Date</th>
                    <th>Delivery Status</th>
                    <th>Lifetime Spend</th>
                    <th>Total Orders</th>
                    <th style="text-align:right;">Action</th>
                  </tr>
                </thead>
                <tbody id="customers-table-tbody">

<?php foreach ($allCustomers as $c): 
  $initials = strtoupper(substr($c['name'], 0, 1) . (strpos($c['name'], ' ') !== false ? substr($c['name'], strpos($c['name'], ' ') + 1, 1) : 'K'));
  $regDateFormatted = date('d M Y', strtotime($c['registered_date']));
  $isNewSignup = (date('Y', strtotime($c['registered_date'])) >= '2026');
?>
  <tr class="clickable-row" onclick="openCustomerDrawerManual('<?= htmlspecialchars($c['cust_code']) ?>')" title="Click to view customer details">
    <td>
      <div style="display:flex; align-items:center; gap:10px;">
        <div class="user-avatar" style="width:38px; height:38px; font-size:0.82rem; background:var(--forest-green); color:var(--champagne-beige); border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700; flex-shrink:0;">
          <?= $initials ?>
        </div>
        <div>
          <div style="font-weight:700; color:var(--deep-charcoal-green); display:flex; align-items:center; gap:6px;">
            <span><?= htmlspecialchars($c['name']) ?></span>
            <span style="font-size:0.7rem; font-weight:600; color:var(--color-text-subtle);">#<?= htmlspecialchars($c['cust_code']) ?></span>
          </div>
          <div style="font-size:0.74rem; color:var(--color-text-muted);"><?= htmlspecialchars($c['email']) ?> <?= !empty($c['phone']) ? '&bull; ' . htmlspecialchars($c['phone']) : '' ?></div>
        </div>
      </div>
    </td>
    <td>
      <?php if ($c['is_blocked']): ?>
        <span class="status-pill status-out-of-stock">&#10008; Blocked</span>
      <?php else: ?>
        <span class="customer-badge-active">&#10003; Active</span>
      <?php endif; ?>
    </td>
    <td>
      <div class="reg-date-cell">
        <span><?= $regDateFormatted ?></span>
        <?php if ($isNewSignup): ?>
          <span class="status-pill status-optimal" style="font-size:0.65rem; padding:1px 6px; margin-left:4px;">New 2026</span>
        <?php endif; ?>
      </div>
    </td>
    <td><span class="status-pill status-optimal">&#10004; Fulfilled</span></td>
    <td><strong style="color:var(--forest-green); font-size:0.92rem;"><?= format_currency($c['total_spent']) ?></strong></td>
    <td><strong><?= (int)$c['total_orders'] ?></strong> order(s)</td>
    <td style="text-align:right;">
      <button class="btn-luxury-primary" style="font-size:0.75rem; padding:5px 12px;" onclick="event.stopPropagation(); openCustomerDrawerManual('<?= htmlspecialchars($c['cust_code']) ?>')">
        View Details
      </button>
    </td>
  </tr>
<?php endforeach; ?>

                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==================================================================
             VIEW 6B: CUSTOMER REVIEWS
             ================================================================== -->
        <section class="page-view" id="view-reviews">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Customer Reviews</h2>
              <p>View and manage product reviews left by your customers</p>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
              <button class="btn-secondary" onclick="window.location.href='actions/export_csv.php?type=reviews'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export CSV
              </button>
              <button class="btn-luxury-primary" onclick="openModal('add-review-modal')">
                <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Review
              </button>
            </div>
          </div>

          <!-- Reviews KPI Stats -->
          <div class="dashboard-kpi-grid" style="margin-bottom:24px; max-width:650px;">
            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Total Reviews</span>
                <div class="kpi-icon-wrap" style="width:38px; height:38px; border-radius:10px; background:var(--champagne-beige); color:var(--forest-green); display:flex; align-items:center; justify-content:center;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
              </div>
              <div class="kpi-value"><?= number_format($reviewStats['total']) ?></div>
            </div>

            <div class="kpi-card">
              <div class="kpi-top">
                <span class="kpi-label">Avg. Rating</span>
                <div class="kpi-icon-wrap" style="width:38px; height:38px; border-radius:10px; background:#fff9e6; color:var(--gold-accent); display:flex; align-items:center; justify-content:center;">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" stroke="none"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
                </div>
              </div>
              <div class="kpi-value" style="color:var(--antique-copper);"><?= number_format($reviewStats['avg_rating'], 1) ?> ★</div>
            </div>
          </div>

          <!-- Reviews Filter Toolbar -->
          <div class="customer-toolbar-wrapper">
            <div class="customer-filters-row">
              <div class="customer-search-box">
                <svg class="customer-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="reviews-search-input" placeholder="Search by customer, product, or keyword..." oninput="handleReviewSearch(this.value)">
              </div>
              <div class="customer-filter-group">
                <select id="reviews-filter-rating" onchange="handleReviewFilterChange()">
                  <option value="all">All Ratings</option>
                  <option value="5">5 Stars ★★★★★</option>
                  <option value="4">4 Stars ★★★★</option>
                  <option value="3">3 Stars ★★★</option>
                  <option value="2">2 Stars ★★</option>
                  <option value="1">1 Star ★</option>
                </select>
                <button class="btn-secondary" onclick="resetReviewFilters()" style="padding:8px 12px; font-size:0.8rem;" title="Reset filters">Reset</button>
              </div>
            </div>
          </div>

          <!-- Reviews Table -->
          <div class="table-card">
            <div class="table-responsive">
              <table class="luxury-table" id="reviews-main-table">
                <thead>
                  <tr>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Rating</th>
                    <th>Review</th>
                    <th>Date</th>
                  </tr>
                </thead>
                <tbody id="reviews-table-tbody">
<?php foreach ($allReviews as $rv):
  $stars = str_repeat('★', (int)$rv['rating']) . str_repeat('☆', 5 - (int)$rv['rating']);
  $reviewDate = date('d M Y', strtotime($rv['created_at']));
  $initials = strtoupper(substr($rv['customer_name'], 0, 1) . (strpos($rv['customer_name'], ' ') !== false ? substr($rv['customer_name'], strpos($rv['customer_name'], ' ') + 1, 1) : 'K'));
?>
                  <tr data-review-rating="<?= (int)$rv['rating'] ?>">
                    <td>
                      <div style="display:flex; align-items:center; gap:9px;">
                        <div style="width:34px; height:34px; border-radius:50%; background:var(--forest-green); color:var(--champagne-beige); display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.78rem; flex-shrink:0;"><?= $initials ?></div>
                        <div>
                          <div style="font-weight:700; color:var(--deep-charcoal-green); font-size:0.86rem;"><?= htmlspecialchars($rv['customer_name']) ?></div>
                          <div style="font-size:0.72rem; color:var(--color-text-muted);"><?= htmlspecialchars($rv['customer_email']) ?></div>
                        </div>
                      </div>
                    </td>
                    <td>
                      <div style="font-weight:600; color:var(--deep-charcoal-green); font-size:0.84rem; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?= htmlspecialchars($rv['product_name']) ?></div>
                    </td>
                    <td>
                      <span style="color:var(--antique-copper); font-size:1rem; letter-spacing:1px;" title="<?= (int)$rv['rating'] ?> out of 5"><?= $stars ?></span>
                      <span style="font-size:0.73rem; color:var(--color-text-muted); margin-left:4px;"><?= (int)$rv['rating'] ?>/5</span>
                    </td>
                    <td>
                      <?php if (!empty($rv['title'])): ?>
                        <div style="font-weight:600; font-size:0.83rem; color:var(--deep-charcoal-green); margin-bottom:2px;"><?= htmlspecialchars($rv['title']) ?></div>
                      <?php endif; ?>
                      <div style="font-size:0.76rem; color:var(--color-text-muted); line-height:1.4;" title="<?= htmlspecialchars($rv['review_text'] ?? '') ?>"><?= htmlspecialchars($rv['review_text'] ?? '') ?></div>
                    </td>
                    <td style="font-size:0.8rem; color:var(--color-text-muted); white-space:nowrap;"><?= $reviewDate ?></td>
                  </tr>
<?php endforeach; ?>
                  <?php if (empty($allReviews)): ?>
                  <tr><td colspan="5" style="text-align:center; padding:40px; color:var(--color-text-muted); font-size:0.9rem;">No customer reviews yet. Reviews submitted from the storefront will appear here.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==================================================================
             VIEW 7: COUPONS & OFFERS
             ================================================================== -->

        <section class="page-view" id="view-coupons">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Coupons & Offers</h2>
              <p>Manage discount coupons and promotional offers</p>
            </div>
            <button class="btn-luxury-primary" onclick="openAddCouponModal()">
              <svg viewBox="0 0 24 24" fill="none"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Create Coupon
            </button>
          </div>

          <div class="coupons-grid" id="coupons-grid-container">

<?php foreach ($allCoupons as $cp): ?>
  <div class="coupon-ticket-card">
    <div class="coupon-top">
      <div class="coupon-code-badge"><?= htmlspecialchars($cp['code']) ?></div>
      <span class="status-pill <?= ($cp['status'] === 'Active') ? 'status-active' : 'status-danger' ?>"><?= htmlspecialchars($cp['status']) ?></span>
    </div>
    <div class="coupon-discount-value">
      <?= ($cp['discount_type'] === 'Percentage') ? ((int)$cp['value'] . '% OFF') : (format_currency($cp['value']) . ' OFF') ?>
    </div>
    <div class="coupon-rules">
      <div><strong>Min Order:</strong> <?= format_currency($cp['min_order']) ?></div>
      <div><strong>Applies to:</strong> <?= htmlspecialchars($cp['applicable_to']) ?></div>
      <div><strong>Redemptions:</strong> <?= (int)$cp['used_count'] ?> / <?= (int)$cp['usage_limit'] ?></div>
    </div>
    <div class="coupon-expiry">
      <span>Ends: <strong><?= !empty($cp['expires_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime($cp['expires_at']))) : 'No end date' ?></strong></span>
      <a href="actions/coupon_action.php?action=delete&id=<?= $cp['id'] ?>" class="action-icon-btn btn-delete" onclick="return confirm('Delete coupon <?= htmlspecialchars(addslashes($cp['code'])) ?>?');" title="Delete coupon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      </a>
    </div>
  </div>
<?php endforeach; ?>

            </div>
        </section>

        <!-- ==================================================================
             VIEW 8: REPORTS & ANALYTICS (Jewellery E-Commerce Requirements • Page 7)
             ================================================================== -->
        <section class="page-view" id="view-reports">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Financial Reports & Analytics</h2>
              <p>Overview of sales, revenue, orders, customers, inventory, and coupons</p>
            </div>
            <div style="display:flex; gap:10px;">
              <button class="btn-secondary" onclick="exportOrdersCSV()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                Export CSV
              </button>
              <button class="btn-copper" onclick="window.print()">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                Print Report
              </button>
            </div>
          </div>

          <!-- Reports Toolbar (Report Selection & Date Filtering) -->
          <div class="reports-toolbar-wrapper">
            <div class="reports-date-filter-row">
              <!-- Report Selector Dropdown -->
              <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <label for="report-section-select" style="font-size:0.84rem; font-weight:700; color:var(--deep-charcoal-green); display:flex; align-items:center; gap:6px;">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1"/><rect x="14" y="3" width="7" height="5" rx="1"/><rect x="14" y="12" width="7" height="9" rx="1"/><rect x="3" y="16" width="7" height="5" rx="1"/></svg>
                  Report Overview:
                </label>
                <select id="report-section-select" class="filter-select" onchange="switchReportTab(this.value)" style="min-width:240px; font-weight:600; padding:8px 14px; border-radius:8px; border:1px solid var(--border-gold); background:var(--warm-ivory); font-size:0.86rem; color:var(--deep-charcoal-green);">
                  <option value="all">All Reports Overview</option>
                  <option value="sales">Sales Report</option>
                  <option value="revenue">Revenue Report</option>
                  <option value="orders">Order Report</option>
                  <option value="customers">Customer Report</option>
                  <option value="performance">Product Performance</option>
                  <option value="bestsellers">Best-Selling Products</option>
                  <option value="inventory">Inventory Report</option>
                  <option value="coupons">Coupon Usage</option>
                </select>
              </div>

              <!-- Date Filter Dropdown & Custom Range -->
              <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
                <div style="display:flex; align-items:center; gap:8px;">
                  <label for="report-date-select" style="font-size:0.84rem; font-weight:700; color:var(--forest-green); display:flex; align-items:center; gap:5px;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    Date Filter:
                  </label>
                  <select id="report-date-select" class="filter-select" onchange="setReportDatePreset(this.value)" style="min-width:160px; font-weight:600; padding:8px 12px; border-radius:8px; border:1px solid var(--border-beige); background:var(--warm-ivory); font-size:0.85rem; color:var(--deep-charcoal-green);">
                    <option value="all">All Time</option>
                    <option value="today">Today</option>
                    <option value="7days">Last 7 Days</option>
                    <option value="30days">Last 30 Days</option>
                    <option value="thisMonth">This Month</option>
                    <option value="thisYear">This Year</option>
                  </select>
                </div>

                <div class="reports-custom-date-group">
                  <label>From:</label>
                  <input type="date" id="report-filter-start" value="2026-01-01">
                  <label>To:</label>
                  <input type="date" id="report-filter-end" value="2026-12-31">
                  <button class="btn-luxury-primary" style="padding:6px 14px; font-size:0.78rem;" onclick="applyCustomDateFilter()">Apply Filter</button>
                  <button class="btn-secondary" style="padding:6px 12px; font-size:0.78rem;" onclick="resetReportDateFilter()">Reset</button>
                </div>
              </div>
            </div>

            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
              <div class="reports-filter-status-tag" id="report-filter-status-tag">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <span id="report-filter-status-text">Active Filter: All Records</span>
              </div>
              <span style="font-size:0.76rem; color:var(--color-text-muted);">
                Data synchronized with Kinz Jewellery Inventory &amp; Order Pipeline
              </span>
            </div>
          </div>

          <!-- PANE 1: ALL REPORTS OVERVIEW -->
          <div class="report-pane active-pane" id="pane-report-all">
            <!-- Best Sellers Spotlight Podium -->
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Top Best-Selling Products
                </h3>
                <button class="btn-secondary" style="font-size:0.75rem; padding:4px 10px;" onclick="switchReportTab('bestsellers')">View All Rankings &rarr;</button>
              </div>
              <div style="padding:20px;">
                <div class="podium-grid" id="report-overview-podium">
<?php 
$podiumMedals = ['🥇', '🥈', '🥉'];
if (!empty($bestSellers)):
  foreach (array_slice($bestSellers, 0, 3) as $bIdx => $bp):
?>
      <div class="podium-card rank-<?= $bIdx + 1 ?>">
        <span class="podium-rank-badge"><?= $podiumMedals[$bIdx] ?? '#' . ($bIdx + 1) ?></span>
        <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
          <?php if (!empty($bp['image'])): ?>
            <img src="<?= htmlspecialchars($bp['image']) ?>" alt="<?= htmlspecialchars($bp['title']) ?>" class="jewel-thumbnail" style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:1px solid rgba(200, 121, 39, 0.3); flex-shrink:0;">
          <?php else: ?>
            <div class="jewel-thumbnail blank-image-slot" style="width:52px; height:52px; border-radius:8px; background:var(--champagne-beige); border:1px solid rgba(200, 121, 39, 0.3); flex-shrink:0;"></div>
          <?php endif; ?>
          <div>
            <h4 style="margin:0; font-size:0.96rem; color:var(--deep-charcoal-green); font-family:var(--font-serif);"><?= htmlspecialchars($bp['title']) ?></h4>
            <span style="font-size:0.74rem; color:var(--color-text-muted); font-family:monospace;"><?= htmlspecialchars($bp['sku']) ?> &bull; <?= htmlspecialchars($bp['category']) ?></span>
          </div>
        </div>
        <div style="font-size:0.84rem; display:flex; justify-content:space-between; margin-bottom:6px;">
          <span>Units Sold: <strong><?= (int)$bp['sales_count'] ?></strong></span>
          <span>Revenue: <strong style="color:var(--forest-green);"><?= format_currency($bp['revenue_generated'] ?: ($bp['base_price'] * $bp['sales_count'])) ?></strong></span>
        </div>
        <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-beige); padding-top:8px; margin-top:8px;">
          <span class="guarantee-badge"><?= htmlspecialchars($bp['guarantee'] ?: '1 Year Guarantee') ?></span>
          <span style="font-size:0.75rem; color:var(--color-text-muted);"><?= (int)$bp['stock'] ?> in stock</span>
        </div>
      </div>
<?php 
  endforeach;
else: 
?>
  <div style="grid-column: 1 / -1; text-align:center; padding:32px; color:var(--color-text-muted);">
    <p style="margin:0; font-size:0.9rem;">No sales data recorded yet. Best-selling rankings will populate as orders are completed.</p>
  </div>
<?php endif; ?>
                </div>
              </div>
            </div>

            <!-- Top Products Summary Table -->
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Product Performance &amp; Revenue
                </h3>
                <button class="btn-secondary" style="font-size:0.75rem; padding:4px 10px;" onclick="switchReportTab('performance')">Full Performance &rarr;</button>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Product &amp; SKU</th>
                      <th>Category</th>
                      <th>Units Sold</th>
                      <th>Total Revenue</th>
                      <th>Current Stocks</th>
                      <th>Performance</th>
                    </tr>
                  </thead>
                  <tbody id="report-top-products-tbody">
<?php foreach (array_slice($bestSellers, 0, 8) as $idx => $p): ?>
  <tr>
    <td><strong>#<?= $idx + 1 ?></strong></td>
    <td>
      <div style="display:flex; align-items:center; gap:10px;">
        <?php if (!empty($p['image'])): ?>
          <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="jewel-thumbnail" style="width:36px; height:36px; border-radius:6px; object-fit:cover; border:1px solid var(--border-gold); flex-shrink:0;">
        <?php else: ?>
          <div class="jewel-thumbnail blank-image-slot" style="width:36px; height:36px; border-radius:6px; background:var(--champagne-beige); border:1px solid var(--border-gold); flex-shrink:0;"></div>
        <?php endif; ?>
        <div>
          <div style="font-weight:600; color:var(--deep-charcoal-green);"><?= htmlspecialchars($p['title']) ?></div>
          <div style="font-size:0.72rem; color:var(--color-text-muted); font-family:monospace;"><?= htmlspecialchars($p['sku']) ?></div>
        </div>
      </div>
    </td>
    <td><strong><?= htmlspecialchars($p['category']) ?></strong></td>
    <td><?= (int)$p['sales_count'] ?> units</td>
    <td><strong style="color:var(--forest-green);"><?= format_currency($p['revenue_generated'] ?: ($p['base_price'] * $p['sales_count'])) ?></strong></td>
    <td><?= (int)$p['stock'] ?> units</td>
    <td><span class="status-pill status-optimal"><?= ($idx < 3) ? 'Top Performer' : 'Stable Seller' ?></span></td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PANE 2: SALES REPORT -->
          <div class="report-pane" id="pane-report-sales">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Sales Volume by Payment Method
                </h3>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Payment Method</th>
                      <th>Orders Processed</th>
                      <th>Total Amount</th>
                      <th>Share of Total</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="report-sales-gateway-tbody">
<?php
$gatewayCounts = [];
$gatewayRevenue = [];
$totalGwRev = 0;
foreach ($allOrders as $o) {
    $gw = !empty($o['payment_method']) ? $o['payment_method'] : 'UPI Direct';
    $gatewayCounts[$gw] = ($gatewayCounts[$gw] ?? 0) + 1;
    $gatewayRevenue[$gw] = ($gatewayRevenue[$gw] ?? 0) + (float)$o['total_amount'];
    $totalGwRev += (float)$o['total_amount'];
}
if ($totalGwRev <= 0) $totalGwRev = 1;
foreach ($gatewayRevenue as $gwName => $gwAmt):
    $gwCount = $gatewayCounts[$gwName] ?? 0;
    $pct = number_format(($gwAmt / $totalGwRev) * 100, 1);
?>
  <tr>
    <td><strong><?= htmlspecialchars($gwName) ?></strong></td>
    <td><?= $gwCount ?> order(s)</td>
    <td><strong><?= format_currency($gwAmt) ?></strong></td>
    <td>
      <div style="display:flex; align-items:center; gap:8px;">
        <div style="background:var(--border-beige); border-radius:4px; height:8px; width:70px; overflow:hidden;">
          <div style="background:var(--antique-copper); height:100%; width:<?= $pct ?>%;"></div>
        </div>
        <span style="font-size:0.75rem; font-weight:600;"><?= $pct ?>%</span>
      </div>
    </td>
    <td><span class="status-pill status-optimal">Active</span></td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PANE 3: REVENUE REPORT -->
          <div class="report-pane" id="pane-report-revenue">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Revenue by Category
                </h3>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Category</th>
                      <th>Total Products</th>
                      <th>Units Sold</th>
                      <th>Total Revenue</th>
                      <th>Revenue Share (%)</th>
                    </tr>
                  </thead>
                  <tbody id="report-category-revenue-tbody">
<?php
$catRevSql = "SELECT category, COUNT(*) as count, SUM(sales_count) as units, SUM(revenue_generated) as rev FROM products GROUP BY category";
$catRevs = db_fetch_all($catRevSql);
$totCatRev = array_sum(array_column($catRevs, 'rev')) ?: 1;
foreach ($catRevs as $cr):
    $crPct = number_format(($cr['rev'] / $totCatRev) * 100, 1);
?>
  <tr>
    <td><strong><?= htmlspecialchars($cr['category']) ?></strong></td>
    <td><strong><?= format_currency($cr['rev']) ?></strong></td>
    <td><?= $crPct ?>%</td>
    <td><?= (int)$cr['units'] ?> units sold</td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PANE 4: ORDER REPORT -->
          <div class="report-pane" id="pane-report-orders">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Orders Log &amp; Status
                </h3>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>Customer Name</th>
                      <th>Date</th>
                      <th>Items Purchased</th>
                      <th>Total Value</th>
                      <th>Status</th>
                      <th>Tracking ID</th>
                    </tr>
                  </thead>
                  <tbody id="report-orders-tbody">
<?php foreach ($allOrders as $o): ?>
  <tr>
    <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
    <td>
      <strong><?= htmlspecialchars($o['customer_name']) ?></strong>
      <div style="font-size:0.74rem; color:var(--color-text-muted);"><?= htmlspecialchars($o['customer_email']) ?></div>
    </td>
    <td><?= htmlspecialchars($o['order_date']) ?></td>
    <td><?= (int)$o['items_count'] ?> item(s)</td>
    <td><strong><?= format_currency($o['total_amount']) ?></strong></td>
    <td><span class="status-pill status-<?= strtolower(str_replace(' ', '', $o['status'])) ?>"><?= htmlspecialchars($o['status']) ?></span></td>
    <td><span style="font-family:monospace; font-size:0.78rem;"><?= htmlspecialchars($o['tracking_number'] ?: 'Pending Pickup') ?></span></td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PANE 5: CUSTOMER REPORT -->
          <div class="report-pane" id="pane-report-customers">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Customer Report
                </h3>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Customer Name &amp; Email</th>
                      <th>Lifetime Spend</th>
                      <th>Total Orders</th>
                    </tr>
                  </thead>
                  <tbody id="report-customers-tbody">
<?php foreach ($allCustomers as $c): ?>
  <tr>
    <td>
      <strong><?= htmlspecialchars($c['name']) ?></strong>
      <div style="font-size:0.74rem; color:var(--color-text-muted);"><?= htmlspecialchars($c['email']) ?></div>
    </td>
    <td><strong style="color:var(--forest-green);"><?= format_currency($c['total_spent']) ?></strong></td>
    <td><?= (int)$c['total_orders'] ?> order(s)</td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PANE 6: PRODUCT PERFORMANCE -->
          <div class="report-pane" id="pane-report-performance">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Product Performance
                </h3>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Product &amp; SKU</th>
                      <th>Category</th>
                      <th>Units Sold</th>
                      <th>Total Revenue</th>
                      <th>Stock Status</th>
                    </tr>
                  </thead>
                  <tbody id="report-performance-tbody">
<?php foreach ($allProducts as $p): ?>
  <tr>
    <td>
      <div style="display:flex; align-items:center; gap:8px;">
        <?php if (!empty($p['image'])): ?>
          <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" style="width:32px; height:32px; border-radius:4px; object-fit:cover;">
        <?php else: ?>
          <div class="jewel-thumbnail blank-image-slot" style="width:32px; height:32px; border-radius:4px; background:var(--champagne-beige); border:1px solid var(--border-gold); flex-shrink:0;"></div>
        <?php endif; ?>
        <div>
          <strong><?= htmlspecialchars($p['title']) ?></strong>
          <div style="font-size:0.72rem; color:var(--color-text-muted); font-family:monospace;"><?= htmlspecialchars($p['sku']) ?></div>
        </div>
      </div>
    </td>
    <td><?= htmlspecialchars($p['category']) ?></td>
    <td><strong><?= (int)$p['sales_count'] ?></strong></td>
    <td><strong style="color:var(--forest-green);"><?= format_currency($p['revenue_generated'] ?: ($p['base_price'] * $p['sales_count'])) ?></strong></td>
    <td><span class="status-pill <?= ($p['stock'] <= $p['reorder_level']) ? 'status-low-stock' : 'status-optimal' ?>"><?= (int)$p['stock'] ?> units</span></td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PANE 7: BEST-SELLING PRODUCTS -->
          <div class="report-pane" id="pane-report-bestsellers">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Top Best-Selling Products
                </h3>
              </div>
              <div style="padding:20px;">
                <div class="podium-grid" id="report-bestsellers-podium-full">
<?php 
$medalsAll = ['🥇 1st', '🥈 2nd', '🥉 3rd', '#4', '#5', '#6'];
if (!empty($bestSellers)):
  foreach (array_slice($bestSellers, 0, 6) as $idx => $p): 
?>
  <div class="podium-card rank-<?= $idx + 1 ?>">
    <span class="podium-rank-badge" style="font-size:0.88rem; font-weight:700; color:var(--forest-green);"><?= $medalsAll[$idx] ?? '#' . ($idx + 1) ?></span>
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
      <?php if (!empty($p['image'])): ?>
        <img src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="jewel-thumbnail" style="width:52px; height:52px; border-radius:8px; object-fit:cover; border:1px solid rgba(200, 121, 39, 0.3); flex-shrink:0;">
      <?php else: ?>
        <div class="jewel-thumbnail blank-image-slot" style="width:52px; height:52px; border-radius:8px; background:var(--champagne-beige); border:1px solid rgba(200, 121, 39, 0.3); flex-shrink:0;"></div>
      <?php endif; ?>
      <div>
        <h4 style="margin:0; font-size:0.96rem; color:var(--deep-charcoal-green); font-family:var(--font-serif);"><?= htmlspecialchars($p['title']) ?></h4>
        <span style="font-size:0.74rem; color:var(--color-text-muted); font-family:monospace;"><?= htmlspecialchars($p['sku']) ?> &bull; <?= htmlspecialchars($p['category']) ?></span>
      </div>
    </div>
    <div style="font-size:0.84rem; display:flex; justify-content:space-between; margin-bottom:6px;">
      <span>Units Sold: <strong><?= (int)$p['sales_count'] ?></strong></span>
      <span>Revenue: <strong style="color:var(--forest-green);"><?= format_currency($p['revenue_generated'] ?: ($p['base_price'] * $p['sales_count'])) ?></strong></span>
    </div>
    <div style="display:flex; justify-content:space-between; align-items:center; border-top:1px solid var(--border-beige); padding-top:8px; margin-top:8px;">
      <span class="guarantee-badge"><?= htmlspecialchars($p['guarantee'] ?: '1 Year Guarantee') ?></span>
      <span style="font-size:0.75rem; color:var(--color-text-muted);"><?= (int)$p['stock'] ?> in stock</span>
    </div>
  </div>
<?php 
  endforeach;
else:
?>
  <div style="grid-column: 1 / -1; text-align:center; padding:36px; color:var(--color-text-muted);">
    <p style="margin:0; font-size:0.92rem;">No best-selling products yet. Rankings will appear as products are purchased.</p>
  </div>
<?php endif; ?>
                </div>
              </div>
            </div>
          </div>

          <!-- PANE 8: INVENTORY REPORT -->
          <div class="report-pane" id="pane-report-inventory">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Stock Levels &amp; Reorder Alerts
                </h3>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Product &amp; SKU</th>
                      <th>Category</th>
                      <th>Stock Count</th>
                      <th>Reorder Level</th>
                      <th>Sales Velocity</th>
                      <th>Stock Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody id="report-inventory-tbody">
<?php foreach ($allProducts as $p): 
  $isOut = ($p['stock'] == 0);
  $isLow = ($p['stock'] <= $p['reorder_level'] && !$isOut);
  $healthClass = $isOut ? 'status-out-of-stock' : ($isLow ? 'status-low-stock' : 'status-optimal');
  $healthText = $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'Optimal Stock');
  $velocity = ($p['sales_count'] > 120) ? 'Fast-Moving (High Demand)' : (($p['sales_count'] > 80) ? 'Standard Turnover' : 'Moderate');
?>
  <tr>
    <td>
      <strong><?= htmlspecialchars($p['title']) ?></strong>
      <div style="font-size:0.74rem; color:var(--color-text-muted); font-family:monospace;"><?= htmlspecialchars($p['sku']) ?></div>
    </td>
    <td><?= htmlspecialchars($p['category']) ?></td>
    <td><strong style="font-size:1.05rem;"><?= (int)$p['stock'] ?></strong> units</td>
    <td><?= (int)$p['reorder_level'] ?> units</td>
    <td><span style="font-size:0.8rem; font-weight:600; color:var(--forest-green);"><?= $velocity ?></span></td>
    <td><span class="status-pill <?= $healthClass ?>"><?= $healthText ?></span></td>
    <td>
      <button class="btn-secondary" style="font-size:0.74rem; padding:3px 8px;" onclick="openStockAdjustModal('<?= htmlspecialchars($p['sku']) ?>')">
        Restock
      </button>
    </td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- PANE 9: COUPON USAGE -->
          <div class="report-pane" id="pane-report-coupons">
            <div class="table-card" style="margin-bottom:24px;">
              <div class="table-toolbar">
                <h3 style="font-family:var(--font-serif); font-size:1.15rem; color:var(--deep-charcoal-green);">
                  Coupon Usage &amp; Discounts
                </h3>
              </div>
              <div class="table-responsive">
                <table class="luxury-table">
                  <thead>
                    <tr>
                      <th>Promo Code</th>
                      <th>Discount Offer</th>
                      <th>Min Order</th>
                      <th>Times Redeemed</th>
                      <th>Total Discount</th>
                      <th>Expiration Date</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody id="report-coupons-tbody">
<?php foreach ($allCoupons as $cp): 
  $discountsAwarded = ($cp['discount_type'] === 'Percentage') 
    ? ($cp['used_count'] * ($cp['min_order'] * ($cp['value'] / 100))) 
    : ($cp['used_count'] * $cp['value']);
?>
  <tr>
    <td><strong style="font-family:monospace; color:var(--antique-copper);"><?= htmlspecialchars($cp['code']) ?></strong></td>
    <td><?= ($cp['discount_type'] === 'Percentage') ? ((int)$cp['value'] . '% OFF') : (format_currency($cp['value']) . ' OFF') ?></td>
    <td><?= format_currency($cp['min_order']) ?></td>
    <td><strong><?= (int)$cp['used_count'] ?></strong> / <?= (int)$cp['usage_limit'] ?></td>
    <td><strong style="color:var(--forest-green);"><?= format_currency($discountsAwarded) ?></strong></td>
    <td><?= !empty($cp['expires_at']) ? htmlspecialchars(date('d M Y, h:i A', strtotime($cp['expires_at']))) : '—' ?></td>
    <td><span class="status-pill status-<?= strtolower($cp['status']) ?>"><?= htmlspecialchars($cp['status']) ?></span></td>
  </tr>
<?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

        </section>

        <!-- ==================================================================
             VIEW 9: ADMIN USERS
             ================================================================== -->
        <section class="page-view" id="view-users">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Admin Users</h2>
              <p>Store administrators and staff accounts</p>
            </div>
            <div class="page-actions">
              <button type="button" class="btn-luxury-primary" onclick="openAddAdminModal()">
                + Add New Admin
              </button>
            </div>
          </div>

          <div class="table-card">
            <div class="table-responsive">
              <table class="luxury-table">
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Last Active</th>
                    <th style="text-align:right;">Edit Profile</th>
                  </tr>
                </thead>
                <tbody id="admin-users-tbody">

<?php foreach ($allAdminUsers as $u): ?>
  <tr>
    <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
    <td><?= htmlspecialchars($u['email']) ?></td>
    <td><?= htmlspecialchars($u['last_login'] ?: 'Recently') ?></td>
    <td style="text-align:right;">
      <button type="button" class="btn-luxury-primary" style="font-size:0.75rem; padding:6px 14px;" onclick="openEditAdminModalManual('<?= $u['id'] ?>', '<?= htmlspecialchars(addslashes($u['name'])) ?>', '<?= htmlspecialchars(addslashes($u['email'])) ?>')">
        Edit Profile
      </button>
    </td>
  </tr>
<?php endforeach; ?>

                </tbody>
              </table>
            </div>
          </div>
        </section>

        <!-- ==================================================================
             VIEW 10: SETTINGS
             ================================================================== -->
        <section class="page-view" id="view-settings">
          <div class="page-header-bar">
            <div class="page-title-group">
              <h2>Settings</h2>
              <p>Store information and business details</p>
            </div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 24px; max-width: 850px;">

            <!-- Store Information Card -->
            <div class="dashboard-panel">
              <div style="display:flex; align-items:center; gap:12px; margin-bottom:18px; border-bottom:1px solid var(--border-beige); padding-bottom:12px;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="var(--forest-green)" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <h3 style="margin:0; font-family:var(--font-serif); font-size:1.2rem; color:var(--deep-charcoal-green);">Store Information</h3>
              </div>

              <!-- Official Brand Showcase -->
              <div class="settings-brand-banner" style="display:flex; align-items:center; gap:20px; padding:18px 22px; margin-bottom:24px; background:linear-gradient(135deg, #0A3D2E 0%, #062C22 100%); border-radius:12px; border:1px solid rgba(200, 121, 39, 0.45); box-shadow:0 6px 20px rgba(6,44,34,0.18); flex-wrap:wrap;">
                <div style="position:relative; width:72px; height:72px; flex-shrink:0;">
                  <img src="assets/kinz_logo.jpg" alt="Official Kinz Logo" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2.5px solid var(--gold-400); box-shadow:0 0 20px rgba(200,121,39,0.5);">
                </div>
                <div style="flex:1;">
                  <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                    <h3 style="margin:0; font-family:var(--font-royal); color:var(--champagne-beige); font-size:1.25rem; letter-spacing:0.06em;">KINZ JEWELLERY</h3>
                    <span class="status-pill status-active" style="background:rgba(200,121,39,0.2); color:#F4E2C5; border:1px solid var(--gold-400); font-size:0.7rem; padding:2px 8px;">Active Logo</span>
                  </div>
                  <p style="margin:0; font-size:0.84rem; color:var(--champagne-beige); opacity:0.85; line-height:1.4;">
                    &ldquo;A Hidden Treasure&rdquo; &bull; Deep Emerald &amp; Imperial Gold Royal Medallion
                  </p>
                </div>
              </div>

              <form action="actions/settings_action.php" method="POST">
              <div class="form-grid-2">
                <div class="form-group">
                  <label>Store Name</label>
                  <input type="text" name="store_name" id="setting-store-name" class="form-control" value="<?= htmlspecialchars($settings['store_name'] ?? 'Kinz Guarantee Jewellery Store') ?>" required>
                </div>
                <div class="form-group">
                  <label>Store Email</label>
                  <input type="email" name="support_email" id="setting-store-email" class="form-control" value="<?= htmlspecialchars($settings['support_email'] ?? 'support@kinzjewellery.com') ?>" required>
                </div>
              </div>

              <div class="form-grid-2" style="margin-top:12px;">
                <div class="form-group">
                  <label>Store Phone</label>
                  <input type="text" name="support_phone" id="setting-store-phone" class="form-control" value="<?= htmlspecialchars($settings['support_phone'] ?? '+91 98201 12345') ?>" required>
                </div>
                <div class="form-group">
                  <label>Standard Tax / GST Rate (%)</label>
                  <input type="number" name="tax_rate" id="setting-tax-rate" step="0.1" class="form-control" value="<?= htmlspecialchars($settings['tax_rate'] ?? '5.0') ?>" required>
                </div>
              </div>

              <div class="form-group" style="margin-top:12px;">
                <label>Guarantee Standard</label>
                <input type="text" name="assurance_policy" id="setting-guarantee-policy" class="form-control" value="<?= htmlspecialchars($settings['assurance_policy'] ?? '1-Year Color & Polish Guarantee') ?>">
              </div>

              <div class="settings-actions-bar" style="display:flex; justify-content:space-between; align-items:center; margin-top:24px; padding-top:20px; border-top:1px solid var(--border-beige); flex-wrap:wrap; gap:12px;">
                <button type="button" class="btn-secondary" style="color:var(--status-danger);" onclick="showAlert('Demo reset disabled in live deployment mode.', 'info')">
                  Reset Demo Data to Defaults
                </button>
                <button type="submit" class="btn-luxury-primary">
                  Save Store Settings
                </button>
              </div>
            </form>
            </div>

          </div>
        </section>

      </div>
    </main>
  </div>

  <!-- ========================================================================
       MODAL 1: ADD / EDIT PRODUCT
       ======================================================================== -->
  <div class="modal-overlay" id="product-modal">
    <div class="modal-card-wrapper" style="max-width: 880px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('product-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box modal-lg">
      <div class="modal-header">
        <h3 id="product-modal-title">Add New Product</h3>
        <button class="btn-close-modal" onclick="closeModal('product-modal')">&times;</button>
      </div>
      <form id="product-form" action="actions/product_action.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" id="prod-form-action" value="add">
        <input type="hidden" name="id" id="prod-form-id" value="">
        <div class="modal-body">
          <!-- Interactive Multi-Image Gallery with Plus (+) Button -->
          <div class="form-group" style="margin-bottom: 20px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
              <label style="font-weight:700; color:var(--deep-charcoal-green); margin:0; display:flex; align-items:center; gap:6px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--antique-copper)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Product Images
                <span id="prod-images-count-badge" style="margin-left:6px; font-size:0.72rem; padding:2px 8px; border-radius:12px; background:var(--champagne-beige); color:var(--forest-green); font-weight:700;">0 Images</span>
              </label>
              <span style="font-size:0.75rem; color:var(--color-text-muted); font-weight:normal;">JPG, PNG, WEBP</span>
            </div>

            <!-- Hidden file input submitted with POST form -->
            <input type="file" id="prod-file-input" name="images[]" class="upload-file-input" accept="image/*" multiple style="display:none;">
            <!-- Separate picker input triggered when user clicks (+) button tile -->
            <input type="file" id="prod-file-picker" accept="image/*" multiple style="display:none;">
            <!-- Hidden input for existing database images in edit mode -->
            <input type="hidden" name="existing_images_json" id="prod-existing-images-json" value="[]">

            <!-- Interactive Image Tiles Grid with Plus (+) Button -->
            <div class="product-images-uploader-box">
              <div class="product-images-grid" id="product-images-grid">
                <!-- Image tiles rendered here dynamically -->
                <button type="button" class="btn-add-image-tile" id="btn-add-image-tile" onclick="triggerProductImageUpload()" title="Add image for this product">
                  <div class="plus-icon-circle">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                  </div>
                  <span class="plus-tile-label">+ Add Image</span>
                  <span class="plus-tile-subtext">Click to choose</span>
                </button>
              </div>
            </div>

            <div style="font-size:0.74rem; margin-top:6px; display:flex; justify-content:flex-end;">
              <button type="button" id="btn-clear-all-photos" onclick="clearAllProductImages()" style="display:none; background:none; border:none; color:var(--status-danger); cursor:pointer; font-size:0.74rem; font-weight:700; padding:0;">Clear All Photos</button>
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label>Product Title *</label>
              <input type="text" name="title" id="prod-form-title" class="form-control" required placeholder="e.g. Royal Micro Gold Choker">
            </div>
            <div class="form-group">
              <label>SKU / Item Code *</label>
              <input type="text" name="sku" id="prod-form-sku" class="form-control" required placeholder="KJ-NCK-001">
            </div>
          </div>

          <div class="form-grid-3">
            <div class="form-group">
              <label>Product Category *</label>
              <select name="category" id="prod-form-category" class="form-control" required>
                <?php foreach ($allCategories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Metal Type *</label>
              <select name="metal_type" id="prod-form-metal-type" class="form-control" required>
                <option value="" disabled selected>Select Metal Type</option>
                <option value="Anti-Tarnish">Anti-Tarnish</option>
                <option value="Water Resistant">Water Resistant</option>
                <option value="Gold Plated">Gold Plated</option>
                <option value="Stainless Steel">Stainless Steel</option>
              </select>
            </div>
            <div class="form-group">
              <label>Guarantee Period *</label>
              <select name="guarantee" id="prod-form-guarantee" class="form-control" required>
                <option value="1 Year Guarantee">1 Year Guarantee</option>
                <option value="6 Months Guarantee">6 Months Guarantee</option>
                <option value="2 Years Guarantee">2 Years Guarantee</option>
                <option value="Lifetime Polish Guarantee">Lifetime Polish Guarantee</option>
              </select>
            </div>
          </div>

          <div class="form-grid-3">
            <div class="form-group">
              <label>Occasion *</label>
              <select name="occasion" id="prod-form-occasion" class="form-control" required>
                <option value="" disabled selected>Select Occasion</option>
                <option value="Wedding">Wedding</option>
                <option value="Festive">Festive</option>
                <option value="Party">Party</option>
                <option value="Daily Wear">Daily Wear</option>
              </select>
            </div>
            <div class="form-group">
              <label>Price (₹) *</label>
              <input type="number" name="base_price" id="prod-form-base-price" class="form-control" required value="1499" step="1">
            </div>
            <div class="form-group">
              <label>Discount (%)</label>
              <input type="number" name="discount_pct" id="prod-form-discount" class="form-control" value="0">
            </div>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label>Stocks Quantity (Units) *</label>
              <input type="number" name="stock" id="prod-form-stock" class="form-control" required value="15">
            </div>
            <div class="form-group">
              <label>Reorder Threshold Alert</label>
              <input type="number" name="reorder_level" id="prod-form-reorder" class="form-control" required value="5">
            </div>
          </div>

          <div class="form-group">
            <label>Detailed Description</label>
            <textarea name="description" id="prod-form-desc" class="form-control" rows="2" placeholder="Anti-tarnish, skin-friendly guaranteed jewellery piece..."></textarea>
          </div>

          <div class="form-grid-2">
            <div class="form-group">
              <label>Product Status</label>
              <select name="status" id="prod-form-status" class="form-control">
                <option value="Active">Enabled (Available for Sale)</option>
                <option value="Disabled">Disabled (Hidden from Store)</option>
              </select>
            </div>
            <div class="form-group" style="padding-top:28px;">
              <label class="form-check">
                <input type="checkbox" name="featured" id="prod-form-featured" value="1">
                <span>Mark as Featured Product</span>
              </label>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-secondary" onclick="closeModal('product-modal')">Cancel</button>
          <button type="submit" class="btn-luxury-primary" id="prod-modal-submit-btn">Save Product</button>
        </div>
      </form>
    </div>
  </div>
  </div>

  <!-- ========================================================================
       MODAL 1.5: PRODUCT SHOWCASE & GALLERY CARD MODAL
       ======================================================================== -->
  <div class="modal-overlay" id="product-gallery-modal">
    <div class="modal-card-wrapper" style="max-width: 1080px; width: 95%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('product-gallery-modal')" aria-label="Close" title="Close Window">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box modal-xl luxury-product-card-modal">
      
      <!-- Top Modal Header -->
      <div class="luxury-detail-modal-header">
        <div class="luxury-detail-header-top">
          <div class="luxury-detail-title-wrap">
            <h2 id="gallery-product-title" class="luxury-detail-title">Aura Crown Solitaire Guarantee Ring</h2>
            <span id="gallery-product-sku" class="luxury-sku-tag">KJ-RNG-001</span>
            <span id="gallery-product-metal-type" class="luxury-plating-tag">Gold Plated</span>
          </div>
        </div>

        <div class="luxury-detail-header-badges">
          <span id="gallery-product-guarantee" class="luxury-badge-guarantee">1 Year Guarantee</span>
          <span id="gallery-photo-counter-badge" class="luxury-badge-counter">Photo 1 of 5</span>
          <button type="button" class="luxury-badge-btn-edit" onclick="galleryEditCurrentProduct()" title="Edit this product">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Edit Product
          </button>
        </div>
      </div>

      <!-- Modal Body: 2 Columns Side-by-Side -->
      <div class="modal-body luxury-card-modal-body">
        
        <!-- LEFT COLUMN: Product Images & Controls -->
        <div class="luxury-gallery-col-left">
          
          <!-- Main Display Image Box with Chevrons -->
          <div class="luxury-main-image-card" id="gallery-main-card">
            <button type="button" class="luxury-img-chevron luxury-chevron-prev" id="gallery-btn-prev" onclick="galleryPrevImage()" title="Previous Image (Left Arrow)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
            </button>
            
            <img id="gallery-main-img" src="" alt="Product View" class="luxury-main-display-img" onerror="this.onerror=null; this.src='assets/ring.jpg';">
            
            <button type="button" class="luxury-img-chevron luxury-chevron-next" id="gallery-btn-next" onclick="galleryNextImage()" title="Next Image (Right Arrow)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
            </button>
          </div>

          <!-- Empty state fallback when product has 0 photos -->
          <div class="gallery-empty-state-card" id="gallery-empty-card" style="display:none;">
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" stroke="var(--antique-copper)" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
            <h4>No Photos Uploaded</h4>
            <p>Upload photos now using the button below.</p>
          </div>

          <!-- Photo Action Buttons Row (Directly below image) -->
          <div class="luxury-photo-actions-row">
            <button type="button" class="luxury-photo-btn" id="btn-set-cover-photo" onclick="gallerySetCoverPhoto()" title="Make this image the main product cover">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              Set as Cover Photo
            </button>
            <button type="button" class="luxury-photo-btn luxury-btn-danger" id="btn-delete-active-photo" onclick="galleryDeleteActivePhoto()" title="Delete this photo from product">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
              Delete Photo
            </button>
          </div>

          <!-- All Angles & Photos Thumbnail Strip Box -->
          <div class="luxury-angles-card">
            <div class="luxury-angles-header">
              <div class="luxury-angles-title">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--antique-copper)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                <span><strong>All Angles &amp; Photos</strong> ( <span id="gallery-angles-count">0</span> )</span>
              </div>
              <button type="button" class="luxury-angles-upload-btn" onclick="galleryTriggerMoreUpload()" title="Add another photo of this piece">+ Upload Photo</button>
              <input type="file" id="gallery-direct-file-input" accept="image/*" multiple style="display:none;" onchange="galleryHandleDirectUpload(this)">
            </div>
            
            <div class="luxury-angles-strip" id="gallery-angles-strip">
              <!-- Dynamically populated thumbnail tiles with COVER and number badges -->
            </div>
          </div>

        </div>

        <!-- RIGHT COLUMN: Luxury Product Details Card -->
        <div class="luxury-gallery-col-right">
          <div class="luxury-details-box">
            
            <!-- Price & Certification -->
            <div class="luxury-price-block">
              <div class="luxury-price-val" id="gallery-price-val">₹0</div>
              <div class="luxury-price-sub" id="gallery-discount-note">10% Discount Applied &bull; Incl. Assurance Certification &amp; GST</div>
            </div>

            <div class="luxury-spec-divider"></div>

            <!-- Specifications Table -->
            <div class="luxury-specs-list">
              <div class="luxury-spec-row">
                <span class="luxury-spec-label">Category</span>
                <span class="luxury-spec-val" id="gallery-spec-category">Rings</span>
              </div>
              <div class="luxury-spec-row">
                <span class="luxury-spec-label">Metal Type</span>
                <span class="luxury-spec-val" id="gallery-spec-metal-type">Gold Plated</span>
              </div>
              <div class="luxury-spec-row">
                <span class="luxury-spec-label">Guarantee Assurance</span>
                <span class="luxury-spec-val highlight-amber" id="gallery-spec-guarantee">1 Year Guarantee</span>
              </div>
              <div class="luxury-spec-row">
                <span class="luxury-spec-label">Occasion</span>
                <span class="luxury-spec-val" id="gallery-spec-occasion">Daily Wear</span>
              </div>
              <div class="luxury-spec-row">
                <span class="luxury-spec-label">Stock Availability</span>
                <span class="luxury-stock-pill" id="gallery-spec-stock">18 Units Available</span>
              </div>
            </div>

            <!-- Product Description Card -->
            <div class="luxury-desc-card">
              <div class="luxury-desc-title">Product Description</div>
              <div class="luxury-desc-text" id="gallery-spec-desc">
                Elegant round cut AAA zirconia set on high-polish micro gold band with anti-tarnish protective coating.
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="luxury-action-buttons-wrap">
              <button type="button" class="luxury-btn-full-primary" onclick="galleryEditCurrentProduct()">
                Edit Product
              </button>
              <button type="button" class="luxury-btn-full-secondary" onclick="galleryStockCurrentProduct()">
                Update Stock
              </button>
            </div>

          </div>
        </div>

      </div>

    </div>
  </div>
  </div>

  <!-- ========================================================================
       MODAL 2: QUICK STOCK ADJUSTMENT
       ======================================================================== -->
  <div class="modal-overlay" id="stock-modal">
    <div class="modal-card-wrapper" style="max-width: 520px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('stock-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box">
        <div class="modal-header">
          <h3 id="stock-adjust-title">Stock Adjustment</h3>
          <button class="btn-close-modal" onclick="closeModal('stock-modal')">&times;</button>
        </div>
        <form id="stock-adjust-form" action="actions/stock_action.php" method="POST">
          <input type="hidden" name="sku" id="stock-modal-sku" value="">
          <input type="hidden" name="product_id" id="stock-modal-id" value="">
          <div class="modal-body">
            <div style="background:var(--warm-ivory); padding:12px; border-radius:8px; margin-bottom:16px; border:1px solid var(--border-beige);">
              <span>Current Stock:</span>
              <strong id="stock-current-val" style="font-size:1.15rem; color:var(--forest-green); margin-left:8px;"></strong>
            </div>

            <div class="form-grid-2">
              <div class="form-group">
                <label>Action</label>
                <select name="action_type" id="stock-adjust-action" class="form-control">
                  <option value="add">Add Units (+)</option>
                  <option value="deduct">Deduct Units (-)</option>
                  <option value="set">Set Exact Quantity</option>
                </select>
              </div>
              <div class="form-group">
                <label>Quantity</label>
                <input type="number" name="quantity" id="stock-adjust-input" class="form-control" value="5" min="1">
              </div>
            </div>

            <div class="form-group">
              <label>Reason / Justification</label>
              <select name="reason" id="stock-adjust-reason" class="form-control">
                <option value="Workshop Batch Delivery (Restock)">Workshop Batch Delivery (Restock)</option>
                <option value="Retail Store Direct Sale">Retail Store Direct Sale</option>
                <option value="Online Order Fulfillment">Online Order Fulfillment</option>
                <option value="Replacement / Return Guarantee">Replacement / Return Guarantee</option>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('stock-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary">Confirm Stock Update</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL 3: ADD / EDIT CATEGORY
       ======================================================================== -->
  <div class="modal-overlay" id="category-modal">
    <div class="modal-card-wrapper" style="max-width: 600px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('category-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box">
        <div class="modal-header">
          <h3 id="cat-modal-title">Add Category</h3>
          <button class="btn-close-modal" onclick="closeModal('category-modal')">&times;</button>
        </div>
        <form id="category-form" action="actions/category_action.php" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" id="cat-form-action" value="add">
          <input type="hidden" name="id" id="cat-form-id" value="">
          <div class="modal-body">
            <!-- Category Banner Image Upload Dropzone -->
            <div class="form-group" style="margin-bottom: 18px;">
              <label style="font-weight:700; color:var(--deep-charcoal-green); display:flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--antique-copper)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                Category Image <span id="cat-image-hint" style="font-weight:400; font-size:0.75rem;"></span>
              </label>
              <div class="upload-dropzone" id="cat-upload-dropzone">
                <svg viewBox="0 0 24 24" fill="none"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <div class="upload-title">Click to browse or drop category photo here</div>
                <div class="upload-subtitle">Upload category photo (JPG, PNG, WEBP)</div>
                <input type="file" name="image" id="cat-file-input" class="upload-file-input" accept="image/*">
              </div>
              <div class="upload-previews-container" id="cat-upload-preview" style="margin-top:10px;">
                <!-- Uploaded preview dynamically rendered -->
              </div>
            </div>

            <div class="form-group">
              <label>Category Name *</label>
              <input type="text" name="name" id="cat-form-name" class="form-control" required placeholder="e.g. Rings">
            </div>
            <div class="form-group">
              <label>Title</label>
              <input type="text" name="seo_title" id="cat-form-seo-title" class="form-control" placeholder="Rings | Kinz">
            </div>
            <div class="form-group">
              <label>Description</label>
              <textarea name="seo_description" id="cat-form-seo-desc" class="form-control" rows="2" placeholder="Category description..."></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('category-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary">Save Category</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL 4: CREATE COUPON
       ======================================================================== -->
  <div class="modal-overlay" id="coupon-modal">
    <div class="modal-card-wrapper" style="max-width: 640px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('coupon-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box">
        <div class="modal-header">
          <h3>Create New Coupon</h3>
          <button class="btn-close-modal" onclick="closeModal('coupon-modal')">&times;</button>
        </div>
        <form id="coupon-form" action="actions/coupon_action.php" method="POST">
          <input type="hidden" name="action" value="add">
          <div class="modal-body">
            <div class="form-group">
              <label>Promo Code (Uppercase)</label>
              <input type="text" name="code" id="coupon-form-code" class="form-control" required placeholder="e.g. BRIDALGOLD20">
            </div>
            <div class="form-grid-2">
              <div class="form-group">
                <label>Discount Type</label>
                <select name="discount_type" id="coupon-form-type" class="form-control">
                  <option value="Percentage">Percentage (%)</option>
                  <option value="Flat">Flat Cash (₹)</option>
                </select>
              </div>
              <div class="form-group">
                <label>Discount Value</label>
                <input type="number" name="value" id="coupon-form-val" class="form-control" required value="15">
              </div>
            </div>
            <div class="form-grid-2">
              <div class="form-group">
                <label>Minimum Order Value (₹)</label>
                <input type="number" name="min_order" id="coupon-form-min" class="form-control" value="1999">
              </div>
              <div class="form-group">
                <label>End Date &amp; Time</label>
                <input type="datetime-local" name="expires_at" id="coupon-form-expiry" class="form-control" required value="2026-12-31T23:59">
              </div>
            </div>
            <div class="form-group">
              <label>Applicable Jewellery Category</label>
              <select name="applicable_to" id="coupon-form-applicable" class="form-control" required>
                <option value="All Guarantee Jewellery">All Guarantee Jewellery (All Categories)</option>
                <?php foreach ($allCategories as $cat): ?>
                  <option value="<?= htmlspecialchars($cat['name']) ?>"><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn-secondary" onclick="closeModal('coupon-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary">Save Coupon</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL 5: ORDER DETAILS (Centered View Page)
       ======================================================================== -->
  <div class="modal-overlay" id="order-drawer">
    <div class="modal-card-wrapper" style="max-width: 680px; width: 100%;">
      <button class="btn-card-close-outside" onclick="closeModal('order-drawer')" aria-label="Close" title="Close Order Details">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box modal-lg" style="max-height: 90vh; width: 100%; max-width: 100%; display: flex; flex-direction: column;">
        <div class="modal-header">
          <h3 id="drawer-order-id">Order Details</h3>
        </div>
        <div class="modal-body" style="overflow-y:auto; flex:1; padding:22px;">
        <div style="margin-bottom:20px;">
          <label style="font-size:0.8rem; font-weight:600; text-transform:uppercase; color:var(--antique-copper);">Update Status</label>
          <div style="display:flex; align-items:center; gap:10px; margin-top:6px; width:100%;">
            <select id="drawer-order-status" name="status" form="drawer-order-form" class="form-control" style="font-weight:600; flex:1 1 auto; min-width:0; width:auto;">
              <option value="Pending">Pending (Payment Verification)</option>
              <option value="Confirmed">Confirmed (Quality Check)</option>
              <option value="Processing">Processing (Packaging)</option>
              <option value="Shipped">Shipped (Courier Transit)</option>
              <option value="Out for Delivery">Out for Delivery</option>
              <option value="Delivered">Delivered &amp; Signed</option>
              <option value="Cancelled">Cancelled</option>
            </select>
            <form action="actions/order_action.php" method="POST" id="drawer-order-form" style="display:inline-flex !important; flex-direction:row !important; align-items:center; justify-content:center; flex:0 0 auto !important; width:auto !important; min-width:104px; margin:0; padding:0; overflow:visible !important;">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="order_id" id="drawer-order-db-id" value="">
              <button type="submit" class="btn-copper" style="display:inline-flex !important; align-items:center; justify-content:center; min-width:104px; min-height:42px; padding:10px 16px; white-space:nowrap; flex-shrink:0; visibility:visible; opacity:1;">Update Status</button>
            </form>
          </div>
        </div>

        <div style="background:var(--warm-ivory); border-radius:10px; padding:16px; margin-bottom:20px; border:1px solid var(--border-beige);">
          <div style="font-size:0.8rem; font-weight:700; text-transform:uppercase; color:var(--forest-green); margin-bottom:8px;">Customer Information</div>
          <div style="font-weight:700; font-size:1.05rem;" id="drawer-customer-name"></div>
          <div style="font-size:0.84rem; color:var(--color-text-muted);" id="drawer-customer-email"></div>
          <div style="font-size:0.84rem; color:var(--color-text-muted);" id="drawer-customer-phone"></div>
          <div style="font-size:0.84rem; margin-top:8px; line-height:1.4;" id="drawer-customer-address"></div>
        </div>

        <div style="margin-bottom:20px;">
          <div style="font-size:0.8rem; font-weight:700; text-transform:uppercase; color:var(--forest-green); margin-bottom:8px;">Product Details</div>
          <div id="drawer-items-list"></div>
        </div>

        <div style="background:var(--warm-ivory); border-radius:10px; padding:16px; margin-bottom:20px; border:1px solid var(--border-beige); font-size:0.86rem;">
          <div style="margin-bottom:6px;"><strong>Packaging:</strong> <span id="drawer-packaging"></span></div>
          <div style="margin-bottom:6px;"><strong>Payment:</strong> <span id="drawer-payment-method"></span></div>
          <div style="margin-bottom:6px;"><strong>Payment Status:</strong> <span id="drawer-payment-status"></span></div>
          <div style="margin-bottom:6px;"><strong>Razorpay Payment ID:</strong> <span id="drawer-razorpay-payment-id" style="font-family:monospace;"></span></div>
          <div style="margin-bottom:6px;"><strong>Paid On:</strong> <span id="drawer-paid-at"></span></div>
          <div style="margin-bottom:6px;"><strong>Refund ID:</strong> <span id="drawer-razorpay-refund-id" style="font-family:monospace;"></span></div>
          <div class="form-group" style="margin-top:10px;">
            <label>Courier Tracking Number</label>
            <input type="text" id="drawer-tracking-input" name="tracking_number" form="drawer-order-form" class="form-control" placeholder="Enter courier tracking number">
          </div>
        </div>

        <div style="border-top:1px solid var(--border-beige); padding-top:16px;">
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Subtotal:</span> <span id="drawer-subtotal"></span></div>
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Coupon Discount:</span> <span id="drawer-discount"></span></div>
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Coupon Code:</span> <span id="drawer-coupon"></span></div>
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Tax:</span> <span id="drawer-tax"></span></div>
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Shipping:</span> <span id="drawer-shipping"></span></div>
          <div style="display:flex; justify-content:space-between; font-size:1.2rem; font-weight:700; color:var(--forest-green); border-top:1px solid var(--border-beige); padding-top:8px;">
            <span>Grand Total:</span> <span id="drawer-total"></span>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-secondary" onclick="printOrderInvoice()">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
          Print Invoice
        </button>
        <button class="btn-luxury-primary" onclick="closeModal('order-drawer')">Close</button>
      </div>
    </div>
  </div>
</div>

  <!-- ========================================================================
       DRAWER 2: CUSTOMER DETAILS (Centered Form / Modal)
       ======================================================================== -->
  <div class="drawer-overlay" id="customer-drawer">
    <div class="modal-card-wrapper" style="max-width: 860px; width: 92%;">
      <button class="btn-card-close-outside" onclick="closeDrawer('customer-drawer')" aria-label="Close" title="Close Customer Details">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="drawer-content" style="max-width: 100%; width: 100%;">
        <div class="drawer-header" style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
          <div style="display:flex; align-items:center; gap:12px; min-width:0;">
            <div class="brand-logo-wrapper" style="width:38px; height:38px; flex-shrink:0;">
              <img src="assets/kinz_logo.jpg" alt="Kinz Emblem" style="width:38px; height:38px; border-radius:50%; object-fit:cover; border:1.5px solid var(--gold-400); box-shadow:0 0 10px rgba(200,121,39,0.35);">
            </div>
            <div style="min-width:0;">
              <h3 style="margin:0; font-size:1.15rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" id="cust-drawer-header-title">Customer Details</h3>
              <div style="font-size:0.75rem; color:var(--champagne-beige); opacity:0.85;" id="cust-drawer-header-subtitle">Customer Record</div>
            </div>
          </div>
        </div>

      <div class="drawer-body" style="padding:20px 24px;">
        <!-- Customer Identity Bar -->
        <div style="text-align:center; padding:12px 0 16px; border-bottom:1px solid var(--border-beige); margin-bottom:16px;">
          <div style="display:inline-flex; align-items:center; justify-content:center; width:52px; height:52px; border-radius:50%; background:var(--forest-green); color:var(--champagne-beige); font-size:1.2rem; font-weight:700; margin-bottom:8px; border:2px solid var(--antique-copper);" id="cust-drawer-avatar">
            AD
          </div>
          <h2 style="font-family:var(--font-serif); font-size:1.5rem; color:var(--deep-charcoal-green); margin:0;" id="cust-drawer-name">Customer Name</h2>
        </div>

        <!-- 3-Tab Navigation Strip -->
        <div class="cust-tabs-nav" id="cust-detail-tabs">
          <button class="cust-tab-btn active" data-tab="overview" onclick="switchCustomerDrawerTab('overview')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Profile &amp; Details
          </button>
          <button class="cust-tab-btn" data-tab="orders" onclick="switchCustomerDrawerTab('orders')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Order History
            <span class="cust-tab-badge" id="cust-tab-orders-badge">0</span>
          </button>
          <button class="cust-tab-btn" data-tab="addresses" onclick="switchCustomerDrawerTab('addresses')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
            Addresses
            <span class="cust-tab-badge" id="cust-tab-addresses-badge">1</span>
          </button>
          <button class="cust-tab-btn" data-tab="reviews" onclick="switchCustomerDrawerTab('reviews')">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            Reviews
            <span class="cust-tab-badge" id="cust-tab-reviews-badge">0</span>
          </button>
        </div>

        <!-- TAB PANE 1: PROFILE & DETAILS -->
        <div class="cust-tab-pane active" id="cust-pane-overview">
          <!-- Registration Details Card -->
          <div style="background:linear-gradient(135deg, rgba(24,53,45,0.05) 0%, rgba(200,121,39,0.08) 100%); border:1px solid var(--border-gold); border-radius:10px; padding:14px 16px; margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:1px dashed rgba(200,121,39,0.3); padding-bottom:7px;">
              <div style="display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--forest-green)" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                <span style="font-weight:700; color:var(--deep-charcoal-green); text-transform:uppercase; font-size:0.75rem; letter-spacing:0.04em;">Registration Details</span>
              </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:0.82rem; line-height:1.6;">
              <div><strong>Registered Date:</strong> <span id="cust-reg-full-date" style="color:var(--deep-charcoal-green); font-weight:600;">15 Jan 2025</span></div>
              <div><strong>Signup Channel:</strong> <span id="cust-reg-channel">Website</span></div>
              <div><strong>Customer ID:</strong> <span id="cust-reg-code" style="font-family:monospace; font-size:0.76rem; color:var(--antique-copper); font-weight:700;">KINZ-REG-001</span></div>
            </div>
          </div>

          <div style="background:var(--warm-ivory); padding:14px; border-radius:10px; border:1px solid var(--border-beige); font-size:0.84rem; line-height:1.7; margin-bottom:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--border-beige); padding-bottom:8px; margin-bottom:10px;">
              <span style="font-weight:700; color:var(--deep-charcoal-green); text-transform:uppercase; font-size:0.75rem; letter-spacing:0.04em;">Contact Information</span>
            </div>
            <div><strong>Email Address:</strong> <span id="cust-drawer-email"></span></div>
            <div><strong>Phone Number:</strong> <span id="cust-drawer-phone"></span></div>
            <div><strong>Member Since:</strong> <span id="cust-drawer-reg-date"></span></div>
            <div><strong>Address:</strong> <span id="cust-drawer-address"></span></div>
          </div>
        </div>

        <!-- TAB PANE 2: ORDER HISTORY -->
        <div class="cust-tab-pane" id="cust-pane-orders">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
            <div style="display:flex; gap:6px;">
              <button class="report-filter-preset-btn active" id="cust-order-filter-all" onclick="filterCustomerOrders('all')">All Orders</button>
              <button class="report-filter-preset-btn" id="cust-order-filter-pending" onclick="filterCustomerOrders('pending')">Pending Delivery</button>
              <button class="report-filter-preset-btn" id="cust-order-filter-delivered" onclick="filterCustomerOrders('delivered')">Delivered</button>
            </div>
            <span id="cust-order-count-pill" class="status-pill status-optimal" style="font-size:0.72rem;">0 Orders</span>
          </div>

          <!-- Dynamic Orders Container -->
          <div id="cust-drawer-orders-list">
            <!-- Dynamically populated orders -->
          </div>
        </div>

        <!-- TAB PANE 3: CUSTOMER ADDRESSES -->
        <div class="cust-tab-pane" id="cust-pane-addresses">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
              <span style="font-size:0.86rem; font-weight:700; color:var(--deep-charcoal-green);">Saved Delivery Addresses</span>
              <div style="font-size:0.74rem; color:var(--color-text-muted);">Saved delivery addresses for customer orders</div>
            </div>
          </div>

          <div class="cust-addresses-grid" id="cust-drawer-addresses-list">
            <!-- Dynamically populated address cards -->
          </div>
        </div>

        <!-- TAB PANE 4: CUSTOMER REVIEWS -->
        <div class="cust-tab-pane" id="cust-pane-reviews">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px;">
            <div>
              <span style="font-size:0.86rem; font-weight:700; color:var(--deep-charcoal-green);">Reviews by this Customer</span>
              <div style="font-size:0.74rem; color:var(--color-text-muted);">All product reviews submitted by this customer</div>
            </div>
            <span id="cust-reviews-count-pill" class="status-pill status-optimal" style="font-size:0.72rem;">0 Reviews</span>
          </div>
          <div id="cust-drawer-reviews-list">
            <!-- Dynamically populated review cards -->
          </div>
        </div>
      </div>

      <div class="drawer-footer" style="display:flex; justify-content:space-between; align-items:center;">
        <a id="cust-drawer-block-btn" href="#" class="btn-secondary" style="color:var(--status-danger); border-color:var(--status-danger);">Block Customer</a>
        <button class="btn-luxury-primary" onclick="closeDrawer('customer-drawer')">Close</button>
      </div>
    </div>
  </div>
</div>

  <!-- ========================================================================
       MODAL 1: ADD / EDIT CUSTOMER PROFILE MODAL
       ======================================================================== -->
  <div class="modal-overlay" id="customer-modal">
    <div class="modal-card-wrapper" style="max-width: 680px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('customer-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box modal-lg" style="max-height:88vh; width:100%; display:flex; flex-direction:column;">
        <div class="modal-header">
          <h3 id="customer-modal-title">Add New Customer</h3>
          <button class="btn-close-modal" onclick="closeModal('customer-modal')">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y:auto; padding:20px;">
        <form id="customer-form" action="actions/customer_action.php" method="POST">
          <input type="hidden" name="action" id="cust-form-action" value="add">
          <input type="hidden" name="id" id="cust-form-id">
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" name="name" id="cust-form-name" class="form-control" placeholder="e.g. Priya Sharma" required>
            </div>
            <div class="form-group">
              <label>Loyalty Tier *</label>
              <select name="tier" id="cust-form-tier" class="form-control" required>
                <option value="VIP Customer">VIP Customer</option>
                <option value="Platinum">Platinum</option>
                <option value="Gold">Gold</option>
                <option value="Regular Customer">Regular Customer</option>
              </select>
            </div>
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
            <div class="form-group">
              <label>Email Address *</label>
              <input type="email" name="email" id="cust-form-email" class="form-control" placeholder="customer@mail.com" required>
            </div>
            <div class="form-group">
              <label>Telephone Number *</label>
              <input type="tel" name="phone" id="cust-form-phone" class="form-control" placeholder="+91 98000 00000" required>
            </div>
          </div>

          <!-- Initial Primary Address -->
          <div style="background:var(--warm-ivory); padding:14px; border-radius:8px; border:1px solid var(--border-beige); margin-bottom:14px;">
            <div style="font-size:0.8rem; font-weight:700; color:var(--deep-charcoal-green); text-transform:uppercase; margin-bottom:10px;">
              Primary Delivery Address
            </div>
            <div class="form-group" style="margin-bottom:10px;">
              <label>Street Address &amp; House/Flat No.</label>
              <input type="text" name="street" id="cust-form-street" class="form-control" placeholder="Flat 402, Lotus Heights, Perry Cross Rd" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px;">
              <div class="form-group">
                <label>City</label>
                <input type="text" name="city" id="cust-form-city" class="form-control" placeholder="Mumbai" required>
              </div>
              <div class="form-group">
                <label>State</label>
                <input type="text" name="state" id="cust-form-state" class="form-control" placeholder="Maharashtra" required>
              </div>
              <div class="form-group">
                <label>PIN Code</label>
                <input type="text" name="pincode" id="cust-form-pincode" class="form-control" placeholder="400050" required>
              </div>
            </div>
          </div>

          <!-- Sizing & Preferences -->
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:14px; margin-bottom:14px;">
            <div class="form-group">
              <label>Preferred Ring Size</label>
              <input type="text" id="cust-form-ring" class="form-control" placeholder="e.g. 14 (US 7)">
            </div>
            <div class="form-group">
              <label>Preferred Bangle Size</label>
              <input type="text" id="cust-form-bangle" class="form-control" placeholder="e.g. 2.6 / 2.8">
            </div>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label>Preferred Plating / Finish</label>
            <select id="cust-form-finish" class="form-control">
              <option value="24K Micron Gold Plated">24K Micron Gold Plated</option>
              <option value="Matte Antique 24K Plated">Matte Antique 24K Plated</option>
              <option value="High Polish Micro Gold">High Polish Micro Gold</option>
              <option value="Rose Gold Micron Plated">Rose Gold Micron Plated</option>
            </select>
          </div>

          <div class="form-group" style="margin-bottom:14px;">
            <label>Customer Notes</label>
            <textarea id="cust-form-notes" class="form-control" rows="2" placeholder="Customer requests or delivery notes..."></textarea>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:16px;">
            <button type="button" class="btn-secondary" onclick="closeModal('customer-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary" id="cust-form-submit-btn">Save Customer</button>
          </div>
        </form>
        </div>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL 2: ADD / EDIT CUSTOMER ADDRESS MODAL
       ======================================================================== -->
  <div class="modal-overlay" id="customer-address-modal">
    <div class="modal-card-wrapper" style="max-width: 540px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('customer-address-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box" style="width:100%;">
        <div class="modal-header">
          <h3 id="cust-address-modal-title">Add Saved Delivery Address</h3>
          <button class="btn-close-modal" onclick="closeModal('customer-address-modal')">&times;</button>
        </div>
        <div class="modal-body" style="padding:20px;">
        <form id="customer-address-form" onsubmit="saveCustomerAddress(event)">
          <input type="hidden" id="cust-addr-form-id">
          
          <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
            <div class="form-group">
              <label>Address Label *</label>
              <select id="cust-addr-label" class="form-control" required>
                <option value="Home (Residence)">Home (Residence)</option>
                <option value="Office / Studio">Office / Studio</option>
                <option value="Bridal Venue / Destination">Bridal Venue / Destination</option>
                <option value="Family Residence">Family Residence</option>
                <option value="Other">Other</option>
              </select>
            </div>
            <div class="form-group">
              <label>Recipient Name *</label>
              <input type="text" id="cust-addr-name" class="form-control" placeholder="Full name on courier parcel" required>
            </div>
          </div>

          <div class="form-group" style="margin-bottom:12px;">
            <label>Contact Telephone *</label>
            <input type="tel" id="cust-addr-phone" class="form-control" placeholder="+91 98000 00000" required>
          </div>

          <div class="form-group" style="margin-bottom:12px;">
            <label>Flat, House No., Building, Street *</label>
            <input type="text" id="cust-addr-street" class="form-control" placeholder="e.g. Flat 402, Lotus Heights, Perry Cross Rd" required>
          </div>

          <div class="form-group" style="margin-bottom:12px;">
            <label>Landmark (Optional)</label>
            <input type="text" id="cust-addr-landmark" class="form-control" placeholder="e.g. Near Flurys / Opposite Diamond Bourse">
          </div>

          <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:14px;">
            <div class="form-group">
              <label>City *</label>
              <input type="text" id="cust-addr-city" class="form-control" placeholder="City" required>
            </div>
            <div class="form-group">
              <label>State *</label>
              <input type="text" id="cust-addr-state" class="form-control" placeholder="State" required>
            </div>
            <div class="form-group">
              <label>PIN Code *</label>
              <input type="text" id="cust-addr-pincode" class="form-control" placeholder="e.g. 400050" required>
            </div>
          </div>

          <div style="display:flex; align-items:center; gap:8px; margin-bottom:18px;">
            <input type="checkbox" id="cust-addr-default" style="width:16px; height:16px; accent-color:var(--forest-green);">
            <label for="cust-addr-default" style="margin:0; font-size:0.84rem; cursor:pointer;">Set as Primary Default Shipping Address</label>
          </div>

          <div style="display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" class="btn-secondary" onclick="closeModal('customer-address-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary">Save Address</button>
          </div>
        </form>
        </div>
      </div>
    </div>
  </div>


  <!-- ========================================================================
       MODAL 4: GLOBAL CUSTOMER ACTIVITY TIMELINE MODAL
       ======================================================================== -->
  <div class="modal-overlay" id="customer-activity-modal">
    <div class="modal-card-wrapper" style="max-width: 720px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('customer-activity-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box modal-lg" style="max-height:88vh; width:100%; display:flex; flex-direction:column;">
        <div class="modal-header">
          <div style="display:flex; align-items:center; gap:10px;">
            <h3 style="margin:0;">Customer Activity Feed</h3>
            <span class="status-pill status-active" style="font-size:0.72rem;">Real-time Audit</span>
          </div>
          <button class="btn-close-modal" onclick="closeModal('customer-activity-modal')">&times;</button>
        </div>
        <div class="modal-body" style="padding:18px 24px; overflow-y:auto;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; flex-wrap:wrap; gap:8px;">
            <div style="font-size:0.82rem; color:var(--color-text-muted);">
              List of customer logins, orders placed, address changes, and security actions.
            </div>
            <div style="display:flex; gap:6px;">
              <button class="report-filter-preset-btn active" id="global-act-filter-all" onclick="filterGlobalActivity('all')">All</button>
              <button class="report-filter-preset-btn" id="global-act-filter-order" onclick="filterGlobalActivity('order')">Orders</button>
              <button class="report-filter-preset-btn" id="global-act-filter-auth" onclick="filterGlobalActivity('auth')">Logins</button>
              <button class="report-filter-preset-btn" id="global-act-filter-security" onclick="filterGlobalActivity('security')">Security</button>
            </div>
          </div>

          <div class="cust-activity-timeline" id="global-customer-activity-list">
            <!-- Dynamically populated cross-customer activities -->
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-luxury-primary" onclick="closeModal('customer-activity-modal')">Close</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL: CATEGORY UPLOADED PIECES POPUP (Centered View Page)
       ======================================================================== -->
  <div class="modal-overlay" id="category-drawer">
    <div class="modal-card-wrapper" style="max-width: 720px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('category-drawer')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box modal-lg" style="max-height: 88vh; width: 100%; display: flex; flex-direction: column;">
        <div class="modal-header">
          <div style="display:flex; align-items:center; gap:12px;">
            <h3 id="category-drawer-title" style="margin:0;">Category Products</h3>
            <span class="status-pill status-active" id="category-drawer-count" style="background:var(--champagne-beige); color:var(--forest-green); border:1px solid var(--border-gold);"></span>
          </div>
          <button class="btn-close-modal" onclick="closeModal('category-drawer')">&times;</button>
        </div>
        <div class="modal-body" style="overflow-y:auto; flex:1; padding:20px;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; padding-bottom:14px; border-bottom:1px solid var(--border-beige); flex-wrap:wrap; gap:10px;">
            <div>
              <div id="category-drawer-seo" style="font-size:0.78rem; color:var(--antique-copper); font-weight:600;"></div>
              <p style="font-size:0.82rem; color:var(--color-text-muted); margin:4px 0 0;" id="category-drawer-desc"></p>
            </div>
            <div style="display:flex; gap:8px;">
              <button class="btn-secondary" style="font-size:0.78rem; padding:6px 12px;" id="btn-cat-view-catalog">
                View in Products &rarr;
              </button>
              <button class="btn-luxury-primary" style="font-size:0.78rem; padding:6px 12px;" id="btn-cat-add-piece">
                + Add Product
              </button>
            </div>
          </div>

          <div id="category-drawer-pieces-container">
            <!-- Dynamically injected uploaded jewellery items -->
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn-luxury-primary" onclick="closeModal('category-drawer')">Done</button>
        </div>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL: EDIT ADMIN PROFILE & CHANGE PASSWORD
       ======================================================================== -->
  <div class="modal-overlay" id="admin-edit-modal">
    <div class="modal-card-wrapper" style="max-width: 500px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('admin-edit-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box" style="width: 100%;">
        <div class="modal-header">
          <h3 id="admin-edit-modal-title">Edit Profile</h3>
          <button class="btn-close-modal" onclick="closeModal('admin-edit-modal')">&times;</button>
        </div>
        <form id="admin-edit-form" action="actions/admin_action.php" method="POST">
          <div class="modal-body" style="padding: 20px;">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit-admin-id">

            <div class="form-group" style="margin-bottom: 14px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green);">Full Name *</label>
              <input type="text" name="name" id="edit-admin-name" class="form-control" required placeholder="Full Name">
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green);">Email Address *</label>
              <input type="email" name="email" id="edit-admin-email" class="form-control" required placeholder="name@kinzjewellery.com">
            </div>

            <!-- Change Password Section -->
            <div style="background:var(--warm-ivory); border:1px solid var(--border-gold); border-radius:8px; padding:16px; margin-top:8px;">
              <div style="font-weight:700; font-size:0.88rem; color:var(--deep-charcoal-green); margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--forest-green)" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Change Password
              </div>
              <div class="form-group" style="margin-bottom: 12px;">
                <label style="font-size:0.8rem; font-weight:600; color:var(--color-text-main);">New Password</label>
                <input type="password" name="password" id="edit-admin-password" class="form-control" placeholder="Enter new password" minlength="4">
              </div>
              <div class="form-group" style="margin-bottom: 6px;">
                <label style="font-size:0.8rem; font-weight:600; color:var(--color-text-main);">Confirm New Password</label>
                <input type="password" id="edit-admin-confirm-password" class="form-control" placeholder="Confirm new password" minlength="4">
              </div>
              <small style="color:var(--color-text-muted); font-size:0.75rem; margin-top:6px; display:block;">Leave blank if you do not want to change password.</small>
            </div>
          </div>
          <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; padding: 14px 20px;">
            <button type="button" class="btn-secondary" onclick="closeModal('admin-edit-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL: ADD NEW ADMIN USER
       ======================================================================== -->
  <div class="modal-overlay" id="admin-add-modal">
    <div class="modal-card-wrapper" style="max-width: 500px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('admin-add-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box" style="width: 100%;">
        <div class="modal-header">
          <h3>Add New Admin</h3>
          <button class="btn-close-modal" onclick="closeModal('admin-add-modal')">&times;</button>
        </div>
        <form id="admin-add-modal-form" action="actions/admin_action.php" method="POST">
          <input type="hidden" name="action" value="add">
          <div class="modal-body" style="padding: 20px;">
            <div class="form-group" style="margin-bottom: 14px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green);">Full Name *</label>
              <input type="text" name="name" id="modal-newadmin-name" class="form-control" required placeholder="e.g. Ramesh Patel">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green);">Email Address *</label>
              <input type="email" name="email" id="modal-newadmin-email" class="form-control" required placeholder="e.g. ramesh@kinzjewellery.com">
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green);">Role *</label>
              <select name="role" id="modal-newadmin-role" class="form-control">
                <option value="Admin">Admin</option>
                <option value="Super Admin">Super Admin</option>
              </select>
            </div>

            <div class="form-group" style="margin-bottom: 6px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green);">Password *</label>
              <input type="password" name="password" id="modal-newadmin-password" class="form-control" required placeholder="Set login password" minlength="4">
            </div>
          </div>
          <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; padding: 14px 20px;">
            <button type="button" class="btn-secondary" onclick="closeModal('admin-add-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary">Add Admin User</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- ========================================================================
       MODAL: ADD NEW CUSTOMER REVIEW
       ======================================================================== -->
  <div class="modal-overlay" id="add-review-modal">
    <div class="modal-card-wrapper" style="max-width: 540px; width: 100%;">
      <button type="button" class="btn-card-close-outside" onclick="closeModal('add-review-modal')" aria-label="Close" title="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
      <div class="modal-box" style="width: 100%;">
        <div class="modal-header">
          <h3>Add Customer Review</h3>
          <button class="btn-close-modal" onclick="closeModal('add-review-modal')">&times;</button>
        </div>
        <form action="actions/review_action.php" method="POST">
          <input type="hidden" name="action" value="add">
          <div class="modal-body" style="padding: 20px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
              <div class="form-group">
                <label style="font-weight:600; color:var(--deep-charcoal-green); display:block; margin-bottom:4px; font-size:0.84rem;">Customer Name *</label>
                <input type="text" name="customer_name" class="form-control" required placeholder="e.g. Priya Sharma">
              </div>
              <div class="form-group">
                <label style="font-weight:600; color:var(--deep-charcoal-green); display:block; margin-bottom:4px; font-size:0.84rem;">Customer Email *</label>
                <input type="email" name="customer_email" class="form-control" required placeholder="e.g. priya@gmail.com">
              </div>
            </div>

            <div style="display:grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 14px;">
              <div class="form-group">
                <label style="font-weight:600; color:var(--deep-charcoal-green); display:block; margin-bottom:4px; font-size:0.84rem;">Product *</label>
                <select name="product_name" class="form-control" required>
                  <option value="">Select a product...</option>
                  <?php foreach ($allProducts as $prod): ?>
                    <option value="<?= htmlspecialchars($prod['title']) ?>"><?= htmlspecialchars($prod['title']) ?> (<?= htmlspecialchars($prod['sku']) ?>)</option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group">
                <label style="font-weight:600; color:var(--deep-charcoal-green); display:block; margin-bottom:4px; font-size:0.84rem;">Rating *</label>
                <select name="rating" class="form-control" required>
                  <option value="5">5 ★★★★★</option>
                  <option value="4">4 ★★★★</option>
                  <option value="3">3 ★★★</option>
                  <option value="2">2 ★★</option>
                  <option value="1">1 ★</option>
                </select>
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green); display:block; margin-bottom:4px; font-size:0.84rem;">Review Title / Headline</label>
              <input type="text" name="title" class="form-control" placeholder="e.g. Gorgeous shine & comfortable fit!">
            </div>

            <div class="form-group" style="margin-bottom: 6px;">
              <label style="font-weight:600; color:var(--deep-charcoal-green); display:block; margin-bottom:4px; font-size:0.84rem;">Review Comments *</label>
              <textarea name="review_text" class="form-control" rows="3" required placeholder="Customer's comments, finish quality, color guarantee satisfaction..."></textarea>
            </div>
          </div>
          <div class="modal-footer" style="display:flex; justify-content:flex-end; gap:10px; padding: 14px 20px;">
            <button type="button" class="btn-secondary" onclick="closeModal('add-review-modal')">Cancel</button>
            <button type="submit" class="btn-luxury-primary">Save Review</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Container for Printable Luxury Invoice -->
  <div id="printable-invoice-container"></div>

  <!-- Mobile Floating Menu Button (Accessible on mobile across all views) -->
  <button class="mobile-floating-menu-btn" id="mobile-floating-menu" onclick="document.getElementById('sidebar')?.classList.toggle('mobile-open')" title="Toggle Menu">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
  </button>

  <!-- Toast Notification Container -->
  <div class="toast-container" id="toast-container"></div>

  


  <!-- Scripts -->
  <script src="js/chart.js"></script>
  <script src="js/app.js?v=<?= time() ?>"></script>

  <script>
    function openEditAdminModalManual(id, name, email) {
      document.getElementById('edit-admin-id').value = id;
      document.getElementById('edit-admin-name').value = name;
      document.getElementById('edit-admin-email').value = email;
      openModal('admin-edit-modal');
    }

    document.addEventListener('DOMContentLoaded', () => {
      // Handle view query param
      const urlParams = new URLSearchParams(window.location.search);
      const viewParam = urlParams.get('view');
      if (viewParam) {
        navigateTo(viewParam);
      }

      <?php if (!empty($msg)): ?>
        const messages = {
          'login_success': 'Welcome back, <?= htmlspecialchars($loggedAdmin["name"]) ?>!',
          'product_added': 'New jewellery piece added successfully to database!',
          'product_updated': 'Product details updated successfully in MySQL!',
          'product_deleted': 'Product removed from database successfully!',
          'status_toggled': 'Product active status updated in MySQL!',
          'category_added': 'New category collection added to database!',
          'category_deleted': 'Category deleted from database!',
          'stock_adjusted': 'Inventory stock level successfully updated in database!',
          'order_status_updated': 'Order status updated successfully in MySQL!',
          'customer_added': 'New customer account created in database!',
          'customer_block_toggled': 'Customer status updated in MySQL!',
          'coupon_added': 'Promotional coupon code saved to database!',
          'coupon_deleted': 'Coupon removed successfully!',
          'category_updated': 'Category updated successfully!',
          'coupon_invalid_date': 'Please choose a valid end date and time.',
          'profile_updated': 'Admin profile updated successfully in MySQL!',
          'settings_saved': 'Store configuration saved successfully to MySQL!'
        };
        const text = messages['<?= $msg ?>'] || 'Operation completed successfully!';
        showAlert(text, 'success');
      <?php elseif (!empty($error)): ?>
        showAlert('<?= htmlspecialchars($error) ?>', 'error');
      <?php endif; ?>
    });
  </script>

</body>
</html>
