<?php
function pageHeader(string $title = '修法王', bool $includeAuth = true): void {
    $csrf = csrfToken();
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="zh-Hant">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title} — 修法王</title>
    <meta name="csrf-token" content="{$csrf}">
    <style>
    :root{--green:#2ecc71;--dark-green:#27ae60;--red:#e74c3c;--gold:#f1c40f;--dark:#2c3e50;--light:#ecf0f1}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Noto Sans TC',sans-serif;background:#f8f9fa;color:#333}
    .navbar{background:var(--dark);color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center}
    .navbar a{color:#fff;text-decoration:none;margin-left:1rem}
    .navbar a:hover{color:var(--green)}
    .container{max-width:1100px;margin:2rem auto;padding:0 1rem}
    .card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);padding:1.5rem;margin-bottom:1.5rem}
    .btn{display:inline-block;padding:.6rem 1.4rem;border-radius:6px;border:none;cursor:pointer;font-size:.95rem;text-decoration:none;transition:.2s}
    .btn-green{background:var(--green);color:#fff}.btn-green:hover{background:var(--dark-green)}
    .btn-red{background:var(--red);color:#fff}
    .btn-gold{background:var(--gold);color:#333}
    .btn-outline{background:transparent;border:2px solid var(--green);color:var(--green)}
    .btn:disabled{opacity:.5;cursor:not-allowed}
    .form-group{margin-bottom:1rem}
    label{display:block;font-weight:600;margin-bottom:.3rem}
    input,select,textarea{width:100%;padding:.6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem}
    input:focus,select:focus,textarea:focus{outline:none;border-color:var(--green)}
    .error{color:var(--red);font-size:.85rem;margin-top:.2rem}
    .success-msg{background:#d4edda;color:#155724;padding:1rem;border-radius:6px;margin-bottom:1rem}
    .error-msg{background:#f8d7da;color:#721c24;padding:1rem;border-radius:6px;margin-bottom:1rem}
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center}
    .modal-overlay.active{display:flex}
    .modal{background:#fff;border-radius:10px;padding:2rem;max-width:480px;width:90%;position:relative}
    .modal-close{position:absolute;top:.7rem;right:1rem;font-size:1.4rem;cursor:pointer;color:#666;background:none;border:none}
    .progress-bar{background:#ddd;border-radius:99px;height:16px;overflow:hidden;margin:.5rem 0}
    .progress-fill{background:var(--green);height:100%;transition:width .4s}
    .video-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1.5rem}
    .video-card{background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1)}
    .video-card img{width:100%;aspect-ratio:16/9;object-fit:cover}
    .video-card-body{padding:1rem}
    .badge{display:inline-block;padding:.2rem .6rem;border-radius:99px;font-size:.75rem;font-weight:700}
    .badge-gold{background:#fff3cd;color:#856404}
    .badge-green{background:#d4edda;color:#155724}
    .badge-blue{background:#d1ecf1;color:#0c5460}
    @media(max-width:600px){.navbar{flex-direction:column;gap:.5rem}.video-grid{grid-template-columns:1fr}}
    </style>
    </head>
    <body>
    <nav class="navbar">
      <a href="/修法王/public/index.php" style="font-size:1.3rem;font-weight:700">⚖️ 修法王</a>
      <div>
        <a href="/修法王/public/videos.php">影片列表</a>
        <a href="/修法王/public/petition.php">連署</a>
        <a href="/修法王/public/apply.php">我要報名</a>
    HTML;
    if ($includeAuth) {
        if (isLoggedIn()) {
            $user = currentUser();
            $name = htmlspecialchars($user['real_name'], ENT_QUOTES, 'UTF-8');
            echo "    <span style='color:#aaa;margin-left:1rem'>👤 {$name}</span>";
            echo "    <a href='/修法王/public/logout.php'>登出</a>";
        } else {
            echo "    <a href='/修法王/public/login.php'>登入</a>";
            echo "    <a href='/修法王/public/register.php'>註冊</a>";
        }
    }
    echo <<<HTML
      </div>
    </nav>
    HTML;
}

function pageFooter(): void {
    echo <<<HTML
    <footer style="text-align:center;padding:2rem;color:#999;margin-top:3rem;border-top:1px solid #eee">
      <p>© 2026 修法王 — 推動兒少性侵追訴期修法公益活動</p>
    </footer>
    </body></html>
    HTML;
}
