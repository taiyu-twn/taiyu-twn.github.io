<?php
require_once __DIR__ . '/../config/config.php';

const OTP_EXPIRE_SECONDS = 600; // 10 分鐘

function sendOTP(string $phone): bool {
    startSecureSession();
    $otp       = SMS_TEST_MODE ? SMS_TEST_OTP : str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $message   = "【修法王】您的驗證碼為 {$otp}，10分鐘內有效。";

    $_SESSION['otp_code']  = $otp;
    $_SESSION['otp_phone'] = $phone;
    $_SESSION['otp_time']  = time();

    if (SMS_TEST_MODE) {
        return true;
    }

    $params = http_build_query([
        'username' => SMS_USERNAME,
        'password' => SMS_PASSWORD,
        'dstaddr'  => $phone,
        'smbody'   => $message,
        'charset'  => 'UTF-8',
    ]);

    $url  = 'https://sms.mitake.com.tw/b2c/mtk/SmSend?' . $params;
    $ctx  = stream_context_create(['http' => ['timeout' => 10]]);
    $resp = @file_get_contents($url, false, $ctx);

    // 三竹回傳含 [0] statuscode=0 表示成功
    return $resp !== false && strpos($resp, 'statuscode=0') !== false;
}

function verifyOTP(string $phone, string $inputOtp): bool {
    startSecureSession();
    $storedOtp   = $_SESSION['otp_code']  ?? '';
    $storedPhone = $_SESSION['otp_phone'] ?? '';
    $storedTime  = $_SESSION['otp_time']  ?? 0;

    if ($storedPhone !== $phone) return false;
    if ((time() - $storedTime) > OTP_EXPIRE_SECONDS) return false;
    if (!hash_equals($storedOtp, trim($inputOtp))) return false;

    unset($_SESSION['otp_code'], $_SESSION['otp_phone'], $_SESSION['otp_time']);
    return true;
}
