<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/sms.php';
require_once __DIR__ . '/../lib/layout.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: /修法王/public/index.php');
    exit;
}

$errors  = [];
$step    = $_POST['step'] ?? 'form'; // form | otp
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    if ($step === 'form') {
        $realName  = trim(strip_tags($_POST['real_name'] ?? ''));
        $idNumber  = strtoupper(trim($_POST['id_number'] ?? ''));
        $phone     = trim($_POST['phone'] ?? '');
        $password  = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if (empty($realName)) $errors['real_name'] = '請填寫真實姓名';
        if (!preg_match('/^[A-Z][12]\d{8}$/', $idNumber)) $errors['id_number'] = '身分證字號格式不正確';
        if (!preg_match('/^09\d{8}$/', $phone)) $errors['phone'] = '手機號碼格式不正確';
        if (strlen($password) < 8) $errors['password'] = '密碼至少 8 個字元';
        if ($password !== $password2) $errors['password2'] = '兩次密碼不一致';

        if (empty($errors) && checkDuplicateUser($phone)) {
            $errors['phone'] = '此手機號碼已被註冊';
        }

        if (empty($errors)) {
            $sent = sendOTP($phone);
            if (!$sent) {
                $errors['sms'] = '簡訊發送失敗，請稍後再試';
            } else {
                $_SESSION['reg_real_name'] = $realName;
                $_SESSION['reg_id_number'] = $idNumber;
                $_SESSION['reg_phone']     = $phone;
                $_SESSION['reg_password']  = $password;
                $step = 'otp';
            }
        }

    } elseif ($step === 'otp') {
        $inputOtp = trim($_POST['otp'] ?? '');
        $phone    = $_SESSION['reg_phone'] ?? '';

        if (!verifyOTP($phone, $inputOtp)) {
            $errors['otp'] = '驗證碼不正確或已過期';
        } else {
            $realName = $_SESSION['reg_real_name'];
            $idNumber = $_SESSION['reg_id_number'];
            $password = $_SESSION['reg_password'];

            $idHash  = hashId($idNumber);
            $pwdHash = password_hash($password, PASSWORD_BCRYPT);
            $userId  = generateId('USR');

            appendSheet(SHEET_USERS, [
                $userId, $realName, $idHash, $phone, $pwdHash,
                'true', 'false', date('Y-m-d H:i:s'),
            ]);

            unset($_SESSION['reg_real_name'], $_SESSION['reg_id_number'],
                  $_SESSION['reg_phone'],     $_SESSION['reg_password']);

            login([
                'user_id'        => $userId,
                'real_name'      => $realName,
                'has_signed'     => 'false',
                'id_number_hash' => $idHash,
            ]);

            header('Location: /修法王/public/index.php');
            exit;
        }
    }
}

pageHeader('註冊帳號');
?>
<div class="container" style="max-width:480px">
  <div class="card">
    <h2 style="margin-bottom:1.5rem">建立帳號</h2>

    <?php if ($step === 'otp'): ?>
      <p style="margin-bottom:1rem">驗證碼已發送至 <strong><?= htmlspecialchars($_SESSION['reg_phone'] ?? '', ENT_QUOTES) ?></strong>，請在 10 分鐘內輸入。</p>
      <?php if (SMS_TEST_MODE): ?>
        <div class="success-msg" style="margin-bottom:1rem">⚙️ 測試模式：驗證碼為 <strong>123456</strong></div>
      <?php endif ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="step" value="otp">
        <div class="form-group">
          <label>驗證碼</label>
          <input type="text" name="otp" maxlength="6" placeholder="6 位數字" autofocus required>
          <?php if (!empty($errors['otp'])): ?><div class="error"><?= $errors['otp'] ?></div><?php endif ?>
        </div>
        <button type="submit" class="btn btn-green" style="width:100%">驗證並完成註冊</button>
      </form>

    <?php else: ?>
      <form method="post">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="step" value="form">
        <div class="form-group">
          <label>真實姓名</label>
          <input type="text" name="real_name" value="<?= htmlspecialchars($_POST['real_name'] ?? '', ENT_QUOTES) ?>" required>
          <?php if (!empty($errors['real_name'])): ?><div class="error"><?= $errors['real_name'] ?></div><?php endif ?>
        </div>
        <div class="form-group">
          <label>身分證字號</label>
          <input type="text" name="id_number" placeholder="A123456789" maxlength="10" required
                 value="<?= htmlspecialchars($_POST['id_number'] ?? '', ENT_QUOTES) ?>">
          <?php if (!empty($errors['id_number'])): ?><div class="error"><?= $errors['id_number'] ?></div><?php endif ?>
        </div>
        <div class="form-group">
          <label>手機號碼</label>
          <input type="tel" name="phone" placeholder="09xxxxxxxx" required
                 value="<?= htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES) ?>">
          <?php if (!empty($errors['phone'])): ?><div class="error"><?= $errors['phone'] ?></div><?php endif ?>
        </div>
        <div class="form-group">
          <label>密碼（至少 8 字元）</label>
          <input type="password" name="password" required>
          <?php if (!empty($errors['password'])): ?><div class="error"><?= $errors['password'] ?></div><?php endif ?>
        </div>
        <div class="form-group">
          <label>確認密碼</label>
          <input type="password" name="password2" required>
          <?php if (!empty($errors['password2'])): ?><div class="error"><?= $errors['password2'] ?></div><?php endif ?>
        </div>
        <?php if (!empty($errors['sms'])): ?><div class="error-msg"><?= $errors['sms'] ?></div><?php endif ?>
        <button type="submit" class="btn btn-green" style="width:100%">發送簡訊驗證碼</button>
      </form>
      <p style="margin-top:1rem;text-align:center">已有帳號？<a href="/修法王/public/login.php">登入</a></p>
    <?php endif ?>
  </div>
</div>
<?php pageFooter() ?>
