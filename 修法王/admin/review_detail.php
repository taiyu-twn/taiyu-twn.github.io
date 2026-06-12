<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/mail.php';
require_once __DIR__ . '/../admin/_layout.php';

startSecureSession();
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_SPECIAL_CHARS);
$r  = $id ? getSubmissionById($id) : null;
if (!$r) {
    header('Location: /修法王/admin/review.php');
    exit;
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $rowIdx = findRowIndex(SHEET_SUBMISSIONS, 'id', $id);

    if ($action === 'approve' && $rowIdx > 0) {
        // status 第13欄(M), reviewed_at 第15欄(O)
        updateSheetCell(SHEET_SUBMISSIONS, $rowIdx, 'M', 'approved');
        updateSheetCell(SHEET_SUBMISSIONS, $rowIdx, 'O', date('Y-m-d H:i:s'));
        $msg = '✅ 已通過審核';
        $r['status'] = 'approved';

    } elseif ($action === 'reject' && $rowIdx > 0) {
        $reason = strip_tags(trim($_POST['reject_reason'] ?? ''));
        updateSheetCell(SHEET_SUBMISSIONS, $rowIdx, 'M', 'rejected');
        updateSheetCell(SHEET_SUBMISSIONS, $rowIdx, 'O', date('Y-m-d H:i:s'));
        updateSheetCell(SHEET_SUBMISSIONS, $rowIdx, 'P', $reason);
        sendRejectMail($r['email'], $r['team_name'], $reason);
        $msg = '❌ 已拒絕並通知參賽者';
        $r['status'] = 'rejected';

    } elseif ($action === 'hide' && $rowIdx > 0) {
        updateSheetCell(SHEET_SUBMISSIONS, $rowIdx, 'M', 'hidden');
        $msg = '🚫 已下架';
        $r['status'] = 'hidden';
    }
}

$catLabel = ['general'=>'一般大眾','streamer'=>'直播主','student'=>'在校學生'];

function ytEmbed(string $url): string {
    preg_match('/(?:shorts\/|v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/', $url, $m);
    $vid = $m[1] ?? '';
    return $vid ? "https://www.youtube.com/embed/{$vid}" : '';
}

adminHeader('審核詳情');
?>
<div class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:1rem">
    <h3><?= htmlspecialchars($r['team_name'], ENT_QUOTES) ?></h3>
    <a href="/修法王/admin/review.php" class="btn btn-gray">← 返回列表</a>
  </div>

  <?php if ($msg): ?><div style="background:#d4edda;color:#155724;padding:.8rem;border-radius:6px;margin-bottom:1rem"><?= $msg ?></div><?php endif ?>

  <table style="width:auto;margin-bottom:1.5rem">
    <?php
    $fields = [
      '狀態'       => '<span class="badge badge-'.$r['status'].'">'.$r['status'].'</span>',
      '組員人數'   => htmlspecialchars($r['member_count'], ENT_QUOTES),
      '聯絡人'     => htmlspecialchars($r['contact_name'], ENT_QUOTES),
      '電話'       => htmlspecialchars($r['phone'], ENT_QUOTES),
      'Email'      => htmlspecialchars($r['email'], ENT_QUOTES),
      '參賽身分'   => $catLabel[$r['category']] ?? $r['category'],
      '報名時間'   => htmlspecialchars($r['submitted_at'], ENT_QUOTES),
      '審核時間'   => htmlspecialchars($r['reviewed_at'] ?? '', ENT_QUOTES),
      '拒絕原因'   => htmlspecialchars($r['reject_reason'] ?? '', ENT_QUOTES),
    ];
    foreach ($fields as $label => $val):
    ?>
      <tr><th style="text-align:right;padding:.4rem 1rem .4rem 0;color:#555;white-space:nowrap"><?= $label ?>：</th><td><?= $val ?></td></tr>
    <?php endforeach ?>
  </table>

  <!-- 影片預覽 -->
  <?php $embed = ytEmbed($r['video_url']); if ($embed): ?>
  <h4 style="margin-bottom:.5rem">影片預覽</h4>
  <div style="max-width:560px;position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:8px;margin-bottom:1.5rem">
    <iframe src="<?= htmlspecialchars($embed) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%"
            frameborder="0" allowfullscreen></iframe>
  </div>
  <?php endif ?>

  <!-- 照片 -->
  <?php if (!empty($r['id_photo_path'])): ?>
  <h4 style="margin-bottom:.5rem">身分證照片</h4>
  <img src="/修法王/uploads/<?= htmlspecialchars($r['id_photo_path'], ENT_QUOTES) ?>"
       style="max-width:300px;border-radius:6px;cursor:zoom-in;margin-bottom:1rem"
       onclick="openImg(this.src)" alt="身分證">
  <?php endif ?>

  <?php if (!empty($r['student_photo_path'])): ?>
  <h4 style="margin-bottom:.5rem">學生證照片</h4>
  <img src="/修法王/uploads/<?= htmlspecialchars($r['student_photo_path'], ENT_QUOTES) ?>"
       style="max-width:300px;border-radius:6px;cursor:zoom-in;margin-bottom:1rem"
       onclick="openImg(this.src)" alt="學生證">
  <?php endif ?>

  <!-- 操作按鈕 -->
  <div style="display:flex;gap:1rem;flex-wrap:wrap;margin-top:1rem">
    <?php if ($r['status'] === 'pending' || $r['status'] === 'rejected'): ?>
    <form method="post">
      <input type="hidden" name="action" value="approve">
      <button type="submit" class="btn btn-green" onclick="return confirm('確認通過此報名？')">✅ 通過</button>
    </form>
    <?php endif ?>

    <?php if ($r['status'] === 'pending' || $r['status'] === 'approved'): ?>
    <button class="btn btn-red" onclick="document.getElementById('rejectModal').classList.add('active')">❌ 不通過</button>
    <?php endif ?>

    <?php if ($r['status'] === 'approved'): ?>
    <form method="post">
      <input type="hidden" name="action" value="hide">
      <button type="submit" class="btn btn-gray" onclick="return confirm('確認下架此影片？')">🚫 下架</button>
    </form>
    <?php endif ?>
  </div>
</div>

<!-- 照片放大 modal -->
<div class="modal-overlay" id="imgModal" onclick="this.classList.remove('active')">
  <img id="imgModalSrc" src="" style="max-width:90%;max-height:90%;border-radius:8px" alt="照片">
</div>

<!-- 拒絕 modal -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal">
    <h3 style="margin-bottom:1rem">填寫拒絕原因</h3>
    <form method="post">
      <input type="hidden" name="action" value="reject">
      <div class="form-group">
        <label>拒絕原因（將寄給參賽者）</label>
        <textarea name="reject_reason" rows="4" required placeholder="請填寫具體原因..."></textarea>
      </div>
      <div style="display:flex;gap:1rem">
        <button type="submit" class="btn btn-red">確認拒絕</button>
        <button type="button" class="btn btn-gray" onclick="document.getElementById('rejectModal').classList.remove('active')">取消</button>
      </div>
    </form>
  </div>
</div>

<script>
function openImg(src) {
  document.getElementById('imgModalSrc').src = src;
  document.getElementById('imgModal').classList.add('active');
}
</script>
<?php adminFooter() ?>
