<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/mail.php';
require_once __DIR__ . '/../lib/layout.php';

startSecureSession();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    // ── 輸入清理 ──
    $teamName    = trim(strip_tags($_POST['team_name']    ?? ''));
    $memberCount = (int)($_POST['member_count'] ?? 0);
    $contactName = trim(strip_tags($_POST['contact_name'] ?? ''));
    $idNumber    = trim($_POST['id_number'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $videoUrl    = trim($_POST['video_url'] ?? '');
    $igUrl       = trim($_POST['ig_url'] ?? '');
    $platformId  = trim(strip_tags($_POST['platform_id'] ?? ''));
    $category    = trim($_POST['category'] ?? '');
    $school      = trim(strip_tags($_POST['school'] ?? ''));
    $studentId   = trim(strip_tags($_POST['student_id'] ?? ''));

    // ── 驗證 ──
    if (empty($teamName))  $errors['team_name']    = '請填寫組名';
    if ($memberCount < 1 || $memberCount > 5) $errors['member_count'] = '組員人數需為 1-5 人';
    if (empty($contactName)) $errors['contact_name'] = '請填寫聯絡人姓名';

    if (!preg_match('/^[A-Z][12]\d{8}$/', strtoupper($idNumber))) {
        $errors['id_number'] = '身分證字號格式不正確';
    }
    if (!preg_match('/^09\d{8}$/', $phone)) {
        $errors['phone'] = '手機號碼格式不正確（09xxxxxxxx）';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Email 格式不正確';
    }
    if (!preg_match('/^https:\/\/(www\.)?youtube\.com\/(shorts\/|watch\?v=)[\w\-]+/', $videoUrl) &&
        !preg_match('/^https:\/\/youtu\.be\/[\w\-]+/', $videoUrl)) {
        $errors['video_url'] = '請輸入有效的 YouTube 網址';
    }
    if (!empty($igUrl) && !preg_match('/^https:\/\/(www\.)?instagram\.com\/(p|reel|tv)\/[\w\-]+/', $igUrl)) {
        $errors['ig_url'] = '請輸入有效的 Instagram 貼文或 Reels 網址';
    }
    if (!in_array($category, ['general', 'streamer', 'student'])) {
        $errors['category'] = '請選擇參賽身分';
    }
    if ($category === 'student' && (empty($school) || empty($studentId))) {
        $errors['student_info'] = '學生參賽者請填寫學校名稱與學號';
    }

    // ── 照片上傳 ──
    $idPhotoPath      = '';
    $studentPhotoPath = '';

    function uploadPhoto(array $file, string $field): string {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new RuntimeException("請上傳{$field}");
        }
        if ($file['size'] > UPLOAD_MAX_SIZE) {
            throw new RuntimeException('照片大小不得超過 5MB');
        }
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, UPLOAD_ALLOWED)) {
            throw new RuntimeException('只接受 JPG 或 PNG 格式');
        }
        $ext  = ($mime === 'image/png') ? 'png' : 'jpg';
        $name = time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = UPLOAD_DIR . $name;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('照片上傳失敗');
        }
        return $name;
    }

    if (empty($errors)) {
        try {
            $idPhotoPath = uploadPhoto($_FILES['id_photo'] ?? ['error' => 4], '身分證照片');
        } catch (RuntimeException $e) {
            $errors['id_photo'] = $e->getMessage();
        }

        if ($category === 'student' && empty($errors['id_photo'])) {
            try {
                $studentPhotoPath = uploadPhoto($_FILES['student_photo'] ?? ['error' => 4], '學生證照片');
            } catch (RuntimeException $e) {
                $errors['student_photo'] = $e->getMessage();
            }
        }
    }

    if (empty($errors)) {
        $idHash = hashId(strtoupper($idNumber));
        $id     = generateId('SUB');

        appendSheet(SHEET_SUBMISSIONS, [
            $id,
            $teamName,
            $memberCount,
            $contactName,
            $idHash,
            $phone,
            $email,
            $videoUrl,
            $igUrl,
            $platformId,
            $category,
            $idPhotoPath,
            $studentPhotoPath,
            'pending',
            date('Y-m-d H:i:s'),
            '',
            '',
        ]);

        sendApplyConfirmMail($email, $teamName);
        $success = true;
    }
}

pageHeader('我要報名');
?>
<div class="container">
  <div class="card">
    <h1 style="margin-bottom:1.5rem">⚖️ 修法王活動報名</h1>

    <?php if ($success): ?>
      <div class="success-msg">
        <h2>🎉 報名成功！</h2>
        <p>我們將在 3 個工作天內完成審核，審核結果將寄到您的 Email。</p>
        <a href="/修法王/public/index.php" class="btn btn-green" style="margin-top:1rem">回首頁</a>
      </div>
    <?php else: ?>

    <form method="post" enctype="multipart/form-data" id="applyForm" novalidate>
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">

      <div class="form-group">
        <label>組名 *</label>
        <input type="text" name="team_name" value="<?= htmlspecialchars($_POST['team_name'] ?? '', ENT_QUOTES) ?>" required>
        <?php if (!empty($errors['team_name'])): ?><div class="error"><?= $errors['team_name'] ?></div><?php endif ?>
      </div>

      <div class="form-group">
        <label>組員人數 *</label>
        <select name="member_count">
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>" <?= (($_POST['member_count'] ?? 1) == $i) ? 'selected' : '' ?>><?= $i ?> 人</option>
          <?php endfor ?>
        </select>
        <?php if (!empty($errors['member_count'])): ?><div class="error"><?= $errors['member_count'] ?></div><?php endif ?>
      </div>

      <div class="form-group">
        <label>聯絡人姓名 *</label>
        <input type="text" name="contact_name" value="<?= htmlspecialchars($_POST['contact_name'] ?? '', ENT_QUOTES) ?>" required>
        <?php if (!empty($errors['contact_name'])): ?><div class="error"><?= $errors['contact_name'] ?></div><?php endif ?>
      </div>

      <div class="form-group">
        <label>身分證字號 * <small>（送出後將加密儲存，不會明文保存）</small></label>
        <input type="text" name="id_number" placeholder="A123456789" maxlength="10" required
               value="<?= htmlspecialchars($_POST['id_number'] ?? '', ENT_QUOTES) ?>">
        <?php if (!empty($errors['id_number'])): ?><div class="error"><?= $errors['id_number'] ?></div><?php endif ?>
        <div class="error" id="idError"></div>
      </div>

      <div class="form-group">
        <label>聯絡電話 *</label>
        <input type="tel" name="phone" placeholder="09xxxxxxxx"
               value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES) ?>" required>
        <?php if (!empty($errors['phone'])): ?><div class="error"><?= $errors['phone'] ?></div><?php endif ?>
      </div>

      <div class="form-group">
        <label>聯絡 Email *</label>
        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES) ?>" required>
        <?php if (!empty($errors['email'])): ?><div class="error"><?= $errors['email'] ?></div><?php endif ?>
      </div>

      <div class="form-group">
        <label>YouTube Shorts 影片網址 *</label>
        <input type="url" name="video_url" placeholder="https://www.youtube.com/shorts/..."
               value="<?= htmlspecialchars($_POST['video_url'] ?? '', ENT_QUOTES) ?>" required>
        <?php if (!empty($errors['video_url'])): ?><div class="error"><?= $errors['video_url'] ?></div><?php endif ?>
        <div class="error" id="urlError"></div>
      </div>

      <div class="form-group">
        <label>Instagram 貼文／Reels 網址（選填）</label>
        <input type="url" name="ig_url" placeholder="https://www.instagram.com/reel/..."
               value="<?= htmlspecialchars($_POST['ig_url'] ?? '', ENT_QUOTES) ?>">
        <small style="color:#666">填寫後，投票頁會顯示「前往 Instagram 觀看」按鈕</small>
        <?php if (!empty($errors['ig_url'])): ?><div class="error"><?= $errors['ig_url'] ?></div><?php endif ?>
      </div>

      <div class="form-group">
        <label>社群平台帳號 ID（選填）</label>
        <input type="text" name="platform_id" value="<?= htmlspecialchars($_POST['platform_id'] ?? '', ENT_QUOTES) ?>">
      </div>

      <div class="form-group">
        <label>參賽身分 *</label>
        <label style="font-weight:normal;display:flex;align-items:center;gap:.5rem;margin:.3rem 0">
          <input type="radio" name="category" value="general" <?= (($_POST['category'] ?? '') === 'general') ? 'checked' : '' ?>>
          一般大眾
        </label>
        <label style="font-weight:normal;display:flex;align-items:center;gap:.5rem;margin:.3rem 0">
          <input type="radio" name="category" value="streamer" <?= (($_POST['category'] ?? '') === 'streamer') ? 'checked' : '' ?>>
          直播主
        </label>
        <label style="font-weight:normal;display:flex;align-items:center;gap:.5rem;margin:.3rem 0">
          <input type="radio" name="category" value="student" <?= (($_POST['category'] ?? '') === 'student') ? 'checked' : '' ?>>
          在校學生
        </label>
        <?php if (!empty($errors['category'])): ?><div class="error"><?= $errors['category'] ?></div><?php endif ?>
      </div>

      <div id="studentFields" style="display:none">
        <div class="form-group">
          <label>學校名稱 *</label>
          <input type="text" name="school" value="<?= htmlspecialchars($_POST['school'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="form-group">
          <label>學號 *</label>
          <input type="text" name="student_id" value="<?= htmlspecialchars($_POST['student_id'] ?? '', ENT_QUOTES) ?>">
        </div>
        <div class="form-group">
          <label>學生證照片 *（jpg/png，限5MB）</label>
          <input type="file" name="student_photo" accept="image/jpeg,image/png">
          <?php if (!empty($errors['student_photo'])): ?><div class="error"><?= $errors['student_photo'] ?></div><?php endif ?>
        </div>
        <?php if (!empty($errors['student_info'])): ?><div class="error"><?= $errors['student_info'] ?></div><?php endif ?>
      </div>

      <div class="form-group">
        <label>身分證正反面照片 *（jpg/png，限5MB）</label>
        <input type="file" name="id_photo" accept="image/jpeg,image/png" required>
        <?php if (!empty($errors['id_photo'])): ?><div class="error"><?= $errors['id_photo'] ?></div><?php endif ?>
      </div>

      <?php if (!empty($errors)): ?>
        <div class="error-msg">請修正上方錯誤後再送出</div>
      <?php endif ?>

      <button type="submit" class="btn btn-green">送出報名</button>
    </form>

    <?php endif ?>
  </div>
</div>

<script>
// 學生欄位顯示切換
document.querySelectorAll('input[name=category]').forEach(r => {
  r.addEventListener('change', () => {
    document.getElementById('studentFields').style.display =
      r.value === 'student' && r.checked ? 'block' : 'none';
  });
});
if (document.querySelector('input[name=category][value=student]:checked')) {
  document.getElementById('studentFields').style.display = 'block';
}

// 即時驗證
const idInput  = document.querySelector('input[name=id_number]');
const urlInput = document.querySelector('input[name=video_url]');

idInput?.addEventListener('input', () => {
  const v = idInput.value.trim().toUpperCase();
  document.getElementById('idError').textContent =
    /^[A-Z][12]\d{8}$/.test(v) ? '' : (v.length ? '格式不正確' : '');
});

urlInput?.addEventListener('input', () => {
  const v = urlInput.value.trim();
  const ok = /^https:\/\/(www\.)?youtube\.com\/(shorts\/|watch\?v=)[\w\-]+/.test(v) ||
             /^https:\/\/youtu\.be\/[\w\-]+/.test(v);
  document.getElementById('urlError').textContent = (v && !ok) ? 'YouTube 網址格式不正確' : '';
  // 即時驗證後顯示縮圖預覽
  if (ok) {
    const m = v.match(/(?:shorts\/|v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/);
    if (m) {
      let prev = document.getElementById('ytPreview');
      if (!prev) {
        prev = document.createElement('img');
        prev.id = 'ytPreview';
        prev.style.cssText = 'width:200px;border-radius:6px;margin-top:.5rem;display:block';
        urlInput.parentNode.appendChild(prev);
      }
      prev.src = `https://img.youtube.com/vi/${m[1]}/mqdefault.jpg`;
    }
  } else {
    document.getElementById('ytPreview')?.remove();
  }
});

// IG 網址驗證
document.querySelector('input[name=ig_url]')?.addEventListener('input', function() {
  const v = this.value.trim();
  const igOk = !v || /^https:\/\/(www\.)?instagram\.com\/(p|reel|tv)\/[\w\-]+/.test(v);
  this.style.borderColor = (v && !igOk) ? '#e74c3c' : '';
});

// 送出前最後驗證
document.getElementById('applyForm')?.addEventListener('submit', e => {
  const id  = idInput.value.trim().toUpperCase();
  const url = urlInput.value.trim();
  const cat = document.querySelector('input[name=category]:checked');
  if (!/^[A-Z][12]\d{8}$/.test(id)) { e.preventDefault(); idInput.focus(); return; }
  const urlOk = /^https:\/\/(www\.)?youtube\.com\/(shorts\/|watch\?v=)[\w\-]+/.test(url) ||
                /^https:\/\/youtu\.be\/[\w\-]+/.test(url);
  if (!urlOk) { e.preventDefault(); urlInput.focus(); return; }
  if (!cat) { e.preventDefault(); alert('請選擇參賽身分'); return; }
});
</script>
<?php pageFooter() ?>
