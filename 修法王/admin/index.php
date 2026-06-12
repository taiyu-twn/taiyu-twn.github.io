<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../admin/_layout.php';

startSecureSession();

if (isset($_GET['logout'])) {
    session_unset(); session_destroy();
    header('Location: /修法王/admin/login.php');
    exit;
}

requireAdmin();

$submissions = readSheet(SHEET_SUBMISSIONS);
$pending     = count(array_filter($submissions, fn($r) => $r['status'] === 'pending'));
$approved    = count(array_filter($submissions, fn($r) => $r['status'] === 'approved'));
$rejected    = count(array_filter($submissions, fn($r) => $r['status'] === 'rejected'));
$totalSigs   = countSignatures();
$totalVotes  = count(readSheet(SHEET_VOTES));
$todayVotes  = getTodayVoteCount();

adminHeader('統計總覽');
?>
<div class="stat-grid">
  <div class="stat-card"><div class="num" style="color:#f39c12"><?= $pending ?></div><div class="label">待審核</div></div>
  <div class="stat-card"><div class="num" style="color:#2ecc71"><?= $approved ?></div><div class="label">已通過</div></div>
  <div class="stat-card"><div class="num" style="color:#e74c3c"><?= $rejected ?></div><div class="label">已拒絕</div></div>
  <div class="stat-card"><div class="num"><?= number_format($totalSigs) ?></div><div class="label">總連署人數</div></div>
  <div class="stat-card"><div class="num"><?= number_format($totalVotes) ?></div><div class="label">總投票次數</div></div>
  <div class="stat-card"><div class="num" style="color:#3498db"><?= number_format($todayVotes) ?></div><div class="label">今日投票</div></div>
</div>

<div class="card">
  <h3 style="margin-bottom:1rem">快速操作</h3>
  <a href="/修法王/admin/review.php?status=pending" class="btn btn-green">審核待審件（<?= $pending ?>）</a>
</div>
<?php adminFooter() ?>
