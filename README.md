# ShopKey

ShopKey is a capstone-level marketplace for legitimate software keys, game keys, gift cards, subscriptions, and transferable digital accounts. The Laravel Blade website and future mobile clients use the same transactional services and MySQL database.

## Features

- Responsive marketplace homepage, catalog search/filter/sort/pagination, product details, related products and verified-purchase reviews.
- Session cart for guests and persistent user cart with quantity/stock checks and backend-validated vouchers.
- Atomic wallet checkout: locks stock and wallet, creates an order, debits the ledger, assigns encrypted inventory and completes delivery in one database transaction.
- Customer account, profile/password management, order history, delivered credentials, wallet ledger, card deposits and database notifications.
- Naptudong V2 card submission with configured providers/denominations, signed requests, signed callback verification, safe card storage and idempotent wallet credit.
- Role-protected admin dashboard with products, secure image uploads, categories, encrypted inventory imports, users, wallet adjustments, orders/refunds, deposits, vouchers and activity logs.
- Sanctum REST API under `/api/v1` with API Resources and consistent JSON responses.
- Mobile app APIs for runtime configuration, password recovery, session/device management, notifications, bank deposits, support chat, wishlists, recently viewed products and refund requests.
- Automated tests for authentication, authorization, catalog, admin, checkout rollback, unique delivery, reviews, API and payment callback replay protection.

## Technology

- PHP 8.2+, Laravel 12, Laravel Sanctum 4
- MySQL 8+ (SQLite in isolated tests)
- Blade, CSS, JavaScript, Vite
- Integer VND monetary values; Laravel encrypted casts for sensitive inventory

## Local installation

```bash
composer install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
php artisan optimize:clear
```

Set the `DB_*` values in `.env` and create the database first. With Laragon, the web server document root must point to the project's `public` directory so the site opens directly at the domain without `/public`.

Run locally with the configured Laragon domain or:

```bash
php artisan serve
npm run dev
```

## Development accounts

These credentials are created only by local seeders and must be replaced in non-local environments.

| Role | Email | Password |
| --- | --- | --- |
| Admin | `admin@shopkey.test` | `Admin@12345` |
| Customer | `test@example.com` | `password` |

The demo customer starts with a development wallet balance and can use `WELCOME10` or `SHOPKEY50` to test checkout.

## Naptudong configuration

```env
NAPTUDONG_PARTNER_ID=
NAPTUDONG_PARTNER_KEY=
NAPTUDONG_API_URL=https://naptudong.com/chargingws/v2
NAPTUDONG_CALLBACK_URL=https://your-domain.example/api/payment/naptudong/callback
NAPTUDONG_TIMEOUT=15
NAPTUDONG_PROVIDERS=VIETTEL,VINAPHONE,MOBIFONE
NAPTUDONG_DENOMINATIONS=10000,20000,30000,50000,100000,200000,300000,500000,1000000
```

Configure the public HTTPS callback URL in the Naptudong partner panel. The application never accepts a success state from the browser: it verifies `callback_sign`, locks the deposit row and credits the `amount` returned by the provider using a unique ledger idempotency key.

## REST API

The OpenAPI definition is at [docs/openapi.yaml](docs/openapi.yaml). Main endpoints:

- `POST /api/v1/register`, `POST /api/v1/login`, `POST /api/v1/logout`
- `GET /api/v1/app-config`, `GET /api/v1/home`, `GET /api/v1/faqs`, `GET /api/v1/policies`
- `POST /api/v1/forgot-password`, `POST /api/v1/reset-password`, `PUT /api/v1/change-password`
- `GET|DELETE /api/v1/sessions`, `POST /api/v1/logout-all`, `POST|DELETE /api/v1/devices`
- `GET /api/v1/categories`, `GET /api/v1/products`, `GET /api/v1/products/{slug}`
- `GET /api/v1/products/trending`, `GET /api/v1/products/recently-viewed`, `GET /api/v1/search/suggestions`
- `GET|POST /api/v1/cart`, `PATCH|DELETE /api/v1/cart/{id}`
- `POST /api/v1/checkout`
- `GET /api/v1/orders`, `GET /api/v1/orders/{order_code}`, order cancellation, retry and refund request actions
- `GET /api/v1/wallet`, `GET /api/v1/wallet/transactions`
- `POST /api/v1/deposits/card`, bank account/deposit creation, status and cancellation APIs
- `GET|PUT /api/v1/profile`
- Notification read APIs, support chat, wishlists and review management APIs

Send protected requests with `Authorization: Bearer <token>` and `Accept: application/json`.
The same endpoint reference and copy-ready examples are available to administrators at `/admin/api-docs`.

## Testing and quality checks

```bash
php artisan test
vendor/bin/pint --test
npm run build
php artisan optimize:clear
```

External payment calls are mocked in tests. The duplicate callback test proves a repeated provider callback produces one wallet transaction only.

## Deployment

See [docs/deployment.md](docs/deployment.md) for Nginx/Apache, HTTPS, permissions, cache, scheduler, backup and release instructions. The application web root is always `public`; `.env`, `APP_KEY`, database credentials and partner keys must not be committed.

Architecture, ERD and integrity rules are documented in [docs/architecture.md](docs/architecture.md).

The copy-ready mobile implementation prompts and acceptance specifications are available for [Android](docs/android-app-project-spec.md) and [iOS](docs/ios-app-project-spec.md).
