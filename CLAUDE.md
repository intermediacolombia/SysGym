# CLAUDE.md — SysGym Codebase Guide

This file provides essential context for AI assistants working on the SysGym repository.

---

## Project Overview

**SysGym** is a comprehensive gym management system built for the Colombian market. It handles the full client lifecycle: pre-registration, membership plans, attendance tracking, payments (online via MercadoPago and in-person via POS), WhatsApp chatbot self-service, employee payroll, fitness assessments, and reporting.

**Target market:** Gyms in Colombia
**Timezone:** `America/Bogota` (hardcoded throughout the app)
**Language:** Spanish (UI labels, database column values, and comments are in Spanish)

---

## Technology Stack

### Backend
| Component | Technology |
|---|---|
| Language | PHP (procedural, no framework) |
| Database | MySQL 8.0+ via PDO |
| Dependency manager | Composer |
| PDF generation | dompdf/dompdf ^3.1 |
| Spreadsheet export | phpoffice/phpspreadsheet ^4.3 |
| Email | PHPMailer (via SMTP) |
| Payments | mercadopago/dx-php 3.x |

### Frontend
| Component | Technology |
|---|---|
| CSS framework | Bootstrap 5.3.0 (some pages still use 4.5.2) |
| JS utility | jQuery 3.6.0 |
| Data tables | DataTables 1.13.4 |
| Notifications/modals | SweetAlert2 11 |
| Date pickers | Flatpickr |
| Phone inputs | intl-tel-input 17.0.8 |
| Icons | FontAwesome 5.15.4 + Material Icons |
| Font | Plus Jakarta Sans (Google Fonts) |

### External Integrations
- **MercadoPago** — online payment processing and webhooks
- **WhatsApp API** — inbound chatbot + outbound notifications
- **Google OAuth** — optional login method
- **SMTP** — transactional email

---

## Repository Structure

```
SysGym/
├── admin/                  # Main application (all authenticated pages)
│   ├── clients/            # Client management (CRUD, search, attendance, credits)
│   │   └── gets/           # JSON API endpoints for client data
│   ├── caja/               # Point-of-sale / cash register
│   ├── products/           # Product and inventory management
│   ├── users/              # User accounts and role management
│   ├── config/             # System settings (WhatsApp, email, payments, branding)
│   ├── payroll/            # Employee payroll
│   ├── routines/           # Fitness routines and exercises
│   ├── plans/              # Membership plan definitions
│   ├── statistics/         # Analytics and charts
│   ├── reports/            # Exportable reports
│   ├── valoraciones/       # Client fitness assessments
│   ├── contabilidad/       # Accounting and invoicing
│   ├── login/              # Auth: login, session, password reset
│   ├── profile/            # User profile editing
│   ├── cron/               # Scheduled tasks
│   ├── support/            # Help/support pages
│   ├── system/             # System logs and diagnostics
│   ├── tools/              # Utility tools
│   ├── gets_home/          # Dashboard AJAX endpoints
│   ├── bk/                 # Database backup files (not in git for production)
│   ├── css/                # Admin-specific stylesheets
│   ├── js/                 # Admin-specific JavaScript
│   └── inc/                # Shared admin includes (header, footer, sidebar, log)
├── api/                    # Public API (e.g., external client registration)
├── pay/                    # Payment flow pages (MercadoPago redirect/webhook)
├── webhook/                # WhatsApp incoming webhook handler (chatbot)
├── whatsapp/               # WhatsApp outbound message templates and senders
├── mailer/                 # Email templates and sending functions
├── pdf/                    # PDF generation utilities
├── inc/                    # Global config and shared functions
│   └── config.php          # Database connection, constants, global functions
├── assets/                 # Static assets (CSS, JS, images)
├── img/                    # Image assets
├── vendor/                 # Composer dependencies (do not edit)
├── composer.json
├── composer.lock
├── index.php               # Root redirect → /admin/
├── update.php              # Git-pull deployment endpoint (token-protected)
└── .gitignore
```

### Module pattern
Every `admin/` module follows the same layout:
```
admin/<module>/
├── index.php          # Main page (lists, dashboard)
├── new.php            # Create form / handler
├── edit.php           # Edit form / handler
├── delete.php         # Delete handler
└── gets/              # AJAX/JSON data endpoints
    └── get_*.php
```

---

## Database

- **Name:** `activgym_app`
- **Engine:** InnoDB, `utf8mb4` charset
- **Access:** PDO singleton defined in `/inc/config.php` — always use `$pdo` (never mysqli)

### Key tables
| Table | Purpose |
|---|---|
| `clientes` | Master client records |
| `clientes_preinscritos` | Pre-registered prospects |
| `asistencias` | Attendance log (date, time, client_id) |
| `cajas` | POS/cash register sessions |
| `bolsillos` | Client wallet/pocket balances |
| `creditos` | Client credit records |
| `users` | Staff/admin accounts |
| `roles` / `permisos` | RBAC role and permission definitions |
| `planes` | Membership plan definitions |
| `productos` | Products for POS |
| `configuracion` | Key-value system settings (loaded at runtime) |

### Database conventions
- Column names: `snake_case`, mostly Spanish words
- Soft deletes: rows are flagged with a status/deleted column rather than hard-deleted
- Timestamps stored as `DATETIME` in Colombia timezone
- Foreign keys enforced; always use transactions when inserting related rows together

---

## Authentication & Authorization

### How authentication works
1. Login at `/admin/login/index.php` — validates username/password against `users` table
2. Session started; user data stored in `$_SESSION`
3. Optional "remember me" token stored as a cookie and in the DB for auto-restore
4. Every protected page includes `/admin/login/session.php` at the top — this checks the session (and restores from cookie if expired)

### Permission checking
- Every page that requires a specific permission includes `/admin/login/restriction.php`
- The variable `$permisopage` is set before including `restriction.php`
- Redirect to login or 403 if the user lacks the permission

### Adding a new protected page
```php
<?php
session_start();
require_once '../../inc/config.php';
require_once '../login/session.php';          // ensures user is logged in
$permisopage = 'clients_view';               // permission key
require_once '../login/restriction.php';      // enforces permission
// ... rest of page
```

---

## Configuration System

System-wide settings (branding, SMTP, WhatsApp, MercadoPago, etc.) are stored in the `configuracion` table as key-value pairs. They are fetched once per request inside `config.php` and made available as constants or variables.

**Do not hardcode** gym name, colors, SMTP credentials, or API keys anywhere in PHP files. Always read from the `configuracion` table or the constants defined in `config.php`.

### Sensitive files (never commit)
The following are listed in `.gitignore` — never create or commit these:
- `inc/connect.php`
- `inc/url_bd.php`
- Any `backup/` or `bk/` directories with real data

---

## Coding Conventions

### PHP
- **Style:** Procedural PHP — no classes except for Composer libraries
- **Variables:** `$camelCase` for local variables, `$snake_case` for database-mapped values
- **Database queries:** Always use PDO prepared statements with named placeholders:
  ```php
  $stmt = $pdo->prepare("SELECT * FROM clientes WHERE id = :id AND estado = 1");
  $stmt->execute([':id' => $id]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);
  ```
- **Error handling:** Wrap PDO calls in `try/catch`; return JSON error responses for API endpoints
- **JSON responses:**
  ```php
  header('Content-Type: application/json');
  echo json_encode(['success' => true, 'data' => $data]);
  exit;
  ```
- **Logging:** Use `/admin/inc/log_action.php` to record significant user actions
- **Includes:** Use `require_once` for config/session files, `include` for optional template partials

### JavaScript / jQuery
- All custom JS uses jQuery
- AJAX calls use `$.ajax()` or `$.post()` — always handle both success and error callbacks
- Use SweetAlert2 (`Swal.fire(...)`) for all user-facing alerts and confirmations; never use native `alert()`
- DataTables initialized on every list/table page — use `ajax` source pointing to a `gets/get_*.php` endpoint

### CSS
- Custom sidebar components are prefixed `sg-` (e.g., `sg-sidebar`, `sg-item`, `sg-submenu`)
- Theme colors are injected as CSS variables from PHP (system color settings)
- Mobile-first; sidebar collapses on small screens

### Filenames
- PHP files: `snake_case.php` (e.g., `get_clientes_all.php`, `register_credito.php`)
- JavaScript files: `camelCase.js` or descriptive `kebab-case.js`
- Always lowercase

---

## Key Files to Know

| File | Purpose |
|---|---|
| `/inc/config.php` | DB connection singleton, global constants, shared utility functions |
| `/admin/login/session.php` | Session validation and cookie-based auto-login |
| `/admin/login/restriction.php` | Permission enforcement middleware |
| `/admin/inc/log_action.php` | Audit log writer |
| `/webhook/index.php` | WhatsApp chatbot — state machine for multi-step conversations |
| `/pay/crear_pago.php` | Initiates MercadoPago payment |
| `/pay/webhook_mp.php` | MercadoPago payment confirmation webhook |
| `/update.php` | Token-authenticated `git pull` deployment endpoint |

---

## API Endpoint Patterns

### Internal AJAX endpoints (`gets/get_*.php`)
- Respond with JSON
- Require the user to be authenticated (session check via `session.php`)
- Return arrays of objects for DataTables: `{"data": [...]}`

### Public API (`/api/submit.php`)
- Validated with a static token passed in the request
- Used for external client registration (e.g., kiosk or landing page)

### Webhooks
- `/webhook/index.php` — WhatsApp inbound (no auth, IP-restricted at server level)
- `/pay/webhook_mp.php` — MercadoPago payment notifications (signature verified)

---

## WhatsApp Chatbot

The chatbot in `/webhook/index.php` is a **state-machine** bot. Key points:

- Conversation state is persisted in the database per phone number
- Menu has 6 main options: Plans, Hours, Consult Plan, Payments, Certificates, Advisor
- Advisor option has a queue and timeout system
- Hours of operation differ by day (weekday / Saturday / Sunday)
- Message templates are in `/whatsapp/`
- Do not add menu options without updating both the state machine and the reply messages

---

## Deployment

- **Production server:** Traditional PHP + Apache/Nginx (no Docker)
- **Deployment method:** Visit `/update.php?run=1&token=<secret>` — this runs `git pull origin main` and streams the output
- **Main branch for production:** `master`
- **Development branches:** Use feature branches; merge to `master` via pull request

### Steps to deploy a change
1. Commit and push your feature branch
2. Open a pull request to `master`
3. After merge, trigger `/update.php` on the server

---

## Development Workflow

### Adding a new admin module
1. Create `admin/<module>/` directory
2. Add `index.php`, `new.php`, `edit.php`, `delete.php` as needed
3. Create `gets/` subdirectory with AJAX data endpoints
4. Add sidebar link in `admin/inc/sidebar.php` (with permission check)
5. Register any new permissions in the `permisos` table

### Adding a new database column
1. Write the `ALTER TABLE` statement
2. Update all `SELECT *` queries in the module to explicitly list columns if needed
3. Update any export/PDF templates that reference the table

### Modifying system configuration
- Add the new key to the `configuracion` table
- Read it in `config.php` and define a constant or variable
- Add the UI input to `admin/config/index.php` under the appropriate tab

---

## Common Pitfalls

- **Never use `mysqli_*` functions** — the entire codebase uses PDO
- **Never hardcode timezone** as anything other than `America/Bogota` without discussing with the team
- **Do not skip session/permission checks** on any new protected page
- **Do not use `echo` for JSON output without setting the `Content-Type` header** first
- **Bootstrap version mixing:** Most pages use Bootstrap 5; a few legacy pages still use Bootstrap 4. Do not mix versions on the same page
- **The `configuracion` table is the source of truth** for all credentials and settings — do not duplicate values in PHP constants
- **Soft deletes:** Most tables use a status flag; never use `DELETE` for client or financial records

---

## No Formal Test Suite

There is no PHPUnit or automated test framework. The following test files are manual/hardware integration tests:

- `/test_cam.php` — camera/facial recognition test
- `/test_torniquete.php` — hardware turnstile test via Web Serial API
- `/admin/date_test.php` — date logic scratch file

When making changes, manually test the affected module pages and verify AJAX endpoints return correct JSON before committing.
