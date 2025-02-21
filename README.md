# 食安巡檢平台

## 簡介
本專案為「食安巡檢平台」，用於監控與管理食品安全檢查流程。該系統基於 Laravel 框架開發，提供後台管理、報表生成、用戶權限管理等功能，並支援多種數據可視化工具。

## 技術棧
- **後端**：Laravel  
- **前端**：Blade, SCSS, JavaScript
- **數據庫**：MySQL
- **其他**：Redis, Composer, NPM

## 目錄結構
```
foodsafety-main/
├── app/                # 核心應用程式 (Controllers, Models, Services)
├── bootstrap/          # Laravel 啟動文件
├── config/             # 設定檔案 (database, mail, cache 等)
├── database/           # 數據遷移與 Seeder
├── public/             # 靜態資源 (CSS, JS, Images)
├── resources/          # Blade 模板, SCSS, Vue, JS
├── routes/             # API 與 Web 路由 (web.php, api.php)
├── storage/            # 快取與日誌儲存
├── tests/              # 單元與功能測試
├── .env.example        # 環境變數範例
├── composer.json       # PHP 依賴管理
├── package.json        # NPM 依賴管理
├── README.md           # 本文件
```

## 環境設置
### 1. 安裝相依套件
```sh
composer install
php artisan key:generate
npm install
npm run build
```

### 2. 設定環境變數
請將 `.env.example` 複製並重命名為 `.env`，然後修改相關設定：
```sh
cp .env.example .env
```

### 3. 設定資料庫
請在 `.env` 文件內配置 MySQL 資訊，然後執行：
```sh
php artisan migrate --seed
php artisan storage:link
```

### 4. 啟動伺服器
```sh
php artisan serve
```

## Git Pull
```sh
cd /var/www/html/Foodsafety/laravel
sudo git pull --rebase
```

## 主要功能
- **檢查管理**：新增、編輯、刪除巡檢數據
- **用戶管理**：權限分級、角色管理
- **報表系統**：生成與匯出巡檢報告
- **數據可視化**：圖表展示巡檢結果

## 圖片上傳
需要 `storage/app/public` 下建立 `uploads` 資料夾。

## 帳號資訊
- **開發者帳號**
    - **UID**: 001
    - **Password**: vu;31up

## EER 圖
![eer](https://i.imgur.com/w42sNb5.png)

## 伺服器需求
- PHP >= 8.0

## 相關文件與工具
- [cork](https://designreset.com/cork/documentation/laravel/index.html)
- [laravel-permission](https://spatie.be/docs/laravel-permission/v5/introduction)
- [Sopamo/laravel-filepond](https://github.com/Sopamo/laravel-filepond)

## 測試
執行 Laravel 測試：
```sh
php artisan test
```

## 部署
1. 確保 `.env` 配置正確
2. 使用 `php artisan config:cache` 加速設定
3. 部署至 Apache/Nginx，並設定 `public/` 為根目錄

## 貢獻
如果你希望為本專案做出貢獻，請 fork 並提交 PR，或聯絡管理員。

## 聯絡方式
若有問題，請聯繫 IT 部門或相關負責人。

---
**版權所有 &copy; 2025 饗賓**
