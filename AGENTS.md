# SysGYM — PHP Gym Management System

## Stack
- **Language:** PHP 7+ (pure procedural/OO, no framework)
- **DB:** MySQL via PDO (`inc/config.php` has `db()` singleton)
- **PDF:** Dompdf (`vendor/dompdf/dompdf`)
- **Payments:** MercadoPago SDK (`vendor/mercadopago/dx-php`)
- **WhatsApp:** Custom bot via external WhatsApp API (config in `system_settings` DB table)
- **Composer:** Dependencies in `vendor/`, `composer.lock` present

## Key Directories
- `inc/` — Config (`config.php`), DB connection, global helpers, `$settings` loaded from DB
- `admin/` — Internal dashboard (redirected from root `/`)
- `api/` — Public API endpoint (`submit.php`)
- `whatsapp/` — WhatsApp outbound scripts (one-way alerts/reminders)
- `webhook/` — WhatsApp bot webhook (`index.php`) — stateful menu bot with `estados_ws.json`
- `pay/` — MercadoPago payment flow: crear_pago → webhook_mp → pago_exitoso/fallido/pendiente
- `pdf/` — PDF generation via Dompdf (`index.php` routes by `type` param to `cert.php`, `invoice.php`, `consent.php`, etc.)
- `mailer/` — Email sending
- `consent-pending/` — Consent form flow
- `vendor/` — Composer dependencies (do not edit)

## Important Quirks
- **DB credentials** are in `inc/url_bd.php` — this file is **gitignored**
- **System config** is dynamic: `$settings` array loaded at runtime from `system_settings` DB table
- **Timezone:** Always `America/Bogota` (set in `config.php`)
- **WhatsApp bot** is stateful; session state stored in `webhook/estados_ws.json`
- **PDF base path** hardcoded in `pdf/index.php:35`: `$basePath = $_SERVER['DOCUMENT_ROOT'] . '/activgym/pdf/';`
- **MercadoPago** redirect URLs use `$url` (set from `URLBASE` constant)
- **No test suite** — no `phpunit.xml`, no `tests/` directory
- **No JS/CSS build tools** — static assets only

## Working with this codebase
- `composer install` to restore vendor dependencies
- `composer dump-autoload` after adding new files
- Never commit `inc/url_bd.php` or `inc/connect.php`
- The WhatsApp API key is stored in `system_settings` DB table (`wa_api`), not in env files
- PDFs are rendered by including `pdf/{type}.php` via output buffering, then streaming through Dompdf