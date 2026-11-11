<?php
require_once __DIR__ . '/config/db.php';

if (!empty($_SESSION['customer_id']) && !empty($_SESSION['customer_email'])) {
    header('Location: index.php', true, 302);
    exit;
}

require __DIR__ . '/login.php';
