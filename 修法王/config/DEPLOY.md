# 修法王 — 部署說明（Synology NAS）

## 1. Synology Web Station 設定

1. 開啟 **套件中心** → 安裝 **Web Station**
2. 開啟 **Web Station** → **Web Service Portal** → 新增
   - 類型：名稱虛擬主機 或 連接埠虛擬主機
   - 文件根目錄：`/volume1/web/修法王`
   - PHP 版本：選 **PHP 8.x**
3. 確認 PHP 擴充功能已啟用：
   - `curl`、`mbstring`、`openssl`、`json`

---

## 2. Router Port Forwarding

在你的家用路由器管理介面（通常 192.168.1.1）：

| 服務 | 外部 Port | 內部 Port | 協定 |
|------|-----------|-----------|------|
| HTTP  | 80  | 80  | TCP |
| HTTPS | 443 | 443 | TCP |

目標 IP = NAS 的區網 IP（例如 192.168.1.100）

---

## 3. Synology DDNS 設定

1. **控制台** → **外部存取** → **DDNS**
2. 點選「新增」
   - 服務供應商：`Synology`
   - 主機名稱：`yourname.synology.me`
3. 記下這個 DDNS 網域名稱

---

## 4. 自訂網域 DNS A Record

在你的網域註冊商（如 GoDaddy、Cloudflare、HiNet）：

```
類型：A
名稱：@ 或 www
值：你的公網 IP（可到 whatismyip.com 查詢）
TTL：300
```

或者直接 CNAME 指向 DDNS：

```
類型：CNAME
名稱：www
值：yourname.synology.me
```

---

## 5. Let's Encrypt SSL 申請（透過 Synology）

1. **控制台** → **安全性** → **憑證**
2. 點選「新增」→「取得 Let's Encrypt 憑證」
3. 填入：
   - 網域名稱：`yourdomain.com`
   - 電子郵件：`your@email.com`
4. 等待申請完成（需確認 Port 80 已對外開放）
5. 設定自動更新（Synology 預設每 90 天自動更新）

---

## 6. PHP 8.x + Composer 安裝

```bash
# SSH 進入 NAS
ssh admin@192.168.1.100

# 確認 PHP 版本
php -v

# 安裝 Composer（若未安裝）
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# 進入專案目錄
cd /volume1/web/修法王

# 安裝依賴套件
composer install --optimize-autoloader
```

---

## 7. Google credentials.json 設定

1. 至 [Google Cloud Console](https://console.cloud.google.com/) 建立專案
2. 啟用 **Google Sheets API** 與 **Google Drive API**
3. 建立**服務帳號**（Service Account）
4. 下載 JSON 金鑰，命名為 `credentials.json`
5. 放置路徑：`/volume1/web/修法王/config/credentials.json`
6. 設定檔案權限：

```bash
chmod 600 /volume1/web/修法王/config/credentials.json
chown www-data:www-data /volume1/web/修法王/config/credentials.json
```

7. 在 Google Sheet 中，將服務帳號 Email 加入「編輯者」權限

---

## 8. 上傳目錄權限

```bash
chmod 755 /volume1/web/修法王/uploads/
chown www-data:www-data /volume1/web/修法王/uploads/
```

---

## 9. 管理員密碼產生

```php
<?php
// 執行一次，取得 bcrypt hash
echo password_hash('你的密碼', PASSWORD_BCRYPT);
```

將產生的 hash 填入 `config/config.php` 的 `ADMIN_PASSWORD`。

---

## 10. 上線前 Checklist

### 安全設定
- [ ] `config.php` 中的 `HASH_SALT` 已改成隨機字串（建議 32 字元以上）
- [ ] `SMS_TEST_MODE` 改為 `false`
- [ ] `ADMIN_PASSWORD` 已更新為正式密碼的 bcrypt hash
- [ ] `SITE_URL` 已更新為正式網域
- [ ] `SMS_USERNAME` / `SMS_PASSWORD` 填入真實三竹帳密
- [ ] Google Sheet ID 和 Drive Folder ID 已設定

### 資料確認
- [ ] Google Sheet 已建立四個工作表（submissions/users/signatures/votes）
- [ ] 標頭列已正確設定
- [ ] 服務帳號已加入 Sheet 編輯權限

### 功能測試
- [ ] 測試報名表單（提交並確認 Sheet 有新增資料）
- [ ] 測試 SMS 驗證碼發送
- [ ] 測試帳號註冊、登入
- [ ] 測試投票功能（確認一天一票限制）
- [ ] 測試連署功能
- [ ] 測試管理後台審核流程
- [ ] 確認照片上傳正常
- [ ] 確認 Email 發送正常

### 環境確認
- [ ] 時區設定為 `Asia/Taipei`（`date_default_timezone_set` 已執行）
- [ ] HTTPS 已啟用（Let's Encrypt 憑證有效）
- [ ] `uploads/` 目錄可寫入
- [ ] `credentials.json` 存在且可讀取
- [ ] Composer 依賴已安裝（`vendor/` 目錄存在）

### 上線前資料清空
```sql
-- 清空測試資料（只清 Sheet，保留標頭）
-- 至 Google Sheets 手動刪除第 2 列以後的所有資料列
```
