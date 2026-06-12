<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';

header('Content-Type: application/json; charset=UTF-8');
startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'msg' => 'Method Not Allowed']);
    exit;
}

verifyCsrf();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'msg' => '請先登入']);
    exit;
}

$user   = currentUser();
$idHash = $user['id_hash'];

if (checkDuplicateSign($idHash)) {
    updateSessionSigned();
    $total = countSignatures();
    echo json_encode(['status' => 'already_signed', 'msg' => '您已完成連署', 'total' => $total]);
    exit;
}

$sigId = generateId('SIG');
appendSheet(SHEET_SIGNATURES, [
    $sigId,
    $user['user_id'],
    $idHash,
    hash('sha256', HASH_SALT . ($user['user_id'])),
    date('Y-m-d H:i:s'),
]);

// 更新 users 的 has_signed
$rowIndex = findRowIndex(SHEET_USERS, 'user_id', $user['user_id']);
if ($rowIndex > 0) {
    // has_signed 在第 7 欄（G 欄）
    updateSheetCell(SHEET_USERS, $rowIndex, 'G', 'true');
}

updateSessionSigned();

$total = countSignatures();
echo json_encode(['status' => 'success', 'total' => $total]);
