<?php
// ============================================================
// process_listing.php - Equipment Listing Form Handler
// ============================================================
// "I Want to List" ফর্ম থেকে ডাটা গ্রহণ করে DB-তে সেভ করে
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
$owner_name  = trim($_POST['owner_name'] ?? '');
$owner_email = trim($_POST['owner_email'] ?? '');
$owner_phone = trim($_POST['owner_phone'] ?? '');
$equip_name  = trim($_POST['equip_name'] ?? '');
$description = trim($_POST['description'] ?? '');
$category    = trim($_POST['category'] ?? '');
$daily_rate  = trim($_POST['daily_rate'] ?? '');
$image_url   = trim($_POST['image_url'] ?? '');
$location    = trim($_POST['location'] ?? '');

// --- Validation ---
$errors = [];
if (empty($owner_name))  $errors[] = 'Owner name is required.';
if (empty($owner_email)) $errors[] = 'Email is required.';
if (!filter_var($owner_email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email format.';
if (empty($equip_name))  $errors[] = 'Equipment name is required.';
if (empty($daily_rate) || !is_numeric($daily_rate) || $daily_rate <= 0) $errors[] = 'Valid daily rate is required.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Check if user exists by email, if not create one
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$owner_email]);
    $user = $stmt->fetch();

    if ($user) {
        $user_id = $user['id'];
        // update name/phone
        $stmt = $pdo->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?");
        $stmt->execute([$owner_name, $owner_phone, $user_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (name, email, phone) VALUES (?, ?, ?)");
        $stmt->execute([$owner_name, $owner_email, $owner_phone]);
        $user_id = $pdo->lastInsertId();
    }

    // 2. Insert equipment listing
    $stmt = $pdo->prepare("INSERT INTO equipment_listings 
        (user_id, owner_name, owner_email, owner_phone, equip_name, description, category, daily_rate, image_url, location, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    $stmt->execute([
        $user_id, $owner_name, $owner_email, $owner_phone,
        $equip_name, $description, $category, $daily_rate,
        $image_url, $location
    ]);
    $listing_id = $pdo->lastInsertId();

    $pdo->commit();

    // 3. Send confirmation email
    $subject = "Your Equipment Listing on " . SITE_NAME;
    $message = "Hi $owner_name,\n\n";
    $message .= "Your equipment \"$equip_name\" has been listed successfully!\n";
    $message .= "Listing ID: $listing_id\n";
    $message .= "Daily Rate: \$$daily_rate\n\n";
    $message .= "You can edit or remove your listing by contacting us.\n";
    $message .= "Thank you for using " . SITE_NAME . "!\n";
    $headers = "From: " . MAIL_FROM . "\r\n" . "Reply-To: " . ADMIN_EMAIL;
    @mail($owner_email, $subject, $message, $headers);

    // 4. Send admin notification
    $adminSubject = "New Equipment Listing - $equip_name";
    $adminMsg = "New listing by $owner_name ($owner_email)\nEquipment: $equip_name\nRate: \$$daily_rate/day\nLocation: $location";
    @mail(ADMIN_EMAIL, $adminSubject, $adminMsg, $headers);

    // 5. Send to Webhook (Google Sheets / Airtable)
    $webhookData = [
        'type'    => 'new_listing',
        'id'      => $listing_id,
        'name'    => $equip_name,
        'owner'   => $owner_name,
        'email'   => $owner_email,
        'phone'   => $owner_phone,
        'rate'    => $daily_rate,
        'date'    => date('Y-m-d H:i:s'),
    ];
    sendToWebhook($webhookData);

    echo json_encode(['success' => true, 'message' => 'Your equipment has been listed successfully! We will contact you shortly.']);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again later.']);
    // প্রোডাকশনে নিচের লাইন কমেন্ট করে দাও:
    // echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
