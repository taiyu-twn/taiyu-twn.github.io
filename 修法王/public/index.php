<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';

startSecureSession();

$totalSigs  = countSignatures();
$sigPercent = min(100, round($totalSigs / PETITION_TARGET * 100, 1));
$videos     = getApprovedVideos();

// 計票
foreach ($videos as &$v) {
    $v['_votes'] = countVotesForVideo($v['id']);
}
unset($v);
usort($videos, fn($a, $b) => $b['_votes'] <=> $a['_votes']);

// 分組排名
$lawKing     = $videos[0] ?? null;
$restVideos  = array_slice($videos, 1);
$publicTop4  = array_values(array_filter($restVideos, fn($v) => in_array($v['category'], ['general','streamer'])));
$studentTop4 = array_values(array_filter($restVideos, fn($v) => $v['category'] === 'student'));
$publicTop4  = array_slice($publicTop4,  0, 4);
$studentTop4 = array_slice($studentTop4, 0, 4);

pageHeader('首頁');

function ytThumb(string $url): string {
    preg_match('/(?:shorts\/|v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/', $url, $m);
    $vid = $m[1] ?? '';
    return $vid ? "https://img.youtube.com/vi/{$vid}/mqdefault.jpg" : '';
}
?>
<div class="container">

  <!-- 活動說明 -->
  <div class="card" style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;text-align:center;padding:3rem 2rem">
    <h1 style="font-size:2.5rem;margin-bottom:1rem">⚖️ 修法王</h1>
    <p style="font-size:1.1rem;opacity:.9;max-width:600px;margin:0 auto 1.5rem">
      推動兒少性侵追訴期修法——用影片讓聲音被聽見，用連署讓改變發生。
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap">
      <a href="/修法王/public/apply.php"   class="btn btn-green">我要參賽</a>
      <a href="/修法王/public/petition.php" class="btn btn-gold">立即連署</a>
      <a href="/修法王/public/videos.php"   class="btn btn-outline" style="color:#fff;border-color:#fff">觀看影片 &amp; 投票</a>
    </div>
  </div>

  <!-- 連署進度 -->
  <div class="card">
    <h2>📜 連署進度</h2>
    <p style="margin:.5rem 0">目前已有 <strong style="color:var(--green);font-size:1.3rem"><?= number_format($totalSigs) ?></strong> 人連署（目標 <?= number_format(PETITION_TARGET) ?> 人）</p>
    <div class="progress-bar">
      <div class="progress-fill" style="width:<?= $sigPercent ?>%"></div>
    </div>
    <p style="color:#666;font-size:.85rem"><?= $sigPercent ?>% 達成</p>
    <a href="/修法王/public/petition.php" class="btn btn-green" style="margin-top:.5rem">加入連署</a>
  </div>

  <!-- 修法王 -->
  <?php if ($lawKing): ?>
  <div class="card" style="border:3px solid var(--gold)">
    <h2>👑 修法王</h2>
    <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-top:1rem">
      <?php $thumb = ytThumb($lawKing['video_url']); if ($thumb): ?>
        <img src="<?= htmlspecialchars($thumb) ?>" style="width:200px;border-radius:8px" alt="縮圖">
      <?php endif ?>
      <div>
        <h3 style="font-size:1.4rem"><?= htmlspecialchars($lawKing['team_name'], ENT_QUOTES) ?></h3>
        <p style="color:var(--gold);font-size:1.2rem;margin:.3rem 0">🏆 <?= number_format($lawKing['_votes']) ?> 票</p>
        <a href="/修法王/public/video.php?id=<?= urlencode($lawKing['id']) ?>" class="btn btn-gold" style="margin-top:.5rem">觀看 &amp; 投票</a>
      </div>
    </div>
  </div>
  <?php endif ?>

  <!-- 社會大眾守護王 -->
  <?php if ($publicTop4): ?>
  <div class="card">
    <h2>🛡️ 社會大眾組守護王 TOP 4</h2>
    <div class="video-grid" style="margin-top:1rem">
      <?php foreach ($publicTop4 as $i => $v): ?>
      <div class="video-card">
        <?php $thumb = ytThumb($v['video_url']); if ($thumb): ?>
          <img src="<?= htmlspecialchars($thumb) ?>" alt="縮圖">
        <?php endif ?>
        <div class="video-card-body">
          <div style="display:flex;justify-content:space-between;align-items:start">
            <h4><?= htmlspecialchars($v['team_name'], ENT_QUOTES) ?></h4>
            <span class="badge badge-blue">#<?= $i+1 ?></span>
          </div>
          <p style="color:var(--green);margin:.3rem 0"><?= number_format($v['_votes']) ?> 票</p>
          <a href="/修法王/public/video.php?id=<?= urlencode($v['id']) ?>" class="btn btn-green" style="font-size:.85rem">投票</a>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>

  <!-- 校園學生守護王 -->
  <?php if ($studentTop4): ?>
  <div class="card">
    <h2>🎓 校園學生組守護王 TOP 4</h2>
    <div class="video-grid" style="margin-top:1rem">
      <?php foreach ($studentTop4 as $i => $v): ?>
      <div class="video-card">
        <?php $thumb = ytThumb($v['video_url']); if ($thumb): ?>
          <img src="<?= htmlspecialchars($thumb) ?>" alt="縮圖">
        <?php endif ?>
        <div class="video-card-body">
          <div style="display:flex;justify-content:space-between;align-items:start">
            <h4><?= htmlspecialchars($v['team_name'], ENT_QUOTES) ?></h4>
            <span class="badge badge-green">#<?= $i+1 ?></span>
          </div>
          <p style="color:var(--green);margin:.3rem 0"><?= number_format($v['_votes']) ?> 票</p>
          <a href="/修法王/public/video.php?id=<?= urlencode($v['id']) ?>" class="btn btn-green" style="font-size:.85rem">投票</a>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>

</div>
<?php pageFooter() ?>
