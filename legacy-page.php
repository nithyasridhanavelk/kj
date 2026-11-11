<?php
require_once __DIR__ . '/includes/customer_login_guard.php';
kinz_require_customer_login('page', 'legacy-page.php');

$file = basename((string)($_GET['file'] ?? ''));
$routes = [
    'index.html' => 'index.php',
    'afterloginindex.html' => 'index.php',
    'shop.html' => 'shop.php',
    'product-details.html' => 'product-details.php',
    'cart.html' => 'cart.php',
    'checkout.html' => 'checkout.php',
    'order-confirmation.html' => 'order-confirmation.php'
];

if (isset($routes[$file])) {
    require __DIR__ . '/' . $routes[$file];
    exit;
}

$staticPages = ['account.html', 'about.html', 'contact.html'];
if (in_array($file, $staticPages, true)) {
    readfile(__DIR__ . '/' . $file);
    exit;
}

http_response_code(404);
?><!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Page Not Found | KINZ</title></head><body><h1>Page not found</h1><a href="index.php">Continue to KINZ</a></body></html>
