<?php
// ============================================================
// process_contact.php - Contact Form Handler
// ============================================================

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// --- CSRF Token Validation ---
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token.']);
    exit;
}

// --- reCAPTCHA Verification ---
$recaptcha_token = $_POST['g-recaptcha-response'] ?? '';
if (!empty($recaptcha_token) && !verifyRecaptcha($recaptcha_token)) {
    echo json_encode(['success' => false, 'message' => 'reCAPTCHA verification failed. Please try again.']);
    exit;
}

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'No Subject');
$message = trim($_POST['message'] ?? '');

$errors = [];
if (empty($name))    $errors[] = 'Name is required.';
if (empty($email))   $errors[] = 'Email is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email.';
if (empty($message)) $errors[] = 'Message is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);

    $headers = "From: " . MAIL_FROM . "\r\nReply-To: $email";
    @mail(ADMIN_EMAIL, "Contact: $subject", "From: $name ($email)\n\n$message", $headers);

    echo json_encode(['success' => true, 'message' => 'Your message has been sent. We will reply shortly.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
