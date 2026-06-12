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

$videoId = trim(strip_tags($_POST['video_id'] ?? ''));
if (empty($videoId)) {
    echo json_encode(['status' => 'error', 'msg' => '缺少影片 ID']);
    exit;
}

$video = getSubmissionById($videoId);
if (!$video || $video['status'] !== 'approved') {
    echo json_encode(['status' => 'error', 'msg' => '影片不存在']);
    exit;
}

$user   = currentUser();
$idHash = $user['id_hash'];
$today  = date('Y-m-d'); // Asia/Taipei 已在 config 設定

if (checkDuplicateVote($idHash, $videoId, $today)) {
    echo json_encode(['status' => 'already_voted', 'msg' => '今日已投票，請明天再來']);
    exit;
}

$voteId = generateId('VOT');
appendSheet(SHEET_VOTES, [
    $voteId,
    $user['user_id'],
    $idHash,
    $videoId,
    $today,
    date('Y-m-d H:i:s'),
]);

$newCount = countVotesForVideo($videoId);
echo json_encode(['status' => 'success', 'new_count' => $newCount]);
