# ShopKey production deployment

## Server requirements

- PHP 8.2+ with PDO MySQL, OpenSSL, Mbstring, Fileinfo, Ctype, JSON and Tokenizer
- MySQL 8+, Composer 2, Node.js 20.19+ for release builds
- Nginx or Apache with the document root set to `/var/www/shopkey/public`
- A domain with HTTPS/TLS

## Release procedure

```bash
cd /var/www/shopkey
composer install --no-dev --classmap-authoritative --no-interaction
npm ci
npm run build
php artisan migrate --force
php artisan storage:link
php artisan optimize
```

Grant the web-server user write access only to `storage` and `bootstrap/cache`. Never make the whole repository world-writable.

Recommended production environment values:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://shopkey.example
LOG_LEVEL=warning
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
SANCTUM_EXPIRATION=43200
APP_CORS_ALLOWED_ORIGINS=https://app.shopkey.example
MOBILE_MIN_SUPPORTED_VERSION=1.0.0
MOBILE_LATEST_VERSION=1.0.0
MOBILE_FORCE_UPDATE=false
```

Add database, mail and Naptudong secrets through the host secret manager or an uncommitted `.env`. Register `https://shopkey.example/api/payment/naptudong/callback` with the provider.

## Nginx server block

```nginx
server {
    listen 443 ssl http2;
    server_name shopkey.example;
    root /var/www/shopkey/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }

    location ~ /\. { deny all; }
}
```

For Apache, enable `mod_rewrite`, allow the Laravel `public/.htaccess`, and point `DocumentRoot` to the same `public` directory.

## Operations

- Run `php artisan schedule:run` every minute; this is required for the SePay transaction sync fallback and scheduler health check.
- Run supervised `php artisan queue:work --sleep=3 --tries=3` when `QUEUE_CONNECTION` is asynchronous.
- Back up MySQL and uploaded files under `storage/app/public`.
- Monitor `storage/logs/laravel.log`, HTTP 5xx rate, pending deposits and low inventory.
- After each release, request `/up`, `/`, `/products`, `/login`, `/api/v1/app-config` and an authenticated API endpoint.
- Roll back application code and database only with a tested release procedure; never delete customer orders, deliveries or wallet ledgers.
