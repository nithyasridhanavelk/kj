
<?php

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

function response($success, $message, $status = 200)
{
    http_response_code($status);

    echo json_encode([
        'success' => $success,
        'message' => $message
    ]);

    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(false, 'Invalid request method.', 405);
}

// Database connection
require_once __DIR__ . '/config/db.php';

// Read form data
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$inquiry = trim($_POST['inquiry_type'] ?? '');
$message = trim($_POST['message'] ?? '');

// Validate input
$allowedTypes = [
    'Festive & Party Wear Consultation',
    'Gold Plated & Stainless Steel Inquiry',
    'Order & Tracking Assistance',
    'Custom Jewellery Design'
];

if ($name === '' || strlen($name) > 120) {
    response(false, 'Please enter a valid name.', 422);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    response(false, 'Please enter a valid email address.', 422);
}

if ($phone === '' || strlen($phone) > 30) {
    response(false, 'Please enter a valid phone number.', 422);
}

if (!in_array($inquiry, $allowedTypes, true)) {
    response(false, 'Please select a valid inquiry type.', 422);
}

if ($message === '' || strlen($message) > 5000) {
    response(false, 'Please enter your message.', 422);
}

// Insert into the EXISTING contact_messages table
$sql = "INSERT INTO contact_messages
        (name, email, phone, inquiry, message)
        VALUES (?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log('Contact prepare error: ' . $conn->error);
    response(false, 'Unable to save your inquiry. Please try again.', 500);
}

$stmt->bind_param(
    "sssss",
    $name,
    $email,
    $phone,
    $inquiry,
    $message
);

if ($stmt->execute()) {

    $stmt->close();

    response(
        true,
        'Thank you! Your inquiry has been submitted successfully.'
    );

} else {

    error_log('Contact insert error: ' . $stmt->error);
    $stmt->close();

    response(
        false,
        'Unable to save your inquiry. Please try again.',
        500
    );
}

?>
