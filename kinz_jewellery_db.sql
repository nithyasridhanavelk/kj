-- Kinz Guarantee Jewellery Admin - MySQL Database Schema & Seed Data
-- Designed for XAMPP Localhost (MySQL / MariaDB)

CREATE DATABASE IF NOT EXISTS `kinz_jewellery_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kinz_jewellery_db`;

-- ----------------------------------------------------------------------------
-- Table: admin_users
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) DEFAULT 'Admin',
  `phone` VARCHAR(25) DEFAULT '',
  `status` VARCHAR(20) DEFAULT 'Active',
  `last_login` VARCHAR(100) DEFAULT 'Recently',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `admin_users` (`user_code`, `name`, `email`, `password`, `role`, `phone`, `status`, `last_login`) VALUES
('USR-01', 'Alexander Kinz', 'admin@kinzjewellery.com', 'admin123', 'Super Admin', '+91 98765 43211', 'Active', 'Today at 19:45'),
('USR-02', 'Alexander Kinz', 'alexander@kinzjewellery.com', 'admin123', 'Super Admin', '+91 98765 43211', 'Active', 'Today at 19:45'),
('USR-03', 'Priya Sharma', 'priya@kinzjewellery.com', 'admin123', 'Admin', '+91 98765 43210', 'Active', 'Today at 17:30');

-- ----------------------------------------------------------------------------
-- Table: settings
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('store_name', 'Kinz Guarantee Jewellery Store'),
('currency', 'INR'),
('currency_symbol', '₹'),
('tax_rate', '5.0'),
('support_email', 'support@kinzjewellery.com'),
('support_phone', '+91 98201 12345'),
('assurance_policy', '1-Year Color & Polish Guarantee'),
('plating_tech', '24K Micro Gold Plating (3-5 Micron)'),
('certification', 'Anti-Tarnish & Skin Friendly Certified');

-- ----------------------------------------------------------------------------
-- Table: categories
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cat_code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL,
  `image` VARCHAR(255) DEFAULT '',
  `product_count` INT DEFAULT 0,
  `seo_title` VARCHAR(255) DEFAULT '',
  `seo_description` TEXT,
  `status` VARCHAR(20) DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-rings', 'Rings', 'rings', 'assets/ring.jpg', 4, 'Gold Plated & Stainless Steel Rings | Kinz Jewellery', 'Anti-tarnish, waterproof gold plated and stainless steel plated rings for daily wear and party wear.', 'Active');
INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-necklaces', 'Necklaces', 'necklaces', 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=600&q=80', 3, 'Gold Plated & Stainless Steel Necklaces | Kinz Jewellery', 'Festive chokers and daily wear necklaces with a long-lasting anti-tarnish gold plated finish.', 'Active');
INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-earrings', 'Earrings', 'earrings', 'https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=600&q=80', 2, 'Jhumkas & Stud Earrings | Kinz Jewellery', 'Lightweight, skin-friendly anti-tarnish earrings for festive occasions and daily wear.', 'Active');
INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-bangles', 'Bangles', 'bangles', 'https://images.unsplash.com/photo-1611591475879-1662a9332e1d?auto=format&fit=crop&w=600&q=80', 2, 'Gold Plated Bangles & Kadas | Kinz Jewellery', 'Broad festive kadas and daily wear bangle sets in a waterproof, anti-tarnish gold plated finish.', 'Active');
INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-bracelets', 'Bracelets', 'bracelets', 'https://images.unsplash.com/photo-1598560917505-59a3ad559071?auto=format&fit=crop&w=600&q=80', 1, 'Anti-Tarnish Bracelets | Kinz Jewellery', 'Modern charm bracelets, crystal tennis bracelets and cuff designs for daily wear and party wear.', 'Active');
INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-chains', 'Chains', 'chains', 'https://images.unsplash.com/photo-1601121141461-9d6647bca1ed?auto=format&fit=crop&w=600&q=80', 1, 'Daily Wear Gold Plated Chains | Kinz Jewellery', 'Rope, box, wheat and figaro pattern waterproof, anti-tarnish chains for men and women.', 'Active');
INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-anklets', 'Anklets', 'anklets', 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=600&q=80', 1, 'Traditional & Sleek Anklets (Payal) | Kinz Jewellery', 'Ghungroo festive payals and sleek anti-tarnish everyday payal anklets.', 'Active');
INSERT INTO `categories` (`cat_code`, `name`, `slug`, `image`, `product_count`, `seo_title`, `seo_description`, `status`) VALUES ('cat-kids', 'Kids Acccessories', 'kids-accessories', 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=600&q=80', 1, 'Skin-Friendly Kids Jewellery & Accessories | Kinz Jewellery', 'Adjustable nazariya bangles, enamel floral earrings and lightweight chains for kids.', 'Active');

-- ----------------------------------------------------------------------------
-- Table: products
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `prod_code` VARCHAR(50) NOT NULL UNIQUE,
  `sku` VARCHAR(50) NOT NULL UNIQUE,
  `title` VARCHAR(255) NOT NULL,
  `category` VARCHAR(100) NOT NULL,
  `metal_type` VARCHAR(50) NOT NULL DEFAULT 'Gold Plated',
  `guarantee` VARCHAR(100) DEFAULT '1 Year Guarantee',
  `occasion` VARCHAR(30) NOT NULL DEFAULT 'Daily Wear',
  `base_price` DECIMAL(10,2) NOT NULL,
  `making_charges` DECIMAL(10,2) DEFAULT 0.00,
  `discount_pct` INT DEFAULT 0,
  `stock` INT DEFAULT 0,
  `reorder_level` INT DEFAULT 5,
  `status` VARCHAR(20) DEFAULT 'Active',
  `featured` TINYINT(1) DEFAULT 0,
  `sales_count` INT DEFAULT 0,
  `revenue_generated` DECIMAL(12,2) DEFAULT 0.00,
  `image` VARCHAR(255) DEFAULT '',
  `images` TEXT,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-101', 'KJ-RNG-001', 'Aura Crown Solitaire Guarantee Ring', 'Rings', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1499, 0, 10, 18, 5, 'Active', 1, 142, 212858, 'assets/ring.jpg', 'Elegant round cut AAA zirconia set on high-polish micro gold band with anti-tarnish protective coating.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-109', 'KJ-RNG-002', 'Imperial Emerald-Cut Micro Gold Solitaire Ring', 'Rings', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1899, 0, 0, 14, 4, 'Active', 1, 96, 182304, 'assets/ring_macro.jpg', 'Stately emerald-cut centerpiece flanked by micro-pavé crystal accents on a rich gold plated band. Anti-tarnish and waterproof for party wear.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-110', 'KJ-RNG-003', 'Royal Filigree Floral Band Ring', 'Rings', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1299, 0, 5, 22, 5, 'Active', 0, 84, 109116, 'assets/ring_box.jpg', 'Floral filigree band in a matte antique gold plated finish. Comfort fit, anti-tarnish and waterproof for daily wear.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-111', 'KJ-RNG-004', 'Princess Pavé Diamond Crown Ring', 'Rings', 'Gold Plated', '2 Years Guarantee', 'Daily Wear', 1999, 0, 15, 7, 4, 'Active', 1, 110, 219890, 'assets/ring_hand.jpg', 'Arching tiara crown silhouette encrusted with micro-pavé zirconia crystals. Two-tone rhodium and gold finish.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-102', 'KJ-NCK-004', 'Imperial Kundan Choker Necklace Set', 'Necklaces', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 4499, 0, 15, 8, 4, 'Active', 1, 94, 422906, 'assets/necklace_emerald.jpg', 'Statement kundan-style collar necklace with matching drop jhumkas, made for festive and party wear with a 1-year colour guarantee.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-112', 'KJ-NCK-005', 'Royal Peacock Temple Choker with Pearl Drops', 'Necklaces', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 3799, 0, 0, 12, 5, 'Active', 0, 62, 235538, 'https://images.unsplash.com/photo-1599643478518-a784e5dc4c8f?auto=format&fit=crop&w=800&q=80', 'Temple artisan peacock motif with cluster freshwater pearls and adjustable gilded dori cord.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-113', 'KJ-NCK-006', 'Minimalist Solitaire Pendant Chain Necklace', 'Necklaces', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1199, 0, 0, 30, 6, 'Active', 0, 175, 209825, 'https://images.unsplash.com/photo-1617038260897-41a1f14a8ca0?auto=format&fit=crop&w=800&q=80', 'Delicate cable chain with a bezel-set brilliant-cut crystal pendant. Anti-tarnish and waterproof for daily wear.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-103', 'KJ-EAR-012', 'Heritage Peacock Chandbali Earrings', 'Earrings', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1399, 0, 0, 22, 5, 'Active', 0, 180, 251820, 'https://images.unsplash.com/photo-1630019852942-f89202989a59?auto=format&fit=crop&w=800&q=80', 'Traditional peacock filigree motif earrings with lightweight push back clasps, safe for sensitive ears.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-114', 'KJ-EAR-013', 'Royal Jhumka Drop Earrings with Red Spinel', 'Earrings', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1599, 0, 5, 19, 5, 'Active', 1, 132, 211068, 'https://images.unsplash.com/photo-1635767798638-3e25273a8236?auto=format&fit=crop&w=800&q=80', 'Classic three-tier bell jhumkas with hanging micro seed pearls and rich matte gold finish.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-104', 'KJ-BNG-008', 'Broad Filigree Guarantee Bangles (Set of 4)', 'Bangles', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 2499, 0, 5, 14, 4, 'Active', 1, 115, 287385, 'https://images.unsplash.com/photo-1611591475879-1662a9332e1d?auto=format&fit=crop&w=800&q=80', 'Set of 4 high-shine gold plated bangles with smooth inner comfort lining. Anti-tarnish and waterproof for daily wear.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-115', 'KJ-BNG-009', 'Antique Matte Gold Kada with Ruby Accents', 'Bangles', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1999, 0, 0, 9, 4, 'Active', 0, 78, 155922, 'https://images.unsplash.com/photo-1573408301185-9146fe634ad0?auto=format&fit=crop&w=800&q=80', 'Single statement openable hinge kada featuring lion head terminals and red cabochon stones, in an antique matte gold plated finish.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-105', 'KJ-BRC-019', 'Pavé Crystal Tennis Bracelet', 'Bracelets', 'Gold Plated', '6 Months Guarantee', 'Daily Wear', 1499, 0, 0, 4, 5, 'Active', 0, 88, 131912, 'https://images.unsplash.com/photo-1598560917505-59a3ad559071?auto=format&fit=crop&w=800&q=80', 'Flexible tennis bracelet with continuous crystal link and double security fold-over clasp.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-106', 'KJ-CHN-003', 'Daily Wear Hollow Rope Chain (24 inch)', 'Chains', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1699, 0, 0, 26, 6, 'Active', 1, 210, 356790, 'https://images.unsplash.com/photo-1601121141461-9d6647bca1ed?auto=format&fit=crop&w=800&q=80', 'Bold look, lightweight gold plated chain with a durable S-hook clasp. Anti-tarnish and waterproof for daily wear.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-107', 'KJ-ANK-002', 'Floral Ghungroo Traditional Anklets (Pair)', 'Anklets', 'Gold Plated', '1 Year Guarantee', 'Daily Wear', 1599, 0, 0, 12, 5, 'Active', 0, 76, 121524, 'https://images.unsplash.com/photo-1535632066927-ab7c9ab60908?auto=format&fit=crop&w=800&q=80', 'Traditional payal with soft-sounding bells in an anti-tarnish gold plated finish, lovely for festive wear.');
INSERT INTO `products` (`prod_code`, `sku`, `title`, `category`, `metal_type`, `guarantee`, `occasion`, `base_price`, `making_charges`, `discount_pct`, `stock`, `reorder_level`, `status`, `featured`, `sales_count`, `revenue_generated`, `image`, `description`) VALUES ('PROD-108', 'KJ-KID-001', 'Kids Adjustable Nazariya Bangle & Ring Set', 'Kids Acccessories', 'Gold Plated', '6 Months Guarantee', 'Daily Wear', 899, 0, 0, 20, 5, 'Active', 1, 95, 85405, 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?auto=format&fit=crop&w=800&q=80', 'Smooth rounded edges, nickel-free and lead-free guaranteed accessories set tailored for children.');

-- ----------------------------------------------------------------------------
-- Table: customers
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cust_code` VARCHAR(50) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(30) DEFAULT '',
  `tier` VARCHAR(50) DEFAULT 'Member',
  `status` VARCHAR(20) DEFAULT 'Active',
  `is_blocked` TINYINT(1) DEFAULT 0,
  `is_signed_in` TINYINT(1) DEFAULT 0,
  `total_spent` DECIMAL(12,2) DEFAULT 0.00,
  `total_orders` INT DEFAULT 0,
  `primary_address` TEXT,
  `registered_date` DATE,
  `registration_method` VARCHAR(100) DEFAULT 'Website',
  `reg_code` VARCHAR(100) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-001', 'Ananya Deshmukh', 'ananya.d@gmail.com', '+91 98201 12345', 'VIP Patron', 'Active', 0, 1, 15218, 3, 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050', '2025-01-15', 'Mobile OTP Verified', 'KINZ-REG-2025-0115-AD');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-002', 'Meera Krishnan', 'meera.k@yahoo.com', '+91 98450 78910', 'Gold Club', 'Active', 0, 1, 13252, 2, '14/B Anna Nagar West, Chennai 600040', '2025-03-20', 'Email Verified', 'KINZ-REG-2025-0320-MK');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-003', 'Shweta Singhal', 'shweta.s@outlook.com', '+91 98110 54321', 'Regular Customer', 'Active', 0, 1, 6401, 2, 'C-82 South Extension Part II, New Delhi 110049', '2025-05-08', 'Google One-Tap', 'KINZ-REG-2025-0508-SS');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-004', 'Pooja Hegde', 'pooja.h@gmail.com', '+91 97400 33221', 'Royal Platinum', 'Active', 0, 1, 2318, 1, '45 Indiranagar 100ft Road, Bangalore 560038', '2025-06-12', 'Personal Invitation', 'KINZ-REG-2025-0612-PH');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-005', 'Sunita Rawat', 'sunita.rawat@gmail.com', '+91 99280 66554', 'Gold Club', 'Active', 0, 0, 944, 1, '72 Civil Lines, Jaipur 302006', '2025-07-22', 'Mobile OTP Verified', 'KINZ-REG-2025-0722-SR');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-006', 'Rajeshwari Sen', 'rajeshwari.sen@gmail.com', '+91 98300 77112', 'VIP Patron', 'Active', 0, 0, 3423, 1, '92 Park Street, 3rd Floor, Kolkata 700016', '2025-08-10', 'Apple ID Verified', 'KINZ-REG-2025-0810-RS');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-007', 'Vikram Malhotra', 'vikram.m@gmail.com', '+91 98210 99887', 'VIP', 'Active', 0, 0, 1679, 1, 'B-12 Vasant Vihar, New Delhi 110057', '2025-09-01', 'Mobile OTP Verified', 'KINZ-REG-2025-0901-VM');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-008', 'Tara Sutaria', 'tara.s@kinzjewellery.com', '+91 98200 44332', 'Gold Club', 'Active', 0, 1, 4899, 1, 'Tower 4, BeauMonde Towers, Prabhadevi, Mumbai 400025', '2026-09-18', 'Apple ID Verified', 'KINZ-REG-2026-0918-TS');
INSERT INTO `customers` (`cust_code`, `name`, `email`, `phone`, `tier`, `status`, `is_blocked`, `is_signed_in`, `total_spent`, `total_orders`, `primary_address`, `registered_date`, `registration_method`, `reg_code`) VALUES ('CUST-009', 'Kabir Oberoi', 'kabir.oberoi@fintech.co', '+91 99100 88776', 'Regular Customer', 'Active', 0, 0, 1999, 1, 'Villa 14, Magnolias, Golf Course Road, Gurugram 122002', '2026-10-04', 'Google One-Tap', 'KINZ-REG-2026-1004-KO');

-- ----------------------------------------------------------------------------
-- Table: orders
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `orders`;
CREATE TABLE `orders` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `order_code` VARCHAR(50) NOT NULL UNIQUE,
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `customer_phone` VARCHAR(30) DEFAULT '',
  `order_date` DATETIME NOT NULL,
  `items_count` INT DEFAULT 1,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(50) DEFAULT 'Pending',
  `payment_method` VARCHAR(50) DEFAULT 'UPI Direct',
  `tracking_number` VARCHAR(100) DEFAULT '',
  `shipping_address` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9848', 'Ananya Deshmukh', 'ananya.d@gmail.com', '+91 98201 12345', '2026-10-12 16:40:00', 1, 3148, 'Pending', 'UPI Direct', '', 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9852', 'Ananya Deshmukh', 'ananya.d@gmail.com', '+91 98201 12345', '2026-10-10 14:15:00', 2, 4618, 'Shipped', 'Credit Card', 'BLUEDART-882910', 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9840', 'Ananya Deshmukh', 'ananya.d@gmail.com', '+91 98201 12345', '2026-09-28 11:30:00', 2, 7452, 'Delivered', 'UPI Direct', 'DTDC-662911', 'Flat 402, Lotus Heights, Bandra West, Mumbai 400050');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9847', 'Meera Krishnan', 'meera.k@yahoo.com', '+91 98450 78910', '2026-10-12 11:15:00', 1, 4724, 'Confirmed', 'Credit Card', 'BLUEDART-892144', '14/B Anna Nagar West, Chennai 600040');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9841', 'Meera Krishnan', 'meera.k@yahoo.com', '+91 98450 78910', '2026-09-15 15:20:00', 1, 8528, 'Delivered', 'Net Banking', 'DELHIVERY-991204', '14/B Anna Nagar West, Chennai 600040');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9846', 'Shweta Singhal', 'shweta.s@outlook.com', '+91 98110 54321', '2026-10-11 19:22:00', 1, 2624, 'Processing', 'Net Banking', 'DTDC-VLT-901128', 'C-82 South Extension Part II, New Delhi 110049');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9842', 'Shweta Singhal', 'shweta.s@outlook.com', '+91 98110 54321', '2026-08-20 17:00:00', 1, 3777, 'Delivered', 'UPI Direct', 'BLUEDART-332910', 'C-82 South Extension Part II, New Delhi 110049');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9845', 'Pooja Hegde', 'pooja.h@gmail.com', '+91 97400 33221', '2026-10-10 13:05:00', 1, 2318, 'Shipped', 'Cash on Delivery', 'DELHIVERY-44781', '45 Indiranagar 100ft Road, Bangalore 560038');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9850', 'Rajeshwari Sen', 'rajeshwari.sen@gmail.com', '+91 98300 77112', '2026-10-12 18:20:00', 2, 3423, 'Pending', 'UPI Direct', '', '92 Park Street, 3rd Floor, Kolkata 700016');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9844', 'Sunita Rawat', 'sunita.rawat@gmail.com', '+91 99280 66554', '2026-10-08 10:30:00', 1, 944, 'Delivered', 'UPI Direct', 'EKART-771239', '72 Civil Lines, Jaipur 302006');
INSERT INTO `orders` (`order_code`, `customer_name`, `customer_email`, `customer_phone`, `order_date`, `items_count`, `total_amount`, `status`, `payment_method`, `tracking_number`, `shipping_address`) VALUES ('KJ-9839', 'Vikram Malhotra', 'vikram.m@gmail.com', '+91 98210 99887', '2026-10-02 14:10:00', 1, 1679, 'Cancelled', 'Credit Card', '', 'B-12 Vasant Vihar, New Delhi 110057');

-- ----------------------------------------------------------------------------
-- Table: coupons
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `coupons`;
CREATE TABLE `coupons` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `code` VARCHAR(50) NOT NULL UNIQUE,
  `discount_type` VARCHAR(20) DEFAULT 'Percentage',
  `value` DECIMAL(10,2) NOT NULL,
  `min_order` DECIMAL(10,2) DEFAULT 0.00,
  `applicable_to` VARCHAR(100) DEFAULT 'All Guarantee Jewellery',
  `used_count` INT DEFAULT 0,
  `usage_limit` INT DEFAULT 500,
  `expires_at` DATETIME,
  `status` VARCHAR(20) DEFAULT 'Active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `coupons` (`code`, `discount_type`, `value`, `min_order`, `applicable_to`, `used_count`, `usage_limit`, `expires_at`, `status`) VALUES ('FESTIVE15', 'Percentage', 15, 1999, 'All Product Categories', 78, 200, '2026-12-31 23:59:59', 'Active');
INSERT INTO `coupons` (`code`, `discount_type`, `value`, `min_order`, `applicable_to`, `used_count`, `usage_limit`, `expires_at`, `status`) VALUES ('BRIDAL50', 'Flat', 500, 3999, 'Necklaces & Bangles', 34, 100, '2026-11-30 23:59:59', 'Active');
INSERT INTO `coupons` (`code`, `discount_type`, `value`, `min_order`, `applicable_to`, `used_count`, `usage_limit`, `expires_at`, `status`) VALUES ('WELCOME10', 'Percentage', 10, 999, 'All Categories', 142, 300, '2026-12-15 23:59:59', 'Active');

-- ----------------------------------------------------------------------------
-- Table: reviews
-- ----------------------------------------------------------------------------
DROP TABLE IF EXISTS `reviews`;
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `review_code` VARCHAR(50) DEFAULT '',
  `customer_name` VARCHAR(100) NOT NULL,
  `customer_email` VARCHAR(150) NOT NULL,
  `product_id` INT DEFAULT NULL,
  `product_name` VARCHAR(255) NOT NULL,
  `rating` INT NOT NULL DEFAULT 5,
  `title` VARCHAR(255) DEFAULT '',
  `review_text` TEXT,
  `status` VARCHAR(20) DEFAULT 'Approved',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `reviews` (`review_code`, `customer_name`, `customer_email`, `product_name`, `rating`, `title`, `review_text`, `status`, `created_at`) VALUES
('REV-101', 'Ananya Deshmukh', 'ananya.d@gmail.com', 'Aura Crown Solitaire Guarantee Ring', 5, 'Exceptional quality & shine!', 'The gold finish looks rich and real. Worn it daily with zero fading.', 'Approved', '2026-10-15 14:20:00'),
('REV-102', 'Meera Krishnan', 'meera.k@yahoo.com', 'Imperial Kundan Choker Necklace Set', 5, 'Stunning festive set', 'Received numerous compliments at my family function. Excellent weight and finish.', 'Approved', '2026-10-18 11:45:00'),
('REV-103', 'Shweta Singhal', 'shweta.s@outlook.com', 'Heritage Peacock Chandbali Earrings', 4, 'Very elegant design', 'Lightweight and comfortable to wear. Fast delivery and beautiful packaging.', 'Approved', '2026-10-22 09:30:00'),
('REV-104', 'Pooja Hegde', 'pooja.h@gmail.com', 'Broad Filigree Guarantee Bangles', 5, 'True to its guarantee', 'Beautiful craftsmanship. Perfect fit and amazing polish.', 'Pending', '2026-10-25 16:10:00');

