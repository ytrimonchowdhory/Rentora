<?php
// ============================================================
// process_request.php - Rental Request Form Handler
// ============================================================
// "I Want to Rent" ফর্ম থেকে ডাটা গ্রহণ করে DB-তে সেভ করে
// এবং ইমেল + ওয়েবহুক ট্রিগার করে।
// ============================================================

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/webhook.php';

// --- CSRF Token Validation ---
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token. Please refresh the page.']);
    exit;
}

// --- reCAPTCHA Verification ---
$recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
if (!empty($recaptcha_token) && !verifyRecaptcha($recaptcha_token)) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again.']);
    exit;
}

// --- Input Sanitization ---
$renter_name  = trim($_POST['renter_name'] ?? '');
$renter_email = trim($_POST['renter_email'] ?? '');
$renter_phone = trim($_POST['renter_phone'] ?? '');
$requested_item = trim($_POST['requested_item'] ?? '');
$start_date   = trim($_POST['start_date'] ?? '');
$end_date     = trim($_POST['end_date'] ?? '');
$message_text = trim($_POST['message'] ?? '');

// --- Validation ---
$errors = [];
if (empty($renter_name))   $errors[] = 'Your name is required.';
if (empty($renter_email))  $errors[] = 'Email is required.';
if (!filter_var($renter_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
if (empty($requested_item)) $errors[] = 'Please specify what equipment you need.';
if (empty($start_date))    $errors[] = 'Start date is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    // Insert rental request
    $stmt = $pdo->prepare("INSERT INTO rental_requests 
        (requested_item, renter_name, renter_email, renter_phone, start_date, end_date, message, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->execute([
        $requested_item, $renter_name, $renter_email, $renter_phone,
        $start_date, $end_date ?: null, $message_text
    ]);
    $request_id = $pdo->lastInsertId();

    // Send confirmation email
    $subject = "Your Rental Request on " . SITE_NAME;
    $message_body = "Hi $renter_name,\n\n";
    $message_body .= "Your request for \"$requested_item\" has been received!\n";
    $message_body .= "Request ID: $request_id\n";
    $message_body .= "Dates: $start_date to " . ($end_date ?: 'Flexible') . "\n\n";
    $message_body .= "We will connect you with equipment owners soon.\n";
    $message_body .= "Thank you for using " . SITE_NAME . "!\n";
    $headers = "From: " . MAIL_FROM . "\r\n" . "Reply-To: " . ADMIN_EMAIL;
    @mail($renter_email, $subject, $message_body, $headers);

    // Send admin notification
    $adminSubject = "New Rental Request - $requested_item";
    $adminMsg = "New request from $renter_name ($renter_email)\nItem needed: $requested_item\nDates: $start_date - " . ($end_date ?: 'Flexible');
    @mail(ADMIN_EMAIL, $adminSubject, $adminMsg, $headers);

    // Send to Webhook
    $webhookData = [
        'type'  => 'new_request',
        'id'    => $request_id,
        'item'  => $requested_item,
        'name'  => $renter_name,
        'email' => $renter_email,
        'phone' => $renter_phone,
        'start' => $start_date,
        'end'   => $end_date,
        'date'  => date('Y-m-d H:i:s'),
    ];
    sendToWebhook($webhookData);

    echo json_encode(['success' => true, 'message' => 'Your rental request has been submitted! We will match you with equipment owners soon.']);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    // echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
