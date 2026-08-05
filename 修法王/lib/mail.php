<?php
require_once __DIR__ . '/../config/config.php';

function sendMail(string $to, string $subject, string $htmlBody): bool {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $htmlBody, $headers);
}

function sendApplyConfirmMail(string $to, string $teamName): bool {
    $subject = '【修法王】報名成功確認通知';
    $body    = <<<HTML
    <h2>恭喜！您已成功報名修法王活動</h2>
    <p>組名：<strong>{$teamName}</strong></p>
    <p>我們將在 3 個工作天內完成審核，審核結果將另行通知。</p>
    <p>感謝您的參與！</p>
    HTML;
    return sendMail($to, $subject, $body);
}

function sendRejectMail(string $to, string $teamName, string $reason): bool {
    $subject = '【修法王】報名審核結果通知';
    $esc     = htmlspecialchars($reason, ENT_QUOTES, 'UTF-8');
    $body    = <<<HTML
    <h2>修法王報名審核結果</h2>
    <p>組名：<strong>{$teamName}</strong></p>
    <p>很遺憾，您的報名未通過審核。</p>
    <p>原因：{$esc}</p>
    <p>如有疑問請聯繫主辦單位。</p>
    HTML;
    return sendMail($to, $subject, $body);
}
