<?php
function adminHeader(string $title = '後台管理'): void {
    echo <<<HTML
    <!DOCTYPE html>
    <html lang="zh-Hant">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title} — 修法王後台</title>
    <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Noto Sans TC',sans-serif;background:#f4f6f8;color:#333}
    .sidebar{width:200px;background:#2c3e50;min-height:100vh;position:fixed;top:0;left:0;padding:1.5rem 0}
    .sidebar h2{color:#fff;text-align:center;font-size:1.1rem;margin-bottom:1.5rem;padding:0 1rem}
    .sidebar a{display:block;color:#ccc;padding:.7rem 1.5rem;text-decoration:none;font-size:.95rem}
    .sidebar a:hover,.sidebar a.active{background:#34495e;color:#fff}
    .main{margin-left:200px;padding:2rem}
    .topbar{background:#fff;padding:1rem 2rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center}
    .stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:2rem}
    .stat-card{background:#fff;border-radius:8px;padding:1.2rem;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.1)}
    .stat-card .num{font-size:2rem;font-weight:700;color:#2ecc71}
    .stat-card .label{color:#666;font-size:.85rem}
    .card{background:#fff;border-radius:8px;padding:1.5rem;box-shadow:0 1px 4px rgba(0,0,0,.1);margin-bottom:1.5rem}
    table{width:100%;border-collapse:collapse}
    th,td{text-align:left;padding:.7rem 1rem;border-bottom:1px solid #eee;font-size:.9rem}
    th{background:#f8f9fa;font-weight:600}
    tr:hover td{background:#f8f9fa}
    .btn{display:inline-block;padding:.4rem .9rem;border-radius:6px;border:none;cursor:pointer;font-size:.85rem;text-decoration:none}
    .btn-green{background:#2ecc71;color:#fff}.btn-green:hover{background:#27ae60}
    .btn-red{background:#e74c3c;color:#fff}
    .btn-gray{background:#95a5a6;color:#fff}
    .badge{display:inline-block;padding:.2rem .6rem;border-radius:99px;font-size:.75rem;font-weight:700}
    .badge-pending{background:#fff3cd;color:#856404}
    .badge-approved{background:#d4edda;color:#155724}
    .badge-rejected{background:#f8d7da;color:#721c24}
    .badge-hidden{background:#dee2e6;color:#495057}
    .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center}
    .modal-overlay.active{display:flex}
    .modal{background:#fff;border-radius:8px;padding:2rem;max-width:480px;width:90%;position:relative}
    .form-group{margin-bottom:1rem}
    label{display:block;font-weight:600;margin-bottom:.3rem}
    input,select,textarea{width:100%;padding:.6rem;border:1px solid #ccc;border-radius:6px;font-size:.9rem}
    </style>
    </head>
    <body>
    <div class="sidebar">
      <h2>⚖️ 修法王後台</h2>
      <a href="/修法王/admin/index.php">📊 總覽</a>
      <a href="/修法王/admin/review.php">📋 審核管理</a>
      <a href="/修法王/admin/login.php?logout=1">🚪 登出</a>
    </div>
    <div class="main">
    HTML;
}

function adminFooter(): void {
    echo '</div></body></html>';
}
