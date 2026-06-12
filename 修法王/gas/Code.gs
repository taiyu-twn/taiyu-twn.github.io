// ================================================================
// 修法王 — Google Apps Script 後端
// 部署方式：擴充功能 > Apps Script > 貼上 > 部署為網路應用程式
// 執行身分：我（開發者）  存取權限：任何人（包含匿名）
// ================================================================
// Script Properties 需設定（專案設定 > 指令碼屬性）：
//   SHEET_ID          Google Sheet 的 ID
//   DRIVE_FOLDER_ID   Google Drive 照片資料夾 ID
//   HASH_SALT         隨機字串（至少32字元）
//   SMS_USERNAME      三竹帳號
//   SMS_PASSWORD      三竹密碼
//   SMS_TEST_MODE     true / false
//   ADMIN_PASSWORD    管理員密碼（明文，GAS 內部比對）
// ================================================================

const PROPS = PropertiesService.getScriptProperties();
const SHEET_ID        = PROPS.getProperty('SHEET_ID');
const DRIVE_FOLDER_ID = PROPS.getProperty('DRIVE_FOLDER_ID');
const HASH_SALT       = PROPS.getProperty('HASH_SALT') || 'default_salt';
const SMS_TEST_MODE   = PROPS.getProperty('SMS_TEST_MODE') === 'true';
const ADMIN_PASSWORD  = PROPS.getProperty('ADMIN_PASSWORD') || 'admin123';

const SHEETS = { SUBMISSIONS:'submissions', USERS:'users', SIGNATURES:'signatures', VOTES:'votes' };
const PETITION_TARGET = 100000;

// ── Entry Point ──────────────────────────────────────────────

function doPost(e) {
  try {
    const data   = JSON.parse(e.postData.getDataAsString());
    const action = data.action || '';

    // 不需登入的 actions
    const publicActions = [
      'login','register','sendOTP','getVideos','getVideo',
      'getStats','adminLogin','getPetitionCount'
    ];

    let user = null;
    if (!publicActions.includes(action)) {
      user = getSessionUser(data.token);
      if (!user && !action.startsWith('admin')) {
        return json({ status:'error', msg:'請先登入', code:'AUTH' });
      }
    }

    // Admin actions 驗證
    if (action.startsWith('admin') && action !== 'adminLogin') {
      if (!verifyAdminToken(data.adminToken)) {
        return json({ status:'error', msg:'管理員驗證失敗', code:'AUTH' });
      }
    }

    switch (action) {
      case 'login':           return handleLogin(data);
      case 'register':        return handleRegister(data);
      case 'sendOTP':         return handleSendOTP(data);
      case 'vote':            return handleVote(data, user);
      case 'sign':            return handleSign(data, user);
      case 'apply':           return handleApply(data);
      case 'getVideos':       return handleGetVideos(data);
      case 'getVideo':        return handleGetVideo(data);
      case 'getStats':        return handleGetStats(data);
      case 'getPetitionCount':return handleGetPetitionCount();
      case 'adminLogin':      return handleAdminLogin(data);
      case 'adminStats':      return handleAdminStats(data);
      case 'adminList':       return handleAdminList(data);
      case 'adminDetail':     return handleAdminDetail(data);
      case 'adminApprove':    return handleAdminReview(data, 'approved');
      case 'adminReject':     return handleAdminReview(data, 'rejected');
      case 'adminHide':       return handleAdminReview(data, 'hidden');
      default: return json({ status:'error', msg:'未知操作' });
    }
  } catch (err) {
    return json({ status:'error', msg:err.message });
  }
}

function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({ status:'ok', service:'修法王 API' }))
    .setMimeType(ContentService.MimeType.JSON);
}

// ── 帳號系統 ─────────────────────────────────────────────────

function handleSendOTP(data) {
  const phone = (data.phone || '').trim();
  if (!/^09\d{8}$/.test(phone)) return json({ status:'error', msg:'手機號碼格式不正確' });

  const otp = SMS_TEST_MODE ? '123456' : String(Math.floor(100000 + Math.random() * 900000));
  const cache = CacheService.getScriptCache();
  cache.put('otp_' + phone, otp, 600); // 10 分鐘

  if (!SMS_TEST_MODE) {
    sendSMS(phone, otp);
  }

  return json({ status:'success', testMode: SMS_TEST_MODE });
}

function handleRegister(data) {
  const realName = (data.realName || '').trim();
  const idNumber = (data.idNumber || '').toUpperCase().trim();
  const phone    = (data.phone || '').trim();
  const password = data.password || '';
  const otp      = (data.otp || '').trim();

  if (!realName) return json({ status:'error', msg:'請填寫真實姓名' });
  if (!/^[A-Z][12]\d{8}$/.test(idNumber)) return json({ status:'error', msg:'身分證字號格式不正確' });
  if (!/^09\d{8}$/.test(phone)) return json({ status:'error', msg:'手機號碼格式不正確' });
  if (password.length < 8) return json({ status:'error', msg:'密碼至少 8 個字元' });

  const cache      = CacheService.getScriptCache();
  const storedOTP  = cache.get('otp_' + phone);
  if (!storedOTP || storedOTP !== otp) return json({ status:'error', msg:'驗證碼不正確或已過期' });
  cache.remove('otp_' + phone);

  const existing = findRow(SHEETS.USERS, 'phone', phone);
  if (existing) return json({ status:'error', msg:'此手機號碼已被註冊' });

  const idHash  = sha256(HASH_SALT + idNumber);
  const pwdHash = sha256(HASH_SALT + password);
  const userId  = generateId('USR');
  const token   = generateToken();
  const expires = new Date(Date.now() + 30 * 86400000).toISOString();

  appendSheet(SHEETS.USERS, [
    userId, realName, idHash, phone, pwdHash,
    'true', 'false', token, expires, new Date().toISOString()
  ]);

  return json({ status:'success', token, userId, realName, hasSigned: false });
}

function handleLogin(data) {
  const phone    = (data.phone || '').trim();
  const password = data.password || '';

  const user = findRow(SHEETS.USERS, 'phone', phone);
  if (!user) return json({ status:'error', msg:'手機號碼或密碼不正確' });

  const pwdHash = sha256(HASH_SALT + password);
  if (user.password_hash !== pwdHash) return json({ status:'error', msg:'手機號碼或密碼不正確' });

  // 更新 token
  const token   = generateToken();
  const expires = new Date(Date.now() + 30 * 86400000).toISOString();
  const idx     = findRowIndex(SHEETS.USERS, 'phone', phone);
  updateCell(SHEETS.USERS, idx, 8, token);
  updateCell(SHEETS.USERS, idx, 9, expires);

  return json({
    status:    'success',
    token,
    userId:    user.user_id,
    realName:  user.real_name,
    hasSigned: user.has_signed === 'true',
    idHash:    user.id_number_hash
  });
}

function getSessionUser(token) {
  if (!token) return null;
  const user = findRow(SHEETS.USERS, 'session_token', token);
  if (!user) return null;
  if (new Date(user.token_expires) < new Date()) return null;
  return user;
}

// ── 投票 ─────────────────────────────────────────────────────

function handleVote(data, user) {
  const videoId = (data.videoId || '').trim();
  if (!videoId) return json({ status:'error', msg:'缺少影片 ID' });

  const video = findRow(SHEETS.SUBMISSIONS, 'id', videoId);
  if (!video || video.status !== 'approved') return json({ status:'error', msg:'影片不存在' });

  const today  = getTaipeiDate();
  const idHash = user.id_number_hash;

  const votes = readSheet(SHEETS.VOTES);
  const dup   = votes.find(r =>
    r.id_number_hash === idHash && r.video_id === videoId && r.vote_date === today
  );
  if (dup) return json({ status:'already_voted', msg:'今日已投票，請明天再來' });

  appendSheet(SHEETS.VOTES, [
    generateId('VOT'), user.user_id, idHash, videoId, today, new Date().toISOString()
  ]);

  const newCount = readSheet(SHEETS.VOTES).filter(r => r.video_id === videoId).length;
  return json({ status:'success', newCount });
}

// ── 連署 ─────────────────────────────────────────────────────

function handleSign(data, user) {
  const idHash = user.id_number_hash;
  const dup    = findRow(SHEETS.SIGNATURES, 'id_number_hash', idHash);
  const total  = readSheet(SHEETS.SIGNATURES).length + (dup ? 0 : 1);

  if (dup) {
    return json({ status:'already_signed', msg:'您已完成連署', total });
  }

  appendSheet(SHEETS.SIGNATURES, [
    generateId('SIG'), user.user_id, idHash, new Date().toISOString()
  ]);

  const idx = findRowIndex(SHEETS.USERS, 'user_id', user.user_id);
  if (idx > 0) updateCell(SHEETS.USERS, idx, 7, 'true');

  return json({ status:'success', total });
}

// ── 報名 ─────────────────────────────────────────────────────

function handleApply(data) {
  const required = ['teamName','memberCount','contactName','idNumber','phone','email','videoUrl','category'];
  for (const f of required) {
    if (!data[f]) return json({ status:'error', msg:`缺少必填欄位：${f}` });
  }

  const idNumber = data.idNumber.toUpperCase().trim();
  if (!/^[A-Z][12]\d{8}$/.test(idNumber)) return json({ status:'error', msg:'身分證字號格式不正確' });
  if (!/^09\d{8}$/.test(data.phone)) return json({ status:'error', msg:'手機號碼格式不正確' });
  if (!isValidYouTubeUrl(data.videoUrl)) return json({ status:'error', msg:'YouTube 網址格式不正確' });
  if (!['general','streamer','student'].includes(data.category)) return json({ status:'error', msg:'請選擇參賽身分' });

  if (data.category === 'student' && (!data.school || !data.studentId)) {
    return json({ status:'error', msg:'學生參賽者請填寫學校與學號' });
  }

  // 儲存照片到 Google Drive
  let idPhotoId = '', studentPhotoId = '';
  if (data.idPhotoBase64) {
    idPhotoId = savePhotoToDrive(data.idPhotoBase64, `id_${Date.now()}`);
  } else {
    return json({ status:'error', msg:'請上傳身分證照片' });
  }
  if (data.category === 'student' && data.studentPhotoBase64) {
    studentPhotoId = savePhotoToDrive(data.studentPhotoBase64, `student_${Date.now()}`);
  }

  const idHash = sha256(HASH_SALT + idNumber);
  const id     = generateId('SUB');

  appendSheet(SHEETS.SUBMISSIONS, [
    id, data.teamName, data.memberCount, data.contactName, idHash,
    data.phone, data.email, data.videoUrl, data.igUrl || '', data.platformId || '',
    data.category, idPhotoId, studentPhotoId, 'pending',
    new Date().toISOString(), '', ''
  ]);

  sendConfirmEmail(data.email, data.teamName);
  return json({ status:'success', id });
}

// ── 影片列表 ──────────────────────────────────────────────────

function handleGetVideos(data) {
  const videos  = readSheet(SHEETS.SUBMISSIONS).filter(r => r.status === 'approved');
  const allVotes = readSheet(SHEETS.VOTES);

  const result = videos.map(v => ({
    id:       v.id,
    teamName: v.team_name,
    videoUrl: v.video_url,
    igUrl:    v.ig_url || '',
    category: v.category,
    votes:    allVotes.filter(vt => vt.video_id === v.id).length
  })).sort((a, b) => b.votes - a.votes);

  return json({ status:'success', videos: result });
}

function handleGetVideo(data) {
  const video = findRow(SHEETS.SUBMISSIONS, 'id', data.videoId);
  if (!video || video.status !== 'approved') return json({ status:'error', msg:'找不到影片' });

  const votes = readSheet(SHEETS.VOTES).filter(r => r.video_id === data.videoId).length;
  return json({
    status: 'success',
    video: {
      id:       video.id,
      teamName: video.team_name,
      videoUrl: video.video_url,
      igUrl:    video.ig_url || '',
      category: video.category,
      votes
    }
  });
}

function handleGetStats(data) {
  const videos    = readSheet(SHEETS.SUBMISSIONS).filter(r => r.status === 'approved');
  const allVotes  = readSheet(SHEETS.VOTES);
  const sigs      = readSheet(SHEETS.SIGNATURES).length;
  const today     = getTaipeiDate();
  const todayVotes = allVotes.filter(r => r.vote_date === today).length;

  const ranked = videos.map(v => ({
    id:       v.id,
    teamName: v.team_name,
    videoUrl: v.video_url,
    igUrl:    v.ig_url || '',
    category: v.category,
    votes:    allVotes.filter(vt => vt.video_id === v.id).length
  })).sort((a, b) => b.votes - a.votes);

  const lawKing     = ranked[0] || null;
  const rest        = ranked.slice(1);
  const publicTop4  = rest.filter(v => ['general','streamer'].includes(v.category)).slice(0, 4);
  const studentTop4 = rest.filter(v => v.category === 'student').slice(0, 4);

  return json({ status:'success', sigs, todayVotes, target: PETITION_TARGET, lawKing, publicTop4, studentTop4 });
}

function handleGetPetitionCount() {
  const total = readSheet(SHEETS.SIGNATURES).length;
  return json({ status:'success', total, target: PETITION_TARGET });
}

// ── 管理後台 ──────────────────────────────────────────────────

function handleAdminLogin(data) {
  if ((data.password || '') !== ADMIN_PASSWORD) {
    return json({ status:'error', msg:'密碼不正確' });
  }
  const token = generateToken();
  CacheService.getScriptCache().put('admin_' + token, '1', 28800); // 8 小時
  return json({ status:'success', adminToken: token });
}

function verifyAdminToken(token) {
  if (!token) return false;
  return CacheService.getScriptCache().get('admin_' + token) === '1';
}

function handleAdminStats(data) {
  const subs      = readSheet(SHEETS.SUBMISSIONS);
  const allVotes  = readSheet(SHEETS.VOTES);
  const sigs      = readSheet(SHEETS.SIGNATURES).length;
  const today     = getTaipeiDate();
  return json({
    status:     'success',
    pending:    subs.filter(r => r.status === 'pending').length,
    approved:   subs.filter(r => r.status === 'approved').length,
    rejected:   subs.filter(r => r.status === 'rejected').length,
    totalSigs:  sigs,
    totalVotes: allVotes.length,
    todayVotes: allVotes.filter(r => r.vote_date === today).length
  });
}

function handleAdminList(data) {
  const filter = data.filter || 'all';
  let rows = readSheet(SHEETS.SUBMISSIONS);
  if (filter !== 'all') rows = rows.filter(r => r.status === filter);
  return json({ status:'success', rows });
}

function handleAdminDetail(data) {
  const row = findRow(SHEETS.SUBMISSIONS, 'id', data.id);
  if (!row) return json({ status:'error', msg:'找不到資料' });
  const votes = readSheet(SHEETS.VOTES).filter(r => r.video_id === data.id).length;
  return json({ status:'success', row, votes });
}

function handleAdminReview(data, newStatus) {
  const idx = findRowIndex(SHEETS.SUBMISSIONS, 'id', data.id);
  if (idx < 0) return json({ status:'error', msg:'找不到資料' });

  const headers = getHeaders(SHEETS.SUBMISSIONS);
  const statusCol  = headers.indexOf('status') + 1;
  const reviewedCol = headers.indexOf('reviewed_at') + 1;
  const reasonCol  = headers.indexOf('reject_reason') + 1;

  updateCell(SHEETS.SUBMISSIONS, idx, statusCol, newStatus);
  updateCell(SHEETS.SUBMISSIONS, idx, reviewedCol, new Date().toISOString());

  if (newStatus === 'rejected' && data.reason) {
    updateCell(SHEETS.SUBMISSIONS, idx, reasonCol, data.reason);
    const row = findRow(SHEETS.SUBMISSIONS, 'id', data.id);
    if (row) sendRejectEmail(row.email, row.team_name, data.reason);
  }

  return json({ status:'success' });
}

// ── Google Sheets 工具 ────────────────────────────────────────

function getSheet(name) {
  return SpreadsheetApp.openById(SHEET_ID).getSheetByName(name);
}

function readSheet(name) {
  const sheet = getSheet(name);
  const data  = sheet.getDataRange().getValues();
  if (data.length < 2) return [];
  const headers = data[0];
  return data.slice(1).map(row => {
    const obj = {};
    headers.forEach((h, i) => obj[h] = String(row[i] ?? ''));
    return obj;
  });
}

function appendSheet(name, values) {
  getSheet(name).appendRow(values);
}

function getHeaders(name) {
  return getSheet(name).getRange(1, 1, 1, getSheet(name).getLastColumn()).getValues()[0];
}

function findRow(name, col, value) {
  return readSheet(name).find(r => r[col] === value) || null;
}

function findRowIndex(name, col, value) {
  const rows = readSheet(name);
  const idx  = rows.findIndex(r => r[col] === value);
  return idx >= 0 ? idx + 2 : -1; // +2: 1-based + header row
}

function updateCell(name, rowIdx, colIdx, value) {
  getSheet(name).getRange(rowIdx, colIdx).setValue(value);
}

// ── Google Drive 照片存檔 ─────────────────────────────────────

function savePhotoToDrive(base64Data, filename) {
  const match  = base64Data.match(/^data:(image\/\w+);base64,(.+)$/);
  if (!match) throw new Error('照片格式錯誤');
  const mime   = match[1];
  const bytes  = Utilities.base64Decode(match[2]);
  const blob   = Utilities.newBlob(bytes, mime, filename + '.' + mime.split('/')[1]);
  const folder = DriveApp.getFolderById(DRIVE_FOLDER_ID);
  const file   = folder.createFile(blob);
  file.setSharing(DriveApp.Access.ANYONE_WITH_LINK, DriveApp.Permission.VIEW);
  return file.getId();
}

// ── SMS ───────────────────────────────────────────────────────

function sendSMS(phone, otp) {
  const smsUser = PROPS.getProperty('SMS_USERNAME');
  const smsPwd  = PROPS.getProperty('SMS_PASSWORD');
  const msg     = `【修法王】您的驗證碼為 ${otp}，10分鐘內有效。`;
  const url     = `https://sms.mitake.com.tw/b2c/mtk/SmSend?username=${encodeURIComponent(smsUser)}&password=${encodeURIComponent(smsPwd)}&dstaddr=${phone}&smbody=${encodeURIComponent(msg)}&charset=UTF-8`;
  UrlFetchApp.fetch(url);
}

// ── Email ─────────────────────────────────────────────────────

function sendConfirmEmail(to, teamName) {
  MailApp.sendEmail({
    to, subject:'【修法王】報名成功確認通知',
    htmlBody: `<h2>恭喜！您已成功報名修法王活動</h2><p>組名：<strong>${teamName}</strong></p><p>我們將在 3 個工作天內完成審核。</p>`
  });
}

function sendRejectEmail(to, teamName, reason) {
  MailApp.sendEmail({
    to, subject:'【修法王】報名審核結果通知',
    htmlBody: `<h2>修法王報名審核結果</h2><p>組名：<strong>${teamName}</strong></p><p>很遺憾，您的報名未通過審核。</p><p>原因：${reason}</p>`
  });
}

// ── 工具函式 ──────────────────────────────────────────────────

function sha256(input) {
  const bytes = Utilities.computeDigest(
    Utilities.DigestAlgorithm.SHA_256, input, Utilities.Charset.UTF_8
  );
  return bytes.map(b => ('0' + (b & 0xFF).toString(16)).slice(-2)).join('');
}

function generateId(prefix) {
  return prefix + new Date().getTime() + Math.random().toString(36).slice(2, 6).toUpperCase();
}

function generateToken() {
  return Utilities.base64Encode(Utilities.generateKey ? Utilities.generateKey(32) :
    Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256,
      Math.random().toString() + Date.now()
    )
  ).replace(/[^a-zA-Z0-9]/g, '').slice(0, 48);
}

function getTaipeiDate() {
  return Utilities.formatDate(new Date(), 'Asia/Taipei', 'yyyy-MM-dd');
}

function isValidYouTubeUrl(url) {
  return /^https:\/\/(www\.)?youtube\.com\/(shorts\/|watch\?v=)[\w\-]+/.test(url) ||
         /^https:\/\/youtu\.be\/[\w\-]+/.test(url);
}

function json(data) {
  return ContentService.createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}
