<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/auth.php';

startSecureSession();

if (isAdmin()) {
    header('Location: /修法王/admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if (adminLogin($username, $password)) {
        header('Location: /修法王/admin/index.php');
        exit;
    }
    $error = '帳號或密碼不正確';
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>修法王 — 管理員登入</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Noto Sans TC',sans-serif;background:#1a1a2e;display:flex;align-items:center;justify-content:center;min-height:100vh}
.card{background:#fff;border-radius:10px;padding:2.5rem;width:100%;max-width:360px;box-shadow:0 8px 32px rgba(0,0,0,.3)}
h2{margin-bottom:1.5rem;text-align:center;color:#2c3e50}
label{display:block;font-weight:600;margin-bottom:.3rem}
input{width:100%;padding:.6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;margin-bottom:1rem}
button{width:100%;padding:.8rem;background:#2ecc71;color:#fff;border:none;border-radius:6px;font-size:1rem;cursor:pointer}
button:hover{background:#27ae60}
.error{background:#f8d7da;color:#721c24;padding:.8rem;border-radius:6px;margin-bottom:1rem;text-align:center}
</style>
</head>
<body>
<div class="card">
  <h2>⚖️ 修法王後台</h2>
  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif ?>
  <form method="post">
    <label>帳號</label>
    <input type="text" name="username" autofocus required>
    <label>密碼</label>
    <input type="password" name="password" required>
    <button type="submit">登入</button>
  </form>
</div>
</body>
</html>
