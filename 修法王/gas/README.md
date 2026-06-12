# GAS 部署步驟

## 1. 建立 Google Sheets

建立一個新的 Google Sheet，新增以下 5 個工作表（名稱要完全一致）：

### submissions
```
id | team_name | member_count | contact_name | id_number_hash | phone | email | video_url | ig_url | platform_id | category | id_photo_drive_id | student_photo_drive_id | status | submitted_at | reviewed_at | reject_reason
```

### users
```
user_id | real_name | id_number_hash | phone | password_hash | is_verified | has_signed | session_token | token_expires | created_at
```

### signatures
```
sig_id | user_id | id_number_hash | signed_at
```

### votes
```
vote_id | user_id | id_number_hash | video_id | vote_date | voted_at
```

## 2. 建立 Google Drive 資料夾

建立一個資料夾專門存放照片，記下資料夾 ID（網址中的 `folders/` 後面那串）。

## 3. 建立 Apps Script 專案

1. 開啟 Google Sheet → **擴充功能** → **Apps Script**
2. 刪除預設程式碼
3. 貼上 `Code.gs` 的全部內容
4. 點擊 **儲存** 💾

## 4. 設定指令碼屬性

**專案設定（左側齒輪圖示）** → **指令碼屬性** → **新增屬性**：

| 屬性名稱 | 值 |
|---------|-----|
| `SHEET_ID` | Google Sheet 的 ID（網址中 `/d/` 和 `/edit` 之間那串） |
| `DRIVE_FOLDER_ID` | 步驟 2 的資料夾 ID |
| `HASH_SALT` | 隨機字串，至少 32 字元（可用密碼產生器） |
| `SMS_USERNAME` | 三竹簡訊帳號 |
| `SMS_PASSWORD` | 三竹簡訊密碼 |
| `SMS_TEST_MODE` | `true`（測試時）/ `false`（上線後） |
| `ADMIN_PASSWORD` | 管理員密碼（明文） |

## 5. 部署為網路應用程式

1. 右上角 **部署** → **新增部署作業**
2. 類型選 **網路應用程式**
3. 設定：
   - 說明：`修法王 API v1`
   - 執行身分：**我（你的帳號）**
   - 誰可以存取：**所有人（包括匿名使用者）**
4. 點選 **部署**
5. 複製 **網路應用程式網址**（長得像 `https://script.google.com/macros/s/AKfy.../exec`）

## 6. 更新前端設定

開啟 `public/js/app.js`，把第一行的網址換成你的 GAS 網址：

```javascript
const GAS_URL = 'https://script.google.com/macros/s/你的網址/exec';
```

## 7. 部署前端到 GitHub Pages

1. Push 程式碼到 GitHub
2. Repository Settings → Pages → 選 `master` branch
3. 等待幾分鐘，你的網站就會在 `https://你的帳號.github.io/修法王/public/` 上線

## 8. 每次更新 GAS 程式碼

修改 Code.gs 後，必須**重新部署**才會生效：
- 部署 → 管理部署作業 → 編輯 → 版本改「新版本」→ 部署

## 注意事項

- GAS 免費版每天可發送 100 封 Email（MailApp 配額）
- GAS 每日執行時間上限 6 分鐘（對本專案夠用）
- 如果 SMS_TEST_MODE=true，驗證碼固定為 `123456`，不會真的發簡訊
- 管理員 Token 存在 ScriptCache，每 8 小時需重新登入
