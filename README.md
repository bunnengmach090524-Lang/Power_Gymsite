# GymSite

**English:** A multi-tenant SaaS platform to manage gyms — **one platform, many gyms**. Each gym (tenant) gets its own public website and its own admin dashboard; members get a self-service account area on the public site (profile, QR, subscription, bookings) and can buy memberships online via **Bakong KHQR**. Members can also book/purchase individual classes (free or paid).

**ភាសាខ្មែរ:** ជាវេទិកា SaaS សម្រាប់គ្រប់គ្រងកន្លែងហាត់ប្រាណ — **ផ្លាតហ្វ័រមួយ ហាត់ប្រាណច្រើនកន្លែង**។ កន្លែងហាត់ប្រាណនីមួយៗ (tenant) មានគេហទំព័រសាធារណៈផ្ទាល់ខ្លួន និងផ្ទាំងគ្រប់គ្រង (Admin Dashboard) ផ្ទាល់ខ្លួន។ សមាជិកអាចចូលគណនីផ្ទាល់ខ្លួននៅលើគេហទំព័រ (ទម្រង់ព័ត៌មាន, QR, សមាជិកភាព, ការកក់) និងទិញសមាជិកភាពតាមអនឡាញតាមរយៈ **Bakong KHQR**។ សមាជិកក៏អាចកក់/ទិញថ្នាក់ហាត់ប្រាណម្តងមួយៗបានដែរ (ឥតគិតថ្លៃ ឬបង់ថ្លៃ)។

Reference/inspiration: [GoGym Cambodia](https://gogymcambodia.com/)

A gym is identified publicly as `http://localhost/gym/{slug}`.
កន្លែងហាត់ប្រាណត្រូវបានកំណត់អត្តសញ្ញាណជាសាធារណៈតាម `http://localhost/gym/{slug}`។

---

## Tech stack / បច្ចេកវិទ្យា

- **Backend / ផ្នែក Backend:** Laravel 13, PHP ^8.3, MySQL (local: `gymsite_db`)
- **Frontend / ផ្នែក Frontend:** Vue 3 + Inertia.js (`@inertiajs/vue3` v3)
- **Build / ការកសាង:** Vite 8 (`laravel-vite-plugin` + `resolvePageComponent`), Tailwind CSS v4
- **Charts / ក្រាហ្វ:** chart.js + vue-chartjs
- **QR codes / កូដ QR:** `endroid/qr-code` (PNG for Telegram), `simplesoftwareio/simple-qrcode` (SVG), `html5-qrcode` (browser scanning), `qrcode` (npm, client-side payment QR)
- **Google OAuth / ការចូលតាម Google:** `laravel/socialite` + emailed OTP step
- **Bakong KHQR / ការបង់ប្រាក់ Bakong:** `fidele007/bakong-khqr-php` (per-tenant QR + MD5 transaction verification)
- **Telegram bot / បូត Telegram:** raw HTTP API via `App\Services\TelegramService`
- **Media storage / ការផ្ទុករូបភាព:** local `public` disk or **Cloudflare R2** (`MEDIA_DISK` env)

---

## Setup / របៀបដំឡើង

### Prerequisites / អ្វីដែលត្រូវការ
- PHP ^8.3, Composer, Node.js (npm), MySQL
- [Laragon](https://laragon.org/) (recommended for local dev on Windows — ត្រូវបានណែនាំសម្រាប់ការអភិវឌ្ឍក្នុងតំបន់លើ Windows)

### One-shot setup / ការដំឡើងម្តងៗ
```bash
composer setup
```
This runs: `composer install` → create `.env` → `key:generate` → `migrate --force` → `npm install` → `npm run build`.
<br>*ដំណើរការ: ដំឡើងទាំងអស់ក្នុងមួយបញ្ជារ។*

### Manual setup / ការដំឡើងដោយដៃ
```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
npm run build
```

---

## Environment variables

Copy `.env.example` to `.env` and fill in:

| Variable | Purpose |
|---|---|
| `DB_CONNECTION` / `DB_DATABASE` | Database (MySQL; `gymsite_db` locally) |
| `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` / `GOOGLE_CLIENT_REDIRECT_URI` | Google OAuth login/register |
| `TELEGRAM_BOT_TOKEN` / `TELEGRAM_BOT_USERNAME` / `TELEGRAM_WEBHOOK_SECRET` | Telegram bot linking + QR delivery |
| `MEDIA_DISK` | `public` (local) or `r2` (Cloudflare R2) |
| `R2_*` | Cloudflare R2 credentials (when `MEDIA_DISK=r2`) |

> ⚠️ These keys are used by the code but are **not** present in `.env.example` yet.

---

## Development workflow / របៀបអភិវឌ្ឍ

```bash
composer dev        # concurrently: artisan serve + queue:listen + pail(logs) + vite
npm run dev         # or: npm run build
composer test       # config:clear + phpunit
```

- **Scheduler / កម្មវិធីកំណត់ពេល:** `notifications:membership-expiring` daily at 08:00 (add to cron / `php artisan schedule:work`).
- **Queue worker:** `php artisan queue:listen` (jobs table driver).
- **Integrity audit for member accounts / ពិនិត្យភាពត្រឹមត្រូវនៃគណនីសមាជិក:** `php artisan members:check-integrity [--fix] [--create-missing]`
- **Set the Telegram webhook once / កំណត់ Telegram webhook ម្តង:** `php artisan telegram:set-webhook {public-base-url}`
- Local Telegram/KHQR testing needs a public URL (Expose/tunnel).
  *ការធ្វើតេស្ត Telegram/KHQR ក្នុងតំបន់ត្រូវការ URL សាធារណៈ។*

---

## Roles / តួនាទី

| Role / តួនាទី | Scope / វិសាលភាព | Access / ការចូលប្រើ |
|---|---|---|
| `super_admin` | Platform-wide / វេទិកាទាំងមូល | `/super-admin` tenant management |
| `gym_admin` | Per-tenant / ក្នុងកន្លែងហាត់ប្រាណ | Full dashboard — ផ្ទាំងគ្រប់គ្រងពេញ (website, media, team, promotions, plans, payments, reports, trainers) |
| `manager` | Per-tenant / ក្នុងកន្លែងហាត់ប្រាណ | Staff management, staff attendance, salary + salary report — គ្រប់គ្រងបុគ្គលិក, វត្តមាន, ប្រាក់ខែ + របាយការណ៍ |
| `staff` | Per-tenant / ក្នុងកន្លែងហាត់ប្រាណ | Members, classes, roster, check-in, trainer-attendance, notifications, quick search, self-service |
| `trainer` | Per-tenant / ក្នុងកន្លែងហាត់ប្រាណ | Staff-capable login created via "Invite to Login" — គណនីចូលតាមការអញ្ជើញ |
| `member` | Public consumer / អ្នកប្រើសាធារណៈ | `/gym/{slug}/account` self-service portal |

---

## Key features / មុខងារសំខាន់ៗ

- **Public gym site / គេហទំព័រសាធារណៈ** — home, pricing, trainers (+detail), classes (+detail + free/paid checkout), gallery, contact, member login/register
  — ទំព័រដើម, តម្លៃ, គ្រូបង្វឹក (+ព័ត៌មានលម្អិត), ថ្នាក់ (+លម្អិត +ការទិញ/កក់), វិចិត្រសាល, ទំនាក់ទំនង, ការចូល/ចុះឈ្មោះសមាជិក
- **Member self-service / សេវាកម្មខ្លួនឯងរបស់សមាជិក** — profile edit with photo upload, personal QR, subscription/check-ins/bookings, class self-enrollment
  — កែទម្រង់ព័ត៌មាន អាចផ្ទុករូបថត, QR ផ្ទាល់ខ្លួន, សមាជិកភាព/ការចូលហាត់/ការកក់, ការចុះឈ្មោះចូលថ្នាក់ដោយខ្លួនឯង
- **Membership purchase via Bakong KHQR / ការទិញសមាជិកភាពតាម Bakong KHQR** with API auto-verification — ជាមួយការផ្ទៀងផ្ទាត់ដោយស្វ័យប្រវត្តិតាម API
- **Paid classes e-commerce / ការទិញថ្នាក់ដែលបង់ថ្លៃ** — client-side cart (`useCart`), KHQR checkout, dev simulation endpoint
  — កន្ត្រកទិញឥវ៉ាន់, ការបង់ប្រាក់ KHQR, ចំណុចធ្វើតេស្តការទូទាត់
- **Check-in system / ប្រព័ន្ធចូលហាត់** — manual search + QR scan, membership gating, daily dedupe, trends
  — ស្វែងរក + ស្កេន QR, ពិនិត្យសមាជិកភាព, ការពារការចូលច្រើនដងក្នុងមួយថ្ងៃ, ស្ថិតិ
- **Trainers CRUD, trainer attendance / គ្រូបង្វឹក + វត្តមានគ្រូ** (scan/toggle/export, late detection), Telegram bot linking + QR delivery
- **Class attendance tracking / ការតាមដានវត្តមានថ្នាក់** — per-class roster per date, present/absent/permission
  — បញ្ជីសមាជិកក្នុងថ្នាក់តាមកាលបរិច្ឆេទ, សម្គាល់មាន/អវត្តមាន/អនុញ្ញាត
- **Staff management & salary module / ការគ្រប់គ្រងបុគ្គលិក + ប្រាក់ខែ** — `StaffProfile`, staff attendance, salary calculation/report
  — ទម្រង់បុគ្គលិក, វត្តមានបុគ្គលិក, ការគណនា/របាយការណ៍ប្រាក់ខែ
- **Payment refunds / ការសងប្រាក់វិញ** (manual, admin-only) with automatic class un-enrollment — ដោយដៃ (សម្រាប់ Admin) និងដកសមាជិកចេញពីថ្នាក់ដោយស្វ័យប្រវត្តិ
- **Google OAuth login/register / ការចូល/ចុះឈ្មោះតាម Google** + emailed OTP verification
- **Multi-language UI / ភាសាច្រើន** (Khmer default / English — ខ្មែរលំនាំដើម / អង់គ្លេស), theme (light/dark), notifications (admin + member-scoped — ការជូនដំណឹងសម្រាប់ Admin និងសមាជិក)

---

## Project structure / រចនាសម្ព័ន្ធគម្រោង

```
app/
  Console/Commands/      artisan commands (notifications, webhook, member integrity)
  Http/Controllers/      Auth / Admin / Public / SuperAdmin / root-level controllers
  Http/Middleware/       tenant.identify, tenant.member, role, superadmin, staff.access
  Mail/                  TeamInviteMail, GoogleLoginOtpMail
  Models/                Eloquent models + Scopes/TenantScope
  Policies/              Gates (Member, GymClass, Promotion, MediaImage, Payment)
  Services/              Telegram, BakongKHQR, SalaryCalculation
  Support/MediaUrl.php   central media file helper
resources/js/
  Pages/                 Admin / Auth / Client / Staff Vue pages
  Components/            SiteHeader, SiteFooter, CartDrawer
  composables/           useLang, useTheme, useSidebar, useCountUp, useCart
  lang/                  en.js, km.js
routes/web.php           all web routes
database/migrations/     schema
```

---

## Contributing / ការចូលរួមអភិវឌ្ឍ

This is a private project. If you'd like to contribute, open an issue or pull request describing your change.
*នេះជាគម្រោងឯកជន។ ប្រសិនបើអ្នកចង់ចូលរួមអភិវឌ្ឍ សូមបង្កើត issue ឬ pull request ដែលពិពណ៌នាពីការផ្លាស់ប្តូររបស់អ្នក។*

## License / អាជ្ញាប័ណ្ណ

Proprietary — internal use.
*ជាកម្មសិទ្ធិ — សម្រាប់ប្រើប្រាស់ផ្ទៃក្នុង។*
