<?php
/**
 * Kinz Jewellery Admin - Product Backend Action Handler
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
check_auth();

$action = $_REQUEST['action'] ?? '';

function kinz_product_choice($value, array $allowed, $default) {
    $value = trim((string)$value);
    if (!in_array($value, $allowed, true)) $value = $default;
    return sanitize_input($value);
}

$allowedMetalTypes = ['Anti-Tarnish', 'Water Resistant', 'Gold Plated', 'Stainless Steel'];
$allowedOccasions = ['Wedding', 'Festive', 'Party', 'Daily Wear'];

// Helper to handle multiple uploaded image files
function handle_product_images($fileInputName = 'images') {
    $uploadedPaths = [];
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'svg', 'gif', 'avif'];
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    if (isset($_FILES[$fileInputName]['name']) && is_array($_FILES[$fileInputName]['name'])) {
        $count = count($_FILES[$fileInputName]['name']);
        for ($i = 0; $i < $count; $i++) {
            if ($_FILES[$fileInputName]['error'][$i] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($_FILES[$fileInputName]['name'][$i], PATHINFO_EXTENSION));
                if (in_array($ext, $allowed)) {
                    $filename = 'jewel_' . time() . '_' . rand(100, 999) . '_' . $i . '.' . $ext;
                    if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'][$i], $uploadDir . $filename)) {
                        $uploadedPaths[] = 'uploads/' . $filename;
                    }
                }
            }
        }
    } elseif (isset($_FILES[$fileInputName]) && !is_array($_FILES[$fileInputName]['name']) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES[$fileInputName]['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'jewel_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'], $uploadDir . $filename)) {
                $uploadedPaths[] = 'uploads/' . $filename;
            }
        }
    }

    // Also fallback to single 'image' input if provided
    if (empty($uploadedPaths) && isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'jewel_' . time() . '_' . rand(100, 999) . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
                $uploadedPaths[] = 'uploads/' . $filename;
            }
        }
    }

    return $uploadedPaths;
}

// Ensure images column exists in products table safely without throwing duplicate column exception
try {
    $colCheck = $conn->query("SHOW COLUMNS FROM `products` LIKE 'images'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE `products` ADD COLUMN `images` TEXT AFTER `image`");
    }
} catch (Throwable $t) {
    // Column already exists or table structure is current
}

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = sanitize_input($_POST['sku'] ?? '');
    $title = sanitize_input($_POST['title'] ?? '');
    $category = sanitize_input($_POST['category'] ?? 'Rings');
    $metalType = kinz_product_choice($_POST['metal_type'] ?? '', $allowedMetalTypes, 'Gold Plated');
    $guarantee = sanitize_input($_POST['guarantee'] ?? '1 Year Guarantee');
    $occasion = kinz_product_choice($_POST['occasion'] ?? '', $allowedOccasions, 'Daily Wear');
    $price = (float)($_POST['base_price'] ?? 0);
    $makingCharges = (float)($_POST['making_charges'] ?? 0);
    $discount = (int)($_POST['discount_pct'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $reorderLevel = (int)($_POST['reorder_level'] ?? 5);
    $status = sanitize_input($_POST['status'] ?? 'Active');
    $description = sanitize_input($_POST['description'] ?? '');

    $uploadedImages = handle_product_images('images');
    $primaryImage = !empty($uploadedImages) ? $uploadedImages[0] : '';
    $imagesJson = !empty($uploadedImages) ? $conn->real_escape_string(json_encode($uploadedImages)) : '[]';

    $prodCode = 'PROD-' . rand(1000, 9999);
    if (empty($sku)) {
        $sku = 'KJ-' . strtoupper(substr($category, 0, 3)) . '-' . rand(100, 999);
    }

    $sql = "INSERT INTO products (prod_code, sku, title, category, metal_type, guarantee, occasion, base_price, making_charges, discount_pct, stock, reorder_level, status, image, images, description) 
            VALUES ('$prodCode', '$sku', '$title', '$category', '$metalType', '$guarantee', '$occasion', $price, $makingCharges, $discount, $stock, $reorderLevel, '$status', '$primaryImage', '$imagesJson', '$description')";

    if ($conn->query($sql)) {
        header("Location: ../index.php?view=products&msg=product_added");
        exit();
    } else {
        header("Location: ../index.php?view=products&error=" . urlencode($conn->error));
        exit();
    }

} elseif ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $sku = sanitize_input($_POST['sku'] ?? '');
    $title = sanitize_input($_POST['title'] ?? '');
    $category = sanitize_input($_POST['category'] ?? '');
    $metalType = kinz_product_choice($_POST['metal_type'] ?? '', $allowedMetalTypes, 'Gold Plated');
    $guarantee = sanitize_input($_POST['guarantee'] ?? '1 Year Guarantee');
    $occasion = kinz_product_choice($_POST['occasion'] ?? '', $allowedOccasions, 'Daily Wear');
    $price = (float)($_POST['base_price'] ?? 0);
    $discount = (int)($_POST['discount_pct'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $reorderLevel = (int)($_POST['reorder_level'] ?? 5);
    $status = sanitize_input($_POST['status'] ?? 'Active');
    $description = sanitize_input($_POST['description'] ?? '');

    // Existing images retained by user in the plus button multi-image manager
    $existingImages = [];
    if (!empty($_POST['existing_images_json'])) {
        $rawExisting = json_decode($_POST['existing_images_json'], true);
        if (is_array($rawExisting)) {
            foreach ($rawExisting as $imgPath) {
                $trimmed = trim($imgPath);
                if (!empty($trimmed)) {
                    $existingImages[] = sanitize_input($trimmed);
                }
            }
        }
    }

    $newUploadedImages = handle_product_images('images');
    $allImages = array_merge($existingImages, $newUploadedImages);

    $imageUpdate = "";
    if (!empty($allImages)) {
        $primaryImage = $allImages[0];
        $imagesJson = $conn->real_escape_string(json_encode($allImages));
        $imageUpdate = ", image = '$primaryImage', images = '$imagesJson'";
    } elseif (isset($_POST['existing_images_json'])) {
        // User explicitly removed all images
        $imageUpdate = ", image = '', images = '[]'";
    }

    $skuUpdate = !empty($sku) ? "sku = '$sku'," : "";
    $sql = "UPDATE products SET $skuUpdate title = '$title', category = '$category', metal_type = '$metalType', guarantee = '$guarantee', occasion = '$occasion', base_price = $price, discount_pct = $discount, stock = $stock, reorder_level = $reorderLevel, status = '$status', description = '$description' $imageUpdate WHERE id = $id";
    
    if ($conn->query($sql)) {
        header("Location: ../index.php?view=products&msg=product_updated");
        exit();
    } else {
        header("Location: ../index.php?view=products&error=" . urlencode($conn->error));
        exit();
    }

} elseif ($action === 'toggle_status') {
    $id = (int)($_GET['id'] ?? 0);
    $product = db_fetch_one("SELECT status FROM products WHERE id = $id");
    if ($product) {
        $newStatus = ($product['status'] === 'Active') ? 'Disabled' : 'Active';
        $conn->query("UPDATE products SET status = '$newStatus' WHERE id = $id");
    }
    header("Location: ../index.php?view=products&msg=status_toggled");
    exit();

} elseif ($action === 'delete') {
    $id = (int)($_GET['id'] ?? 0);
    $conn->query("DELETE FROM products WHERE id = $id");
    header("Location: ../index.php?view=products&msg=product_deleted");
    exit();
}

header("Location: ../index.php?view=products");
exit();
?>
