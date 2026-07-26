<?php
// ============================================================
// webhook.php - Google Sheets / Airtable এ ডাটা পাঠানোর ফাংশন
// ============================================================
// এই ফাংশনটি PHP cURL ব্যবহার করে ওয়েবহুক URL-এ ডাটা পাঠায়।
// সেটআপ করার বিস্তারিত নিচের "Webhook Setup Guide" অংশে।
// ============================================================

function sendToWebhook($data) {
    // config.php থেকে WEBHOOK_URL পড়ো
    $url = defined('WEBHOOK_URL') ? WEBHOOK_URL : '';

    // ওয়েবহুক URL সেট না করা থাকলে কিছু করো না
    if (empty($url)) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($data),
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,  // লোকালহোস্টে টেস্টের জন্য
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // সফল হলে true, না হলে false
    return ($httpCode >= 200 && $httpCode < 300);
}
