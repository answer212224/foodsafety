# 巡檢平台

### Installation

A step by step guide that will tell you how to get the development environment up and running.

```bash
composer install
php artisan key:generate
npm install
npm run build
php artisan migrate
php artisan storage:link
```

### image upload

需要 storage/app/public 下建立 uploads 資料夾

## git pull

```bash
cd /var/www/html/Foodsafety/laravel
sudo git pull --rebase
```

## start server

```bash
php artisan serve
```

## Account

-   開發者帳號
    -   UID: 001
    -   password: vu;31up

## EER

![eer](https://i.imgur.com/w42sNb5.png)

### Server

-   PHP >= 8.0

## Additional Documentation and Acknowledgments

-   [cork]https://designreset.com/cork/documentation/laravel/index.html
-   [laravel-permission](https://spatie.be/docs/laravel-permission/v5/introduction)
-   [Sopamo/laravel-filepond](https://github.com/Sopamo/laravel-filepond)
