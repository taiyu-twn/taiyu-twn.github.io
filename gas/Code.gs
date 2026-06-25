// ═══════════════════════════════════════════════════════════════
// 泰宇減重追蹤系統 — Google Apps Script 後端
// 部署方式：發佈 > 部署為網路應用程式 > 任何人皆可存取
// Script Properties：ANTHROPIC_API_KEY = sk-ant-...
//                    SHEET_ID = Google 試算表的 ID
// ═══════════════════════════════════════════════════════════════

// ── 試算表 Sheet 名稱 ──────────────────────────────────────────
const SHEET_WEIGHT = 'Sheet1'; // 日期 | 體重 | 備註
const SHEET_CHECKS = 'Sheet2'; // 日期 | 項目ID | 是否完成
const SHEET_LOGS   = 'Sheet3'; // 日期時間 | 類型 | 用戶輸入 | AI回覆

// ── Claude API 設定 ────────────────────────────────────────────
const CLAUDE_MODEL  = 'claude-haiku-4-5-20251001';
const CLAUDE_TOKENS = 1024;

// ── CORS Headers ───────────────────────────────────────────────
function corsHeaders() {
  return {
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Methods': 'GET, POST, OPTIONS',
    'Access-Control-Allow-Headers': 'Content-Type',
    'Content-Type': 'application/json',
  };
}

function makeResponse(data) {
  return ContentService
    .createTextOutput(JSON.stringify(data))
    .setMimeType(ContentService.MimeType.JSON);
}

// ── 取得試算表 ─────────────────────────────────────────────────
function getSheet(name) {
  const props = PropertiesService.getScriptProperties();
  const id = props.getProperty('SHEET_ID');
  const ss = id
    ? SpreadsheetApp.openById(id)
    : SpreadsheetApp.getActiveSpreadsheet();
  return ss.getSheetByName(name);
}

// ═══════════════════════════════════════════════════════════════
// GET 路由
// ═══════════════════════════════════════════════════════════════
function doGet(e) {
  const action = e.parameter.action || '';
  try {
    if (action === 'getWeight') return makeResponse(getWeight());
    if (action === 'getChecks') return makeResponse(getChecks(e.parameter.date));
    if (action === 'getLogs')   return makeResponse(getLogs());
    return makeResponse({ error: 'unknown action: ' + action });
  } catch (err) {
    return makeResponse({ error: err.message });
  }
}

// ═══════════════════════════════════════════════════════════════
// POST 路由
// ═══════════════════════════════════════════════════════════════
function doPost(e) {
  const action = e.parameter.action || '';
  let body = {};
  try {
    body = JSON.parse(e.postData.contents || '{}');
  } catch (_) {}

  try {
    if (action === 'addWeight')   return makeResponse(addWeight(body));
    if (action === 'toggleCheck') return makeResponse(toggleCheck(body));
    if (action === 'analyze')     return makeResponse(analyze(body));
    return makeResponse({ error: 'unknown action: ' + action });
  } catch (err) {
    return makeResponse({ error: err.message });
  }
}

// ═══════════════════════════════════════════════════════════════
// getWeight — 取得所有體重記錄（Sheet1）
// ═══════════════════════════════════════════════════════════════
function getWeight() {
  const sheet = getSheet(SHEET_WEIGHT);
  const data  = sheet.getDataRange().getValues();
  const records = [];
  for (let i = 1; i < data.length; i++) {
    const [date, weight, note] = data[i];
    if (date && weight) {
      records.push({
        date:   String(date).slice(0, 10),
        weight: Number(weight),
        note:   note || '',
      });
    }
  }
  records.sort((a, b) => a.date.localeCompare(b.date));
  return { records };
}

// ═══════════════════════════════════════════════════════════════
// addWeight — 新增或更新體重（Sheet1）
// ═══════════════════════════════════════════════════════════════
function addWeight(body) {
  const { date, weight, note } = body;
  if (!date || !weight) throw new Error('date and weight required');

  const sheet = getSheet(SHEET_WEIGHT);
  const data  = sheet.getDataRange().getValues();

  // 檢查同一天是否已有記錄 → 更新
  for (let i = 1; i < data.length; i++) {
    if (String(data[i][0]).slice(0, 10) === date) {
      sheet.getRange(i + 1, 1, 1, 3).setValues([[date, weight, note || '']]);
      return { ok: true, updated: true };
    }
  }

  // 新增一列（確保 Sheet1 有標頭）
  if (data.length === 0 || (data.length === 1 && !data[0][0])) {
    sheet.getRange(1, 1, 1, 3).setValues([['日期', '體重(kg)', '備註']]);
  }
  sheet.appendRow([date, weight, note || '']);
  return { ok: true, updated: false };
}

// ═══════════════════════════════════════════════════════════════
// getChecks — 取得某天的清單狀態（Sheet2）
// ═══════════════════════════════════════════════════════════════
function getChecks(date) {
  if (!date) date = new Date().toISOString().slice(0, 10);

  const sheet  = getSheet(SHEET_CHECKS);
  const data   = sheet.getDataRange().getValues();
  const checks = {};

  for (let i = 1; i < data.length; i++) {
    const [rowDate, itemId, done] = data[i];
    if (String(rowDate).slice(0, 10) === date) {
      checks[itemId] = !!done;
    }
  }
  return { checks };
}

// ═══════════════════════════════════════════════════════════════
// toggleCheck — 新增或更新清單項目（Sheet2）
// ═══════════════════════════════════════════════════════════════
function toggleCheck(body) {
  const { date, id, done } = body;
  if (!date || !id) throw new Error('date and id required');

  const sheet = getSheet(SHEET_CHECKS);
  const data  = sheet.getDataRange().getValues();

  // 先確認標頭
  if (data.length === 0 || !data[0][0]) {
    sheet.getRange(1, 1, 1, 3).setValues([['日期', '項目ID', '是否完成']]);
    data.length = 0;
  }

  // 找到同日同ID → 更新
  for (let i = 1; i < data.length; i++) {
    if (String(data[i][0]).slice(0, 10) === date && data[i][1] === id) {
      sheet.getRange(i + 1, 3).setValue(done ? 1 : 0);
      return { ok: true };
    }
  }

  // 新增
  sheet.appendRow([date, id, done ? 1 : 0]);
  return { ok: true };
}

// ═══════════════════════════════════════════════════════════════
// getLogs — 取得 AI 日誌（Sheet3），最新 30 筆
// ═══════════════════════════════════════════════════════════════
function getLogs() {
  const sheet = getSheet(SHEET_LOGS);
  const data  = sheet.getDataRange().getValues();
  const logs  = [];

  for (let i = 1; i < data.length; i++) {
    const [dt, type, input, reply] = data[i];
    if (dt) {
      logs.push({
        date:    String(dt),
        type:    String(type || ''),
        tag:     typeToTag(String(type || '')),
        content: String(input || ''),
        ai:      String(reply || ''),
      });
    }
  }

  logs.reverse();
  return { logs: logs.slice(0, 30) };
}

function typeToTag(type) {
  if (type === '飲食記錄') return 'food';
  if (type === '運動記錄') return 'move';
  return 'free';
}

// ═══════════════════════════════════════════════════════════════
// analyze — 呼叫 Claude API 並儲存日誌（Sheet3）
// ═══════════════════════════════════════════════════════════════
function analyze(body) {
  const { type, input, weight, lost, remaining, unchecked } = body;
  if (!input) throw new Error('input required');

  const apiKey = PropertiesService.getScriptProperties().getProperty('ANTHROPIC_API_KEY');
  if (!apiKey) throw new Error('ANTHROPIC_API_KEY not set in Script Properties');

  const typeLabels = { food: '飲食記錄', move: '運動記錄', free: '自由提問' };
  const typeLabel  = typeLabels[type] || '自由提問';

  const systemPrompt = buildSystemPrompt(weight, lost, remaining, unchecked);
  const userMessage  = buildUserMessage(type, input);

  const payload = {
    model:      CLAUDE_MODEL,
    max_tokens: CLAUDE_TOKENS,
    system:     systemPrompt,
    messages: [{ role: 'user', content: userMessage }],
  };

  const options = {
    method:             'post',
    contentType:        'application/json',
    headers: {
      'x-api-key':         apiKey,
      'anthropic-version': '2023-06-01',
    },
    payload:            JSON.stringify(payload),
    muteHttpExceptions: true,
  };

  const res  = UrlFetchApp.fetch('https://api.anthropic.com/v1/messages', options);
  const json = JSON.parse(res.getContentText());

  if (json.error) throw new Error(json.error.message || JSON.stringify(json.error));

  const reply = json.content?.[0]?.text || '（無回應）';

  // 儲存到 Sheet3
  const sheet = getSheet(SHEET_LOGS);
  const data  = sheet.getDataRange().getValues();
  if (data.length === 0 || !data[0][0]) {
    sheet.getRange(1, 1, 1, 4).setValues([['日期時間', '類型', '用戶輸入', 'AI回覆']]);
  }
  const now = Utilities.formatDate(new Date(), 'Asia/Taipei', 'yyyy/MM/dd HH:mm');
  sheet.appendRow([now, typeLabel, input, reply]);

  return { reply };
}

// ═══════════════════════════════════════════════════════════════
// 提示詞建構
// ═══════════════════════════════════════════════════════════════
function buildSystemPrompt(weight, lost, remaining, unchecked) {
  const uncheckedStr = unchecked && unchecked.length
    ? '今天尚未完成的項目：\n' + unchecked.map(t => '・' + t).join('\n')
    : '今天所有項目都已完成！';

  return `你是泰宇的專屬 AI 減重教練，請用繁體中文、鼓勵但務實的語氣回應。

【泰宇的減重計畫】
- 起始體重：72.9 kg，目標：62.0 kg（共需減 10.9 kg）
- 目前體重：${weight} kg，已減：${lost} kg，距目標還有：${remaining} kg
- ${uncheckedStr}

【每日執行計畫】
飲食：
1. 早餐：混合飲（40g 蛋白粉 + 燕麥 + 亞麻仁籽 + 水）+ 水煮蛋 + 蘋果
2. 午餐：低油高蛋白外食（滷味、烤雞、蒸魚等，避免油炸）
3. 下午：混合飲 40g（無燕麥版）
4. 晚餐：混合飲 + 茶葉蛋（7-8 點前完成）
5. 運動後：蛋白素 2 匙 + 水

補充品：
- 起床：強健活力組合（全套，含倍欣）
- 早餐後、午餐後：好甘萃各 1 錠
- 晚餐後：強健活力組合（無倍欣）+ 好甘萃
- 睡前：加美D鈣片 2 顆

運動：
- 早上走路 30 分鐘
- 中午爬樓梯 18 層
- 晚上 9-10 點滑步機 1 小時

【回覆原則】
1. 具體、簡潔，不超過 250 字
2. 先肯定做到的事，再指出可改善的地方
3. 給出 1-2 個今天可立即執行的建議
4. 如果問到體重停滯，鼓勵並說明可能原因`;
}

function buildUserMessage(type, input) {
  const prefix = {
    food: '【今日飲食記錄】\n',
    move: '【今日運動記錄】\n',
    free: '',
  };
  return (prefix[type] || '') + input;
}
