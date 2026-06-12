<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';

startSecureSession();

$id    = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
$video = $id ? getSubmissionById($id) : null;

if (!$video || $video['status'] !== 'approved') {
    http_response_code(404);
    pageHeader('找不到影片');
    echo '<div class="container"><div class="card"><p>找不到此影片。</p><a href="/修法王/public/videos.php">回列表</a></div></div>';
    pageFooter();
    exit;
}

$votes     = countVotesForVideo($id);
$pageUrl   = SITE_URL . '/public/video.php?id=' . urlencode($id);
$qrUrl     = 'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($pageUrl);
$loggedIn  = isLoggedIn();
$hasSigned = $loggedIn && ($_SESSION['has_signed'] ?? false);

function ytEmbed(string $url): string {
    preg_match('/(?:shorts\/|v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/', $url, $m);
    $vid = $m[1] ?? '';
    return $vid ? "https://www.youtube.com/embed/{$vid}" : '';
}

$embedUrl = ytEmbed($video['video_url']);
$teamName = htmlspecialchars($video['team_name'], ENT_QUOTES, 'UTF-8');

pageHeader($video['team_name']);
?>
<div class="container" style="max-width:800px">
  <div class="card">
    <h1 style="margin-bottom:1rem"><?= $teamName ?></h1>

    <?php if ($embedUrl): ?>
    <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:8px;margin-bottom:1.5rem">
      <iframe src="<?= htmlspecialchars($embedUrl) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%"
              frameborder="0" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture"
              allowfullscreen></iframe>
    </div>
    <?php endif ?>

    <div style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;margin-bottom:1.5rem">
      <span style="font-size:1.4rem;color:var(--green);font-weight:700">🗳️ <?= number_format($votes) ?> 票</span>
      <span class="badge badge-<?= $video['category']==='student'?'green':'blue' ?>">
        <?= ['general'=>'一般大眾','streamer'=>'直播主','student'=>'在校學生'][$video['category']] ?? '' ?>
      </span>
    </div>

    <?php if ($loggedIn): ?>
      <button id="voteBtn" class="btn btn-green" style="font-size:1.1rem;padding:.8rem 2rem"
              data-id="<?= htmlspecialchars($id, ENT_QUOTES) ?>"
              data-signed="<?= $hasSigned ? 'true' : 'false' ?>">
        投票支持
      </button>
      <div id="voteMsg" style="margin-top:.8rem;font-weight:600"></div>
    <?php else: ?>
      <a href="/修法王/public/login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-green">登入後投票</a>
    <?php endif ?>

    <hr style="margin:1.5rem 0">

    <div style="display:flex;gap:2rem;align-items:flex-start;flex-wrap:wrap">
      <div>
        <h4>分享此影片</h4>
        <p style="margin:.5rem 0;word-break:break-all;font-size:.85rem;color:#666"><?= htmlspecialchars($pageUrl) ?></p>
        <button onclick="navigator.clipboard.writeText('<?= htmlspecialchars($pageUrl, ENT_QUOTES) ?>').then(()=>this.textContent='已複製！')"
                class="btn btn-outline">複製連結</button>
      </div>
      <div>
        <h4>QR Code</h4>
        <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Code" style="width:150px;height:150px">
      </div>
    </div>
  </div>
</div>

<!-- 連署彈窗 -->
<div class="modal-overlay" id="signModal">
  <div class="modal">
    <button class="modal-close" id="skipSign" title="略過">✕</button>
    <h2 style="margin-bottom:1rem">你聯署了嗎？</h2>
    <p style="color:#555;margin-bottom:1.5rem">加入我們，讓修法聲音被更多人聽見。</p>
    <button id="agreeSign" class="btn btn-green" style="width:100%;font-size:1.1rem;padding:1rem">我同意連署</button>
    <p style="text-align:center;margin-top:.8rem">
      <small><a href="/修法王/public/petition.php" target="_blank">了解修法內容</a></small>
    </p>
  </div>
</div>

<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;

async function doVote() {
  const btn = document.getElementById('voteBtn');
  btn.disabled = true;
  const res  = await fetch('/修法王/api/vote.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':csrf},
    body: 'video_id=' + encodeURIComponent(btn.dataset.id)
  });
  const data = await res.json();
  const msg  = document.getElementById('voteMsg');
  if (data.status === 'success') {
    msg.style.color = 'var(--green)';
    msg.textContent = '✅ 投票成功！目前 ' + data.new_count + ' 票';
  } else if (data.status === 'already_voted') {
    msg.style.color = '#e67e22';
    msg.textContent = '⏰ ' + data.msg;
    btn.disabled = false;
  } else {
    msg.style.color = 'var(--red)';
    msg.textContent = '❌ ' + (data.msg || '投票失敗');
    btn.disabled = false;
  }
}

document.getElementById('voteBtn')?.addEventListener('click', () => {
  const signed = document.getElementById('voteBtn').dataset.signed === 'true';
  if (!signed) {
    document.getElementById('signModal').classList.add('active');
  } else {
    doVote();
  }
});

document.getElementById('skipSign')?.addEventListener('click', () => {
  document.getElementById('signModal').classList.remove('active');
  doVote();
});

document.getElementById('agreeSign')?.addEventListener('click', async () => {
  const res  = await fetch('/修法王/api/sign.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':csrf},
    body: ''
  });
  const data = await res.json();
  document.getElementById('voteBtn').dataset.signed = 'true';
  document.getElementById('signModal').classList.remove('active');
  doVote();
});
</script>
<?php pageFooter() ?>
