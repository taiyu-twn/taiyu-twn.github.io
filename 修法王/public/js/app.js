// ── 設定 ────────────────────────────────────────────────────
const GAS_URL = 'YOUR_GAS_WEB_APP_URL'; // 部署後換成實際網址

// ── API 呼叫 ─────────────────────────────────────────────────
async function api(action, data = {}) {
  const token      = getToken();
  const adminToken = getAdminToken();
  const payload    = { action, ...data };
  if (token)      payload.token      = token;
  if (adminToken) payload.adminToken = adminToken;

  const res = await fetch(GAS_URL, {
    method:  'POST',
    headers: { 'Content-Type': 'text/plain' },
    body:    JSON.stringify(payload)
  });
  return res.json();
}

// ── Auth (localStorage) ──────────────────────────────────────
function getToken()      { return localStorage.getItem('lrk_token'); }
function getAdminToken() { return localStorage.getItem('lrk_admin_token'); }
function isLoggedIn()    { return !!localStorage.getItem('lrk_token'); }
function isAdmin()       { return !!localStorage.getItem('lrk_admin_token'); }

function saveSession(data) {
  localStorage.setItem('lrk_token',     data.token);
  localStorage.setItem('lrk_user_id',   data.userId);
  localStorage.setItem('lrk_real_name', data.realName);
  localStorage.setItem('lrk_has_signed', data.hasSigned ? '1' : '0');
  localStorage.setItem('lrk_id_hash',   data.idHash || '');
}

function clearSession() {
  ['lrk_token','lrk_user_id','lrk_real_name','lrk_has_signed','lrk_id_hash'].forEach(k => localStorage.removeItem(k));
}

function hasSigned() { return localStorage.getItem('lrk_has_signed') === '1'; }
function markSigned() { localStorage.setItem('lrk_has_signed', '1'); }

// ── YouTube 工具 ──────────────────────────────────────────────
function ytVideoId(url) {
  const m = url.match(/(?:shorts\/|v=|youtu\.be\/)([a-zA-Z0-9_\-]+)/);
  return m ? m[1] : null;
}
function ytThumb(url)  { const id = ytVideoId(url); return id ? `https://img.youtube.com/vi/${id}/mqdefault.jpg` : ''; }
function ytEmbed(url)  { const id = ytVideoId(url); return id ? `https://www.youtube.com/embed/${id}` : ''; }

// ── 共用 CSS ──────────────────────────────────────────────────
const CSS = `
  @import url('https://fonts.googleapis.com/css2?family=Noto+Sans+TC:wght@400;600;700&display=swap');
  :root{--green:#2ecc71;--dark-green:#27ae60;--red:#e74c3c;--gold:#f1c40f;--dark:#2c3e50;--ig:#E1306C}
  *{box-sizing:border-box;margin:0;padding:0}
  body{font-family:'Noto Sans TC',sans-serif;background:#f8f9fa;color:#333}
  .navbar{background:var(--dark);color:#fff;padding:1rem 2rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.5rem}
  .navbar .brand{color:#fff;text-decoration:none;font-size:1.3rem;font-weight:700}
  .navbar a{color:#ccc;text-decoration:none;margin-left:1rem;font-size:.95rem}
  .navbar a:hover{color:#fff}
  .navbar .user-name{color:#aaa;margin-left:1rem;font-size:.9rem}
  .container{max-width:1100px;margin:2rem auto;padding:0 1rem}
  .card{background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.1);padding:1.5rem;margin-bottom:1.5rem}
  .btn{display:inline-block;padding:.6rem 1.4rem;border-radius:6px;border:none;cursor:pointer;font-size:.95rem;text-decoration:none;transition:.2s;font-family:inherit}
  .btn-green{background:var(--green);color:#fff}.btn-green:hover{background:var(--dark-green)}
  .btn-red{background:var(--red);color:#fff}
  .btn-gold{background:var(--gold);color:#333}
  .btn-ig{background:var(--ig);color:#fff}
  .btn-outline{background:transparent;border:2px solid var(--green);color:var(--green)}
  .btn-gray{background:#95a5a6;color:#fff}
  .btn:disabled{opacity:.5;cursor:not-allowed}
  .form-group{margin-bottom:1rem}
  label{display:block;font-weight:600;margin-bottom:.3rem}
  input,select,textarea{width:100%;padding:.6rem;border:1px solid #ccc;border-radius:6px;font-size:.95rem;font-family:inherit}
  input:focus,select:focus,textarea:focus{outline:none;border-color:var(--green)}
  .field-error{color:var(--red);font-size:.85rem;margin-top:.2rem}
  .alert-success{background:#d4edda;color:#155724;padding:1rem;border-radius:6px;margin-bottom:1rem}
  .alert-error{background:#f8d7da;color:#721c24;padding:1rem;border-radius:6px;margin-bottom:1rem}
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
  .modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:1000;align-items:center;justify-content:center}
  .modal-overlay.active{display:flex}
  .modal{background:#fff;border-radius:10px;padding:2rem;max-width:480px;width:90%;position:relative}
  .modal-close{position:absolute;top:.7rem;right:1rem;font-size:1.4rem;cursor:pointer;color:#666;background:none;border:none}
  .spinner{display:inline-block;width:20px;height:20px;border:3px solid #f3f3f3;border-top:3px solid var(--green);border-radius:50%;animation:spin .8s linear infinite;vertical-align:middle}
  @keyframes spin{to{transform:rotate(360deg)}}
  @media(max-width:600px){.navbar{gap:.5rem}.video-grid{grid-template-columns:1fr}}
`;

// ── 導覽列注入 ────────────────────────────────────────────────
function injectLayout(pageTitle = '') {
  // 注入 CSS
  const style = document.createElement('style');
  style.textContent = CSS;
  document.head.prepend(style);

  if (pageTitle) document.title = `${pageTitle} — 修法王`;

  const loggedIn = isLoggedIn();
  const name     = localStorage.getItem('lrk_real_name') || '';
  const authHtml = loggedIn
    ? `<span class="user-name">👤 ${escHtml(name)}</span><a href="/修法王/public/logout.html">登出</a>`
    : `<a href="/修法王/public/login.html">登入</a><a href="/修法王/public/register.html">註冊</a>`;

  const nav = document.createElement('nav');
  nav.className = 'navbar';
  nav.innerHTML = `
    <a class="brand" href="/修法王/public/index.html">⚖️ 修法王</a>
    <div>
      <a href="/修法王/public/videos.html">影片列表</a>
      <a href="/修法王/public/petition.html">連署</a>
      <a href="/修法王/public/apply.html">我要報名</a>
      ${authHtml}
    </div>`;
  document.body.prepend(nav);

  const footer = document.createElement('footer');
  footer.style.cssText = 'text-align:center;padding:2rem;color:#999;margin-top:3rem;border-top:1px solid #eee';
  footer.innerHTML = '© 2026 修法王 — 推動兒少性侵追訴期修法公益活動';
  document.body.appendChild(footer);
}

// ── 小工具 ────────────────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function numFmt(n) { return Number(n).toLocaleString('zh-TW'); }

function showMsg(el, msg, type = 'success') {
  el.className = `alert-${type}`;
  el.textContent = msg;
  el.style.display = 'block';
}

function categoryLabel(cat) {
  return { general:'一般大眾', streamer:'直播主', student:'在校學生' }[cat] || cat;
}

// ── 連署彈窗（影片頁用） ──────────────────────────────────────
function buildSignModal(onVote) {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.id = 'signModal';
  overlay.innerHTML = `
    <div class="modal">
      <button class="modal-close" id="skipSign">✕</button>
      <h2 style="margin-bottom:1rem">你聯署了嗎？</h2>
      <p style="color:#555;margin-bottom:1.5rem">加入我們，讓修法聲音被更多人聽見。</p>
      <button id="agreeSign" class="btn btn-green" style="width:100%;font-size:1.1rem;padding:1rem">我同意連署</button>
      <p style="text-align:center;margin-top:.8rem"><small><a href="/修法王/public/petition.html" target="_blank">了解修法內容</a></small></p>
    </div>`;
  document.body.appendChild(overlay);

  document.getElementById('skipSign').onclick = () => {
    overlay.classList.remove('active');
    onVote();
  };

  document.getElementById('agreeSign').onclick = async () => {
    const r = await api('sign');
    if (r.status === 'success' || r.status === 'already_signed') markSigned();
    overlay.classList.remove('active');
    onVote();
  };

  return overlay;
}
