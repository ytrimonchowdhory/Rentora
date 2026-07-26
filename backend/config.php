<?php
// ============================================================
// config.php - Rentora Global Configuration
// ============================================================

// --- Site Settings ---
define('SITE_NAME', 'Rentora');
define('SITE_URL', 'https://mvprentalmarketplace.ytproductstudio.site/');

// --- Admin Notification Email ---
define('ADMIN_EMAIL', 'mdrimon01980026358@gmail.com');

// --- reCAPTCHA v3 (ঐচ্ছিক) ---
define('RECAPTCHA_SITE_KEY', '6LcPF1ItAAAAAOyVe5wgxcgx3gfMTkW16tjYrKVL');
define('RECAPTCHA_SECRET_KEY', '6LcPF1ItAAAAAApSy4sGvc4iYwHKclJOkpPjU00r');

// --- Webhook URL (Google Sheets / Airtable) ---
// নিচের সিঙ্গেল কোটের ভিতরে তোমার Google Apps Script ওয়েবহুক URL বসাও
// বর্তমানে এটি সেট করা আছে — প্রয়োজনে বদলাবে
define('WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbyIywOxliYDo2vX59EmNu55f5FmqONy61AHurWkDnq0aJVWl_xodMo7eMkRNwu9Xmy8/exec');

// --- Email Configuration ---
define('MAIL_FROM', 'noreply@mvprentalmarketplace.ytproductstudio.site');

// --- reCAPTCHA v3 Verification Function ---
function verifyRecaptcha($token) {
    $secret = RECAPTCHA_SECRET_KEY;
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = ['secret' => $secret, 'response' => $token];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
    ]);
    $response = curl_exec($ch);
    $result = json_decode($response, true);
    curl_close($ch);

    return isset($result['success']) && $result['success'] === true;
}

// --- CSRF Token (Session Start) ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
