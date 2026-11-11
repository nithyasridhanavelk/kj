<?php
require_once __DIR__ . '/includes/customer_login_guard.php';

$requestedScript = basename((string)($_GET['script'] ?? ''));
$allowedScripts = [
    'index.php',
    'shop.php',
    'product-details.php',
    'cart.php',
    'cart_api.php',
    'checkout.php',
    'order-confirmation.php',
    'account_api.php',
    'my-orders.php',
    'cancel-order.php',
    'submit-review.php'
];

if (!in_array($requestedScript, $allowedScripts, true)) {
    http_response_code(404);
    exit('Page not found.');
}

$isApi = in_array($requestedScript, ['cart_api.php', 'account_api.php'], true);
kinz_require_customer_login($isApi ? 'json' : 'page', $requestedScript);

require __DIR__ . '/' . $requestedScript;
