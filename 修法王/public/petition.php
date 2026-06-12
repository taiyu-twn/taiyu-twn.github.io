<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';

startSecureSession();

$totalSigs  = countSignatures();
$sigPercent = min(100, round($totalSigs / PETITION_TARGET * 100, 1));
$loggedIn   = isLoggedIn();
$hasSigned  = $loggedIn && ($_SESSION['has_signed'] ?? false);

pageHeader('連署');
?>
<div class="container" style="max-width:700px">
  <div class="card" style="text-align:center;padding:2.5rem">
    <h1 style="margin-bottom:1rem">📜 連署推動修法</h1>
    <h2 style="color:var(--green);margin-bottom:1.5rem">兒少性侵追訴期修法</h2>

    <div style="text-align:left;max-width:550px;margin:0 auto 2rem;line-height:1.8;color:#444">
      <p>目前台灣《刑事訴訟法》針對性侵案件的追訴期規定，使許多受害者在成年後才有能力開口，卻已無法追訴加害者。</p>
      <br>
      <p>我們主張：<strong>兒少性侵案件的追訴期應從受害者成年之日起計算</strong>，給予受害者充足的時間尋求司法救濟。</p>
      <br>
      <p>您的每一份連署，都是讓這個聲音被聽見的力量。</p>
    </div>

    <div style="margin-bottom:1.5rem">
      <p style="font-size:1.1rem">目前已有 <strong style="color:var(--green);font-size:1.5rem"><?= number_format($totalSigs) ?></strong> 人連署</p>
      <div class="progress-bar" style="max-width:400px;margin:.5rem auto">
        <div class="progress-fill" style="width:<?= $sigPercent ?>%"></div>
      </div>
      <p style="color:#666">目標 <?= number_format(PETITION_TARGET) ?> 人（<?= $sigPercent ?>% 達成）</p>
    </div>

    <?php if (!$loggedIn): ?>
      <a href="/修法王/public/login.php?redirect=/修法王/public/petition.php" class="btn btn-green" style="font-size:1.1rem;padding:1rem 2rem">
        登入後連署
      </a>
    <?php elseif ($hasSigned): ?>
      <div class="success-msg" style="max-width:400px;margin:0 auto">
        ✅ 您已完成連署，感謝您的支持！
      </div>
    <?php else: ?>
      <button id="signBtn" class="btn btn-green" style="font-size:1.1rem;padding:1rem 2.5rem">
        我同意連署
      </button>
      <div id="signMsg" style="margin-top:1rem;font-weight:600"></div>
    <?php endif ?>
  </div>
</div>

<?php if ($loggedIn && !$hasSigned): ?>
<script>
const csrf = document.querySelector('meta[name=csrf-token]').content;
document.getElementById('signBtn')?.addEventListener('click', async () => {
  const btn = document.getElementById('signBtn');
  btn.disabled = true;
  btn.textContent = '處理中...';
  const res  = await fetch('/修法王/api/sign.php', {
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':csrf},
    body:''
  });
  const data = await res.json();
  const msg  = document.getElementById('signMsg');
  if (data.status === 'success' || data.status === 'already_signed') {
    msg.style.color = 'var(--green)';
    msg.textContent = '✅ 連署成功！目前 ' + (data.total || '') + ' 人連署';
    btn.style.display = 'none';
  } else {
    msg.style.color = 'var(--red)';
    msg.textContent = '❌ ' + (data.msg || '連署失敗');
    btn.disabled = false;
    btn.textContent = '我同意連署';
  }
});
</script>
<?php endif ?>
<?php pageFooter() ?>
