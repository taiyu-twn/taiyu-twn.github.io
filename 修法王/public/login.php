<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/sheets.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/layout.php';

startSecureSession();

if (isLoggedIn()) {
    header('Location: /修法王/public/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $phone    = trim($_POST['phone']    ?? '');
    $password = $_POST['password'] ?? '';

    $user = getUserByPhone($phone);
    if ($user && password_verify($password, $user['password_hash'])) {
        login($user);
        $redirect = $_GET['redirect'] ?? '/修法王/public/index.php';
        header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
        exit;
    } else {
        $error = '手機號碼或密碼不正確';
    }
}

pageHeader('登入');
?>
<div class="container" style="max-width:420px">
  <div class="card">
    <h2 style="margin-bottom:1.5rem">登入帳號</h2>
    <?php if ($error): ?><div class="error-msg"><?= htmlspecialchars($error) ?></div><?php endif ?>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
      <div class="form-group">
        <label>手機號碼</label>
        <input type="tel" name="phone" placeholder="09xxxxxxxx" required autofocus>
      </div>
      <div class="form-group">
        <label>密碼</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-green" style="width:100%">登入</button>
    </form>
    <p style="margin-top:1rem;text-align:center">還沒有帳號？<a href="/修法王/public/register.php">立即註冊</a></p>
  </div>
</div>
<?php pageFooter() ?>
