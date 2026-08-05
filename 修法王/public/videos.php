<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';

startSecureSession();

$videos = getApprovedVideos();
foreach ($videos as &$v) {
    $v['_votes'] = countVotesForVideo($v['id']);
}
unset($v);
usort($videos, fn($a, $b) => $b['_votes'] <=> $a['_votes']);

function ytThumb(string $url): string {
    preg_match('/(?:shorts\/|v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/', $url, $m);
    $vid = $m[1] ?? '';
    return $vid ? "https://img.youtube.com/vi/{$vid}/mqdefault.jpg" : '';
}

pageHeader('影片列表');
?>
<div class="container">
  <h1 style="margin-bottom:1.5rem">🎬 參賽影片列表</h1>

  <?php if (empty($videos)): ?>
    <div class="card"><p>目前尚無已審核通過的影片。</p></div>
  <?php else: ?>
  <div class="video-grid">
    <?php foreach ($videos as $rank => $v): ?>
    <div class="video-card">
      <a href="/修法王/public/video.php?id=<?= urlencode($v['id']) ?>">
        <?php $thumb = ytThumb($v['video_url']); ?>
        <img src="<?= $thumb ?: '/修法王/public/assets/placeholder.jpg' ?>" alt="影片縮圖">
      </a>
      <div class="video-card-body">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.3rem">
          <span class="badge badge-<?= $rank===0?'gold':($v['category']==='student'?'green':'blue') ?>">
            <?= $rank===0 ? '👑 #1' : "#{$rank}" ?> <?= $v['category']==='student' ? '學生' : '大眾' ?>
          </span>
          <span style="color:var(--green);font-weight:700"><?= number_format($v['_votes']) ?> 票</span>
        </div>
        <h4 style="margin-bottom:.5rem"><?= htmlspecialchars($v['team_name'], ENT_QUOTES) ?></h4>
        <a href="/修法王/public/video.php?id=<?= urlencode($v['id']) ?>" class="btn btn-green" style="width:100%;text-align:center">
          觀看 &amp; 投票
        </a>
      </div>
    </div>
    <?php endforeach ?>
  </div>
  <?php endif ?>
</div>
<?php pageFooter() ?>
