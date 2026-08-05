<?php
// ============================================================
// 修法王 — 全域設定常數
// 正式上線前請修改所有預設值
// ============================================================

// Google API
define('GOOGLE_SHEET_ID',       'YOUR_GOOGLE_SHEET_ID');
define('GOOGLE_DRIVE_FOLDER_ID','YOUR_GOOGLE_DRIVE_FOLDER_ID');
define('GOOGLE_CREDENTIALS_PATH', __DIR__ . '/credentials.json');

// 工作表名稱
define('SHEET_SUBMISSIONS', 'submissions');
define('SHEET_USERS',       'users');
define('SHEET_SIGNATURES',  'signatures');
define('SHEET_VOTES',       'votes');

// 雜湊 Salt（請改成隨機長字串）
define('HASH_SALT', 'CHANGE_THIS_TO_A_RANDOM_SECRET_STRING_32CHARS');

// 三竹簡訊
define('SMS_USERNAME', 'YOUR_MITAKE_USERNAME');
define('SMS_PASSWORD', 'YOUR_MITAKE_PASSWORD');
define('SMS_TEST_MODE', true);   // 上線前改 false
define('SMS_TEST_OTP',  '123456');

// 管理員帳密
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', '$2y$12$CHANGEME_bcrypt_hash_here');  // bcrypt hash

// 網站設定
define('SITE_URL',   'https://yourdomain.com/修法王');
define('SITE_NAME',  '修法王');
define('TIMEZONE',   'Asia/Taipei');

// 連署目標
define('PETITION_TARGET', 100000);

// 上傳設定
define('UPLOAD_DIR',      __DIR__ . '/../uploads/');
define('UPLOAD_MAX_SIZE', 5 * 1024 * 1024); // 5MB
define('UPLOAD_ALLOWED',  ['image/jpeg', 'image/png']);

// Email（使用 PHP mail() 或 SMTP）
define('MAIL_FROM',    'noreply@yourdomain.com');
define('MAIL_FROM_NAME', '修法王活動組');

// 時區
date_default_timezone_set(TIMEZONE);

// Session 安全設定
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
