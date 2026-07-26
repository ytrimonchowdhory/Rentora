<?php
// ============================================================
// db.php - Rentora Database Connection
// ============================================================
// (তুমি শুধু নিচের ৪টি লাইন পরিবর্তন করবে তোমার cPanel
//  ডাটাবেসের তথ্য অনুযায়ী — নিচে বাংলায় ব্যাখ্যা দেওয়া আছে)
// ============================================================

$DB_HOST = 'localhost';          // সাধারণত 'localhost' থাকে, cPanel-এ তাই
$DB_NAME = 'u720901460_rentoradb';    // [পরিবর্তন করো] cPanel-এ তুমি যেটা তৈরি করবে
$DB_USER = 'u720901460_rentora1';    // [পরিবর্তন করো] cPanel-এর ডাটাবেস ইউজার
$DB_PASS = 'u720901460_rentora1s1A';    // [পরিবর্তন করো] তোমার দেওয়া পাসওয়ার্ড

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // প্রোডাকশনে error লুকিয়ে রাখো — নিচের লাইন কমেন্ট করে দাও
    die("Connection failed: " . $e->getMessage());
    // প্রোডাকশনে ব্যবহার করো:
    // die("Database connection error. Please try again later.");
}
