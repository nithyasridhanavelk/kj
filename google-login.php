<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/google_helper.php';

if (strpos(GOOGLE_CLIENT_ID, 'PASTE_') === 0 || strpos(GOOGLE_CLIENT_SECRET, 'PASTE_') === 0) {
    $_SESSION['google_auth_error'] = 'Google sign-in is not set up yet. Please sign in with your email.';
    header('Location: login.php');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;

$query = http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => kinz_google_redirect_uri(),
    'response_type' => 'code',
    'scope' => 'openid email profile',
    'state' => $state,
    'prompt' => 'select_account'
]);

header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $query);
exit;
