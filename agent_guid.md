# GymSite — Agent Guide

> Guide for AI agents (Claude, Cursor, opencode, etc.) to understand this project quickly. Last full re-read of the folder structure: **Sep 1, 2026** (previous version: Aug 26, 2026).

## 1. What is this project

**GymSite** — a multi-tenant SaaS platform to manage gyms ("one platform, many gyms"). Each gym (tenant) gets its own public website and its own admin dashboard; members get a self-service account area on the public site (profile, QR, subscription, bookings) and can buy memberships online via Bakong KHQR. Members can also book/purchase individual classes (free or paid). Reference/inspiration: https://gogymcambodia.com/

Example of how a gym is identified publicly: `http://localhost/gym/{slug}`.

## 2. Tech stack

- **Backend:** Laravel (v13), PHP ^8.3, SQLite locally / MySQL in `.env` (`gymsite_db`)
- **Frontend:** Vue 3 + Inertia.js (`@inertiajs/vue3` v3)
- **Build:** Vite 8 (`laravel-vite-plugin` + `resolvePageComponent`), Tailwind CSS v4 (`@tailwindcss/vite`)
- **Charts:** chart.js + vue-chartjs (used in `Admin/Overview.vue`)
- **QR codes:** `endroid/qr-code` (PNG bytes for Telegram photos, webhook controller) + `simplesoftwareio/simple-qrcode` (SVG responses, e.g. `trainers/{id}/qr`, member account QR); browser scanning via `html5-qrcode`; client-side QR rendering via npm `qrcode` (payment pending pages)
- **Google OAuth:** `laravel/socialite` ^5.30 — login/register with Google, followed by an emailed OTP step before the session is created
- **Bakong KHQR:** `fidele007/bakong-khqr-php` ^1.2 — per-tenant KHQR generation (`IndividualInfo`) + transaction verification by MD5 via Bakong API
- **Telegram bot:** raw HTTP API via `App\Services\TelegramService` (no SDK); config in `config/services.php` → `TELEGRAM_BOT_TOKEN`, `TELEGRAM_BOT_USERNAME`, `TELEGRAM_WEBHOOK_SECRET`
- **Media storage:** `App\Support\MediaUrl` helper → resolves/deletes files; supports local `public` disk or **Cloudflare R2** (S3-compatible) via `MEDIA_DISK` env
- **Local env:** Laragon, path `F:/Bunnengfile/laragon/www/gymsite` (Linux mount: `/mnt/f/Bunnengfile/laragon/www/gymsite`)
- **DB:** MySQL (`.env`) — `DB_DATABASE=gymsite_db`, root, local dev
- **Mail:** `log` driver; queue `database`; scheduled jobs run via `php artisan queue:listen`

⚠️ Real `.env` contains `GOOGLE_CLIENT_ID/SECRET/REDIRECT_URI`, all three Telegram keys, and optional `R2_*` keys — **none of them are in `.env.example` yet**.

## 3. Multi-tenancy & roles

### Middleware aliases (registered in `bootstrap/app.php`)
| Alias | Class | Purpose |
|---|---|---|
| `tenant.identify` | `IdentifyTenant.php` | Resolves tenant from route `{slug}`, 404 if missing, 403 if `subscription_status = expired`, shares `currentTenant` |
| `tenant.member` | `EnsureUserBelongsToTenant.php` | Blocks users without `tenant_id`; redirects to `billing.expired` if tenant expired |
| `role:gym_admin,staff` | `EnsureUserHasRole.php` | Role-gate (variadic roles) |
| `superadmin` | `EnsureUserIsSuperAdmin.php` | Protects SuperAdmin routes |
| `staff.access` | `EnsureStaffAccess.php` | Allows a logged-in user OR a trainer with a valid `trainer_staff_id` session (trainer magic-link). Used for the `/my/*` staff self-service area. |

CSRF is also exempted globally for `telegram/webhook/*` in `bootstrap/app.php` (`validateCsrfTokens(except:)`).

### Global scoping
`app/Models/Scopes/TenantScope.php` — applied to tenant-scoped models (incl. `CheckIn`, `TrainerAttendance`, `ClassOrder`, `StaffProfile`, `StaffAttendance`, `SalaryPayment`). Filters by `auth()->user()->tenant_id` automatically. Use `withoutGlobalScopes()` when you must bypass (e.g. Telegram webhook linking trainers/members by token, notification dedupe, **member self-service lookup by `user_id`**, trainer self-service token lookup).

### Roles (5 levels)
1. **super_admin** — `tenant_id = null`, platform-wide. Controllers in `app/Http/Controllers/SuperAdmin/` (`DashboardController`, `TenantController`). Routes under `/super-admin`.
2. **gym_admin** — per-tenant, full dashboard access.
3. **manager** — per-tenant (NEW Aug 27). Mid-tier role between admin and staff. Currently gates only the **staff management / salary / staff-attendance** block (`role:gym_admin,manager`). Cannot manage trainers, website, team/roles, promotions, payments, or reports (those remain `gym_admin`-only).
4. **staff** — per-tenant subset (members, classes, class roster, check-in, trainer-attendance, notifications, quick search, and the self-service area). Routes under `/dashboard`.
   - Trainer *management* (CRUD + QR + Telegram connect/resend) lives only in the `role:gym_admin` group. Staff can still do trainer check-in/out (`trainer-attendance.*`).
5. **member** — public-site consumer. Auth Users with role `member` have `tenant_id = null`; their gym link lives on the **Member row** (`members.user_id`). They never enter `/dashboard`; their area is `/gym/{slug}/account` (auth + `tenant.identify`).

There is also a role enum value **`trainer`** (NEW Aug 29). A `trainer`-role **User** is created when an admin converts a trainer-backed StaffProfile into a real login account via the **"Invite to Login"** flow (`StaffController::inviteToLogin`). These users belong to `tenant_id` (not null) and act as staff-capable accounts. Note: `trainer` here is a user *role* — distinct from the `Trainer` *model* (which may or may not have an associated login User).

Users carry `is_owner` (bool), `invitation_accepted_at`, and `google_id` (nullable unique). **Password is nullable** since Aug 21 — Google-only accounts have no password.

**Staff self-service access (`/my/*`)**: two entry paths share the same `Staff/SelfService.vue` page:
- A normal authenticated `staff`/`manager`/`trainer`-role user whose `StaffProfile.payable_type = user` and `payable_id = user.id`.
- A trainer viewing via a **magic link**: admin generates a `self_service_token` QR (`staff.selfServiceQr`), trainer scans → `TrainerSelfLoginController::login` stores `trainer_staff_id` in session (no `Auth::login()`, so `auth.user` is null) → `EnsureStaffAccess` allows the trainer-linked `StaffProfile` (`payable_type = trainer`).

## 4. Directory structure

### Backend (`app/`)
```
app/
  Console/Commands/
    NotifyExpiringMemberships.php   → artisan notifications:membership-expiring (scheduled daily 08:00)
    SetTelegramWebhook.php          → artisan telegram:set-webhook {url} (sets + getWebhookInfo)
    CheckMemberTenantIntegrity.php  → artisan members:check-integrity [--fix] [--create-missing]
                                     (audits/re-pairs User.tenant_id vs Member.tenant_id/user_id)
  Http/
    Controllers/
      Auth/    AuthenticatedSessionController, RegisteredUserController,
               GoogleAuthController (OAuth + OTP flow), TenantSetupController (setup-gym),
               InvitationController, NewPasswordController, PasswordResetLinkController
      Admin/   Overview, Member (+show page, qrCode/connectTelegram/resendTelegramQr, bookClass/unbookClass),
               Class (+price field), ClassAttendance (roster/mark),
               CheckIn, Trainer (CRUD+QR+Telegram), TrainerAttendance, Payment (index/create/store +
               refund), Promotion, Report, Team, Notification, Profile, WebsiteSettings, MediaImage,
               QuickSearch, MembershipPlan (plans CRUD), Staff (profiles + QR + Telegram + self-service
               QR + invite-to-login), StaffAttendance (index/scan/export), Salary (calculate/store/
               markPaid), SalaryReport (index/export)
      Public/  GymSiteController (home/pricing/trainers/trainerShow/classes/classShow/gallery/contact/
               inquiry/login — per-class & per-trainer detail pages),
               MemberAccountController (member profile/QR/bookings, bookClass/unbookClass,
               notification read/read-all),
               PlanPurchaseController (KHQR initiate + status polling),
               ClassCheckoutController (paid class cart checkout + KHQR + polling + simulate),
               MemberRegistrationController (public per-gym member signup)
      SuperAdmin/ DashboardController, TenantController
      Root-level: Controller, StaffSelfServiceController (/my/staff self-service + update + qr),
                  TrainerSelfLoginController (magic-link token login), TelegramWebhookController
    Middleware/ IdentifyTenant, EnsureUserBelongsToTenant, EnsureUserHasRole, EnsureUserIsSuperAdmin,
               EnsureStaffAccess, HandleInertiaRequests
  Listeners/LogMemberAuthActivity.php    (imported BUT not registered — currently dead code)
  Mail/TeamInviteMail.php, GoogleLoginOtpMail.php   → views emails/team-invite.blade.php, google-login-otp.blade.php
  Models/  (see schema below) + Models/Scopes/TenantScope.php
  Policies/ GymClassPolicy, MediaImagePolicy, MemberPolicy, PaymentPolicy, PromotionPolicy
  Providers/AppServiceProvider.php
  Services/ TelegramService.php            → sendMessage / sendPhoto(PNG) / setWebhook
            BakongPaymentService.php       → generateQr / verifyTransaction / isAutoVerifyEnabled
            SalaryCalculationService.php   → calculate() preview (base + commission + hours)
  Support/MediaUrl.php                     → resolve() / delete() / disk() — central media file helper
```
⚠️ NOTE: There is an **orphaned duplicate** `app/Services/StaffSelfServiceController.php` (an older, 49-line version). The real, current one is `app/Http/Controllers/StaffSelfServiceController.php`. The orphan is safe to delete.

### Frontend (`resources/js/`)
```
resources/js/
  Components/ SiteHeader.vue (public site nav + member menu w/ today's classes),
              SiteFooter.vue,
              CartDrawer.vue (slide-over cart for paid classes checkout)
  Layouts/   AdminLayout.vue (sidebar + notifications + user menu),
             ClientLayout.vue (public site shell + floating cart button + CartDrawer)
  Pages/
    Admin/    Overview, Members/{Index,Show,Create,Edit}, Classes/{Index,Create,Edit,Roster},
              Payments/{Index,Create}, Promotions/{Index,Create,Edit}, Reports/Index, Team/Index,
              Profile/Edit, WebsiteEditor, Notification/Notification,
              CheckIn/{Index,Scan}, Trainers/{Index,Create,Edit,Show}, TrainerAttendance/{Index,Scan},
              Plans/{Index,Create,Edit}, Staff/{Index,Edit}, StaffAttendance/{Index,Scan},
              Salary/{Index,Report}
    Auth/     Login, Register, AcceptInvite, ForgotPassword, ResetPassword,
              SetupGym.vue (gym-name step for Google-registered owners),
              VerifyGoogleCode.vue (6-digit OTP entry)
    Client/   GymHome, GymPricing, GymTrainers, GymTrainerDetail, GymClasses, GymClassDetail,
              GymGallery, GymContact, MemberLogin, MemberRegister,
              GymMemberAccount.vue (member self-service + profile edit + class enrollment),
              PaymentPending.vue (membership KHQR + polling),
              ClassPaymentPending.vue (paid class KHQR + polling + simulate)
    Staff/    SelfService.vue (staff self-service portal — view profile, attendance, salary, edit own info, QR)
  composables/ useLang.js, useTheme.js, useSidebar.js, useCountUp.js, useCart.js
  directives/  reveal.js (v-reveal), clickOutside.js (v-click-outside) — registered globally in app.js
  lang/        en.js, km.js (default = km; persisted in localStorage `gymsite_lang`)
  app.js       Inertia bootstrap
resources/css/app.css   (Tailwind v4)
resources/views/app.blade.php  (root view; lang="km")
```

### Routes (`routes/web.php`)
- `GET /` → redirects to dashboard or login.
- `GET /gym/{slug}` group (`tenant.identify`) → public pages: home, pricing, **trainers + trainer detail**, **classes + class detail**, **gallery**, contact, inquiries (inquiry form creates a `Member` row directly + `new_inquiry` notification), and `GET /login` renders `Client/MemberLogin`.
- Guest: `register`, `login` (throttled 5/min), Google OAuth group: `auth/google/redirect|callback|verify(GET+POST)|resend` (resend throttled 3/min), password reset flow (`forgot-password`, `reset-password`).
- Signed: `GET/POST invite/{user}/accept` (team invites)
- Auth (no tenant required): `GET/POST setup-gym` — one-time gym-name step for owners who registered via Google
- `/super-admin` (`superadmin`): dashboard, tenant index/show, suspend/activate/destroy
- `/dashboard` (`tenant.member`): overview, profile, then:
  - `role:gym_admin,staff`: members CRUD (**incl. Show**), classes CRUD (+roster/mark attendance), class booking management on member show, check-in (index/search/store/destroy + scan page/store), members QR + Telegram connect/resend, trainer-attendance (index/check-in/check-out/destroy/scan/toggle/export), notifications, quick search (`GET dashboard/search?q=`)
  - `role:gym_admin`: website-editor, media, team, promotions, **plans (resource, no show)**, payments (+create/store + **refund**), reports (+export), trainers resource + QR + Telegram connect/resend
  - `role:gym_admin,manager` (**NEW Aug 27**): staff (index/store/edit/update/destroy + qr + connect/resend + **self-service-qr** + **invite-login**), staff-attendance (index/scan/toggle/export/destroy), salary (index/calculate/store/mark-paid/destroy), salary-report (index/export)
- Member self-service (`tenant.identify` + `auth`, prefix `gym/{slug}`):
  - `GET/POST /account` (show/update profile), `GET /account/qr` (SVG QR)
  - `POST /account/classes/{class}/book` (member self-enroll), `DELETE /account/classes/{class}/unbook` (cancel)
  - `PATCH /account/notifications/{notification}/read`, `PATCH /account/notifications/read-all` — member-scoped notification read (scoped to `member_id`, 403 otherwise)
  - `GET /plans/{plan}/purchase` → generates KHQR, creates `SubscriptionPaymentRequest`, renders `PaymentPending`
  - `GET /payment-requests/{paymentRequest}/status` → JSON polling endpoint (auto-verifies via Bakong API when enabled)
  - `POST /classes/checkout` → initiates paid class cart checkout, generates KHQR, creates `ClassOrder`
  - `GET /class-orders/{order}/status` → JSON polling endpoint for class order status
  - `POST /class-orders/{order}/simulate` → dev-only simulation endpoint (blocked if tenant has real Bakong token)
- Guest per-gym member registration (`tenant.identify` + `guest`, prefix `gym/{slug}`):
  - `GET/POST /register` → public member registration form (throttled 5/min); `GET /login` → `Client/MemberLogin`
- Trainer self-login: `GET /my/trainer-login/{token}` (`TrainerSelfLoginController`) — magic-link login, stores `trainer_staff_id` in session, redirects to `my.staff.index`. No `Auth::login()`, so `auth.user` is null on these sessions.
- Staff self-service (`staff.access`, prefix `/my`): `GET/POST /my/staff` (view/update own name/phone/photo), `GET /my/staff/qr` (own check-in QR).
- Outside all middleware: `POST telegram/webhook/{secret}` (secret compared with `hash_equals`; CSRF-exempt).

**Route naming:** `public.*`, `dashboard.*`, `superadmin.*`, `invite.*`, `google.*`, `member.account*`, `plan.purchase`, `payment.status`, `class.checkout.*`, `member.register*`, `classes.roster.*`, `members.classes.*`, `telegram.webhook`, `trainer.self.login`, `my.*`.

⚠️ Known wart (still present): `Route::resource('members', ...)` is registered **twice** inside the staff group. Harmless so far but should be deduped.

## 5. Google auth + OTP flow (`GoogleAuthController`)

1. `redirect()` stashes optional session state: `google_auth_tenant_slug` (`?tenant=slug` — member joining a specific gym), `google_auth_intent=register` (owner registration from Register page), and `url.intended` (`?redirect_to=` — post-login destination, reused by Laravel's `redirect()->intended()`).
2. `callback()` finds user by `google_id` OR `email`; backfills `google_id` if missing. New users:
   - With `?tenant=` → creates User (`role=member`, no password, `email_verified_at`) + matching `Member` row (`firstOrCreate` by tenant+email, then force-fills `user_id`).
   - With `intent=register` → creates User (`role=gym_admin`, `tenant_id=null`, no password).
   - Neither + no match → back to login with Khmer error.
3. **Nobody is logged in immediately** — `startOtpVerification()` generates a 6-digit code (`random_int`), stores `otp_code` + `otp_expires_at` (10 min) on the user, emails `GoogleLoginOtpMail`, stashes `google_otp_user_id` + `google_otp_tenant_slug` in session, redirects to `google.verify`.
4. `verify()` compares with `hash_equals`, clears OTP fields, `Auth::login($user, true)` + session regenerate, then `redirectAfterLogin()`: owner without tenant → `setup-gym`; member → `member.account` for their gym; everyone else → dashboard.
5. `resend()` regenerates and re-emails (throttled 3/min). Verify page shows a masked email (`maskEmail()`).

`TenantSetupController` (`setup-gym`) validates `gym_name`, creates Tenant with slug = `Str::slug(name).'.'.Str::random(4)` and `subscription_status='trial'`, attaches to user. Guard-bounces anyone who already has a tenant.

## 6. Shared Inertia props (`HandleInertiaRequests.php`)

- `auth.user` → `{ id, name, email, role, avatar, member_tenant_slug }` — `avatar` is the member's `photo_url` for members (falls back to `users.avatar` for staff/admins); `member_tenant_slug` lets client components know "this viewer belongs to *this* gym"
- `flash` → `success`, `warning`, `telegramLink`, `checkedInMember` (name/photo/status/daysLeft after check-in)
- `sidebarCounts` → members, today's classes, payments this month, active promotions, today's check-ins
- `notifications` → latest 10 for tenant; `unreadNotificationsCount`
- `todayStats` → newMembers today, revenue today
- `upcomingExpiring` → ≤5 active subscriptions ending within 7 days, excluding paused (`whereNull('paused_from')`)
- `todayClasses` → up to 6 classes scheduled today
- `memberTodayClasses` → for role `member`: their class bookings scheduled today (name/times), surfaced in `SiteHeader`
- `memberNotifications` → (NEW) for role `member`: latest 10 notifications scoped to their `member_id` (member's own bell; read/read-all via `member.account.notifications.*`)
- `tenantBranding` → `{ name, logoUrl, publicUrl, subscriptionStatus }`
- `currentTenant` → `{ name, slug }` (set by `IdentifyTenant`)

All tenant-scoped props return empty/zero when there is no `tenant_id`.

## 7. Database schema

Tables (migrations in `database/migrations/`): users, cache, jobs, subscription_plans, tenants, website_settings, media_images, membership_plans, promotions, members, member_subscriptions, trainers, classes, class_bookings, payments, notifications, check_ins, trainer_attendances, subscription_payment_requests, class_attendances, class_orders, class_order_items, **staff_profiles, staff_attendances, salary_payments**.

Noteable columns:
- `users`: `tenant_id` (nullable FK), role enum(**(NEW Aug 27 & 29)** `super_admin, gym_admin, **manager**, **trainer**, `staff, member`), avatar, is_owner, invitation_accepted_at, **google_id (nullable unique)**, **otp_code (string 6, nullable)**, **otp_expires_at**; **password now nullable**
- `tenants`: name, slug, custom_domain, contact info, lat/lng, subscription_plan_id, subscription_status, **bakong_account_id, bakong_merchant_name, bakong_merchant_city, bakong_api_token (text)** — ⚠️ bakong fields are NOT in `Tenant::$fillable`; currently set directly in DB/tinker (no settings UI yet)
- `notifications`: tenant_id, type, title, message, link, read_at, **member_id (nullable, NEW Aug 26)** — `unread()` scope exists. Member-scoped notifications (e.g. class booking confirmation) carry `member_id`; admin notifications leave it null
- `classes`: schedule_day uses 3-letter day abbrs (`mon`…`sun`); `GymClass` has an appended accessor `spots_left` = capacity − booking count; **`price` (decimal 8,2 nullable)** — null/0 = free, >0 = paid add-on class; **`image_url` (NEW Aug 30)**
- `members`: `user_id` (nullable **unique** FK → users; one login per member), qr_token (auto on create), telegram_chat_id, telegram_link_token
- `trainers`: shift_start_time, qr_token, telegram_chat_id, telegram_link_token, **self_service_token (NEW Aug 28**, nullable, for trainer staff magic-link), **email (NEW Aug 29**, for auto-filling the invite-to-login modal)
- `check_ins`: tenant_id, member_id, checked_in_by, checked_in_at — one per member/day enforced in controller logic (not DB unique)
- `trainer_attendances`: tenant_id, trainer_id, checked_in_at, checked_out_at, recorded_by
- `subscription_payment_requests`: tenant_id, member_id, membership_plan_id, promotion_id (nullable), amount decimal(8,2), khqr_md5 (nullable), khqr_qr_string (text), status enum(`pending`,`verified`,`rejected`,`expired`), verified_method enum(`bakong_api`,`manual_admin`,nullable), approved_by (FK users, nullable), receipt_path (nullable), verified_at
- **`class_attendances` (NEW Aug 24):** `class_booking_id` (FK, cascade), `occurred_on` (date), `status` enum(pending, present, absent, permission), `note` (nullable), `marked_by` (FK users), `marked_at` — unique constraint on `(class_booking_id, occurred_on)`
- **`class_orders` (NEW Aug 25):** tenant_id, member_id, `total_amount` decimal(8,2), `status` enum(pending, verified, rejected, expired, **refunded — NEW Aug 26**), `khqr_qr_string` (text), `khqr_md5`, `verified_method` enum(bakong_api, manual_admin, simulation), `approved_by` (FK), `created_by` (FK), `verified_at`
- **`class_order_items` (NEW Aug 25):** `class_order_id` (FK, cascade), `class_id` (FK), `price` decimal(8,2) — price snapshot at order time
- `member_subscriptions` already had `paused_from` / `paused_until` date columns — referenced in `upcomingExpiring` filter but **no pause UI/controller exists yet**
- `payments.method` enum now includes **`simulation`** value (for dev/testing without real Bakong). **Refund columns (NEW Aug 26):** `refunded_at`, `refunded_by` (FK users), `refund_note` — set by the admin refund action
- **`staff_profiles` (NEW Aug 27):** tenant_id, `payable_type` (`user`|`trainer`), `payable_id` (morph-style plain pair — deliberately NOT Eloquent polymorphic), position, phone (NEW Aug 29), salary_type enum(`fixed`,`hourly`,`commission`,`fixed_commission`), base_salary, hourly_rate, commission_percent, commission_source enum(`pt_session`,`class_booking`,`payment_referred`), hire_date, active, qr_token (NEW Aug 27), telegram_chat_id, telegram_link_token (NEW Aug 28). Display name/photo are derived accessors (`name`, `photo_url`) that resolve the underlying payee. A StaffProfile links exactly one User (role staff/manager/trainer) **or** one Trainer.
- **`staff_attendances` (NEW Aug 27):** tenant_id, staff_profile_id, checked_in_at, checked_out_at, recorded_by
- **`salary_payments` (NEW Aug 27):** tenant_id, staff_profile_id, period_start, period_end (dates), base_amount, bonus, deduction, total (decimals), status enum(`pending`,`paid`), paid_at, paid_by (FK users)

## 8. Domain logic worth knowing

### Membership & check-in (unchanged core)
- **Membership status helper** (`CheckInController::membershipStatus`): `active` (>7 days left) / `expiring` (≤7 days) / `expired` (past end_date → check-in blocked) / `none`. Same thresholds reused in search results and flash warnings.
- **Trainer lateness**: `is_late` = checked in later than `shift_start_time` + 15 min grace (`GRACE_MINUTES` const).
- **QR flow**: QR content is the raw `qr_token` string. Scan endpoints look it up scoped by tenant (members) or globally (trainers, tenant from `$request->user()`).
- **Telegram linking**: admin generates `telegram_link_token` → deep link `https://t.me/{bot}?start={token}` → webhook `/start` matches token on `Trainer`/`Member` (`withoutGlobalScopes`), stores `telegram_chat_id`, clears token, sends QR as PNG photo (endroid + PngWriter; SVG won't render as Telegram photo). Webhook secret path segment + `hash_equals`; route outside auth/CSRF. Set webhook once via `php artisan telegram:set-webhook {public-url}`.

### Member self-service (new Aug 21–22, expanded Aug 25)
- Members authenticate normally but land on `/gym/{slug}/account`. Lookup pattern everywhere: `Member::withoutGlobalScopes()->where('user_id',$user->id)->where('tenant_id',$tenant->id)->firstOrFail()` + `abort_unless($user->role === 'member', 403)`.
- Account shows active subscription (with plan), last 10 check-ins, upcoming class-bookings computed as next occurrence from weekly `schedule_day` (day-map mon→Carbon::MONDAY etc.), plus profile edit (photo upload replaces old file under `storage/app/public/avatars`) and personal QR (SVG, token lazily generated if missing).
- **NEW Aug 25:** Profile edit card with photo upload, name, phone fields (email read-only).
- **NEW Aug 25:** Class self-enrollment card — member can browse all available classes and book/unbook directly from account page.

### Class attendance tracking (NEW Aug 24)
1. Admin/staff navigates to `dashboard/classes/{id}/roster?date=YYYY-MM-DD`.
2. `ClassAttendanceController::roster()` loads all bookings for that class on that date, cross-references `CheckIn` records to show a "checked-in hint" (informational only — never auto-sets attendance).
3. `ClassAttendanceController::mark()` creates or updates `ClassAttendance` per booking/date (status: pending/present/absent/permission, optional note).
4. Unique constraint `(class_booking_id, occurred_on)` prevents duplicates.

### Paid class checkout flow (NEW Aug 25)
1. Member browses `/gym/{slug}/classes` — free classes show "Book now", paid classes show "Add to cart".
2. `useCart` composable manages client-side cart (localStorage per tenant slug `gymsite_cart_{slug}`, reactive, `add/remove/has/clear/count/total`).
3. `CartDrawer` slide-over shows items + total; "Checkout" POSTs to `/gym/{slug}/classes/checkout` with `class_ids`.
4. `ClassCheckoutController::initiate()` validates: all classes belong to tenant, all are paid (`isPaid()`), none already booked. Generates KHQR via `BakongPaymentService`, creates `ClassOrder` + `ClassOrderItems` in DB transaction (price snapshot per item).
5. `Client/ClassPaymentPending.vue` renders KHQR via `qrcode` npm, polls `/class-orders/{id}/status` every 4 seconds.
6. Polling: if pending + auto-verify enabled, calls Bakong `checkTransactionByMD5`. On success → `completeOrder()` marks order verified, creates `ClassBooking` per item, creates `Payment` row.
7. If no Bakong token → "Simulate Payment" button visible (hard-blocked server-side if tenant has real token).
8. Maps verification methods: `bakong_api` → `bakong_khqr`, `manual_admin` → `cash`, `simulation` → `simulation`.

### Member self-registration (NEW Aug 25)
1. Public link: `/gym/{slug}/register` (guest only, throttled 5/min).
2. `MemberRegistrationController::create()` renders `Client/MemberRegister` with tenant name/slug.
3. `store()` validates name/email/phone/password → creates User (role=member, tenant_id=null, email_verified_at=now), creates/links Member via `firstOrCreate` + force-fill `user_id`, auto-logs in, redirects to account.
4. Google OAuth option: redirects to `/auth/google/redirect?tenant={slug}`, existing flow creates Member automatically.

### Member class self-enrollment (NEW Aug 25)
- From public classes page or account page, member books free classes directly via `MemberAccountController::bookClass()` (firstOrCreate).
- Unbook via `MemberAccountController::unbookClass()` (ownership enforced).
- Admin/staff can also book/unbook members into classes from `MemberController::bookClass()`/`unbookClass()` (walk-ins, no payment).

### Bakong KHQR membership purchase flow (Aug 21–22)
1. Member clicks a plan on the pricing page → `PlanPurchaseController::initiate`. Requires `tenants.bakong_account_id` (422 abort if missing) and requires the member to belong to that tenant.
2. `BakongPaymentService::generateQr` builds an individual KHQR (USD currency, billNumber = `MEMBER{id}-{timestamp}`) → returns `qr_string` + `md5`; a `SubscriptionPaymentRequest` row is created (`status=pending`).
3. `Client/PaymentPending.vue` renders the KHQR string and polls `payment.status` every few seconds.
4. Polling endpoint: if still `pending` **and** tenant has `bakong_api_token` (auto-verify mode), calls Bakong `checkTransactionByMD5`; on success `activateSubscription()` runs exactly once → request becomes `verified` (`verified_method=bakong_api`, `verified_at`), creates `MemberSubscription` (start now, end = plan.duration_days ?? 30) and a `Payment` row (`method=bakong_khqr`, `reference_type/id` pointing at the request — pseudo-polymorphic, not a morph map).
5. If no API token, auto-verify stays off; columns `verified_method='manual_admin'`, `approved_by`, `receipt_path` are reserved for a future manual-approval UI that **doesn't exist yet**.

### Public site extras
- `storeInquiry` writes straight into `members` (treats inquiries as leads) + fires a `new_inquiry` Notification shown in the dashboard bell.
- Home/pricing/classes pages pass `plans`, `activePromotions` (`currentlyLive()` scope), `trainers`, and classes with appended `spots_left`.
- Classes page passes `bookedClassIds` and `isLoggedInMember` for logged-in members.

### Media storage (`App\Support\MediaUrl`)
- `MediaUrl::resolve($path)` — converts bare relative paths (e.g. `avatars/photo.jpg`) to full URLs; passes through legacy full URLs unchanged.
- `MediaUrl::delete($path)` — deletes files by raw path; skips legacy full URLs.
- `MediaUrl::disk()` — returns the configured filesystem adapter (`config('filesystems.media_disk', 'public')`).
- Switch between local storage and Cloudflare R2 by changing `MEDIA_DISK=r2` in `.env`.

### Staff management & salary module (NEW Aug 27–29)
A **StaffProfile** is a per-tenant "employment record" that wraps an underlying payee — a User (`payable_type=user`, roles staff/manager/trainer) OR a Trainer (`payable_type=trainer`). It holds position, phone, salary config, QR token, and Telegram link. The same Trainer model does NOT get a login by default — only if an admin "Invites to Login" (`StaffController::inviteToLogin`), which creates a `trainer`-role User and re-points the StaffProfile's `payable_type` to that User (Trainer row is left intact for classes etc.).

- **Staff attendance** mirrors trainer attendance: single `StaffAttendance` per profile/day, toggled via QR scan or manually (`StaffAttendanceController::toggle`). CSV export per date.
- **Salary** (`SalaryController`): admin picks a staff profile + period, `SalaryCalculationService::calculate()` returns a preview (base = fixed/hourly-hours×rate; commission auto-computed only for `commission_source=class_booking` + trainer-type, else `manual_required` note). Admin approves to create a `SalaryPayment` (status `pending`), then `markPaid` sets `paid` + `paid_at` + `paid_by`. Paid records can't be deleted.
- **SalaryReportController** (`/dashboard/salary-report`): monthly/6-month/year ranges, prior/yoy comparison, per-staff breakdown, simple revenue-vs-salary ratio/net, trend-monthly series, and an "old pending" alert (unpaid >30 days). Revenue filters out refunded payments (`whereNull('refunded_at')`).
- **Admin "Invite to Login"** (`StaffController::inviteToLogin`): only trainer-type staff; validates email unique; creates a `trainer`-role User with random password, re-points the StaffProfile, emails a `TeamInviteMail`.
- **Self-service QR** (`StaffController::selfServiceQr`): only trainer-type; sets `Trainer.self_service_token`, renders a QR of `GET /my/trainer-login/{token}`. Scanning it logs the trainer into `/my/staff` via session (`TrainerSelfLoginController`).
- `StaffSelfServiceController` (`/my/staff`) lets the linked staff member update their own name/phone/photo and view their attendance + salary payments. Photo/name write through to the underlying payee (Trainer `photo_url` or User `avatar`); phone lives on the StaffProfile.

### Payment refunds (NEW Aug 26)
- `PaymentController::refund()` is admin-only (`role:gym_admin`). It sets `refunded_at/refunded_by/refund_note` on the Payment (bookkeeping only; revenue everywhere now excludes refunded payments via `whereNull('refunded_at')`).
- If the payment's `reference_type = ClassOrder`, refunding also **deletes the member's ClassBookings** for each paid class in that order and sets the order `status='refunded'`. It does NOT auto-refund subscriptions (those stay managed manually).

### Trainer self-service magic link (NEW Aug 28)
- `GET /my/trainer-login/{token}` validates `Trainer.self_service_token` (`withoutGlobalScopes`), stores `trainer_staff_id` in session, redirects to `my.staff.index`. No Laravel auth user — the `EnsureStaffAccess` middleware permits the session id, and `StaffSelfServiceController` renders `staffViewer` so the header shows the staff dropdown instead of guest.

### Member notifications (NEW Aug 26)
- `notifications.member_id` lets members get their own bell. When a member books a class, two notifications are created: one admin (member_id null, `class_booking`) and one member-confirmation (member_id set, `class_booking_confirmed`). Members can mark their own read (scoped to their `member_id`, 403 otherwise). Shared via `memberNotifications` Inertia prop.

### Member lookup helpers (NEW)
- `MemberAccountController::resolveMember()` (and `HandleInertiaRequests` member props) now **progressively fall back**: (1) `user_id + tenant_id`, (2) any `user_id` (self-heals `tenant_id`), (3) `tenant_id + email` (self-heals `user_id`), else 404. This removes the old hard 404 from `User.tenant_id`/`Member.tenant_id` drift during Google OAuth.
- `CheckMemberTenantIntegrity` artisan command audits these mismatches (Cases 1–4) with optional `--fix` (repair Cases 1–3) and `--create-missing` (fabricate a Member row for genuinely orphaned members).

## 9. Frontend conventions

- **i18n:** `useLang()` composable → `t` is a computed dict (`km` default). Add new keys to **both** `lang/km.js` and `lang/en.js`.
- **Theme:** `useTheme()` toggles `dark` class on `<html>` (localStorage `gymsite_theme`).
- **Sidebar:** `useSidebar()` collapse state (localStorage `gymsite_sidebar_collapsed`).
- **Cart:** `useCart()` composable — per-tenant-slug client-side cart (localStorage `gymsite_cart_{slug}`, auto-syncs). Used by `GymClasses.vue` and `CartDrawer.vue`.
- **Layout:** Admin pages use `AdminLayout.vue`; public pages use `ClientLayout.vue` with shared `SiteHeader`/`SiteFooter`/`CartDrawer` components. Admin nav supports role filtering via `adminOnly`.
- **Directives:** `v-reveal` (scroll reveal), `v-click-outside` are global.
- **Flash messages:** controllers return `with('success'|'warning'|'checkedInMember', …)`; surfaced via the shared `flash` prop.
- **QR scanning pages** use `html5-qrcode`; payment QR rendering uses the `qrcode` npm package.
- UI strings/messages are commonly written directly in Khmer.

## 10. Dev workflow

```bash
composer setup          # install + env + migrate + npm build (one-shot)
composer dev            # concurrently: artisan serve + queue:listen + pail(logs) + vite
composer test           # config:clear + phpunit
npm run dev / npm run build
php artisan telegram:set-webhook {public-base-url}
```

- Scheduler (`routes/console.php`): `notifications:membership-expiring` daily at 08:00.
- Tests: `tests/Feature` + `tests/Unit` (still only Laravel boilerplate examples).
- Local Telegram/KHQR testing needs a public URL (Expose/tunnel). The webhook itself is synchronous — no queue worker needed for it.
- **Simulate payments** for dev/testing: the `/class-orders/{order}/simulate` endpoint lets you complete a class order without a real Bakong merchant (blocked automatically if tenant has a real `bakong_api_token`).

## 11. Progress status (as of Sep 1, 2026)

- ✅ Auth (register/login/logout, throttled), signed team invites + password reset, roles
- ✅ Tenant identification + global scoping, super-admin tenant management
- ✅ Members CRUD (+Show detail page with class booking management), Classes CRUD (+price field), Payments, Promotions CRUD, Reports (+export), Team management, Profile, Notifications (real, shared via Inertia), Website Editor + media images
- ✅ Public gym site expanded: home, pricing, trainers (+detail), classes (+detail), gallery, contact/inquiry via `ClientLayout` + `SiteHeader`/`SiteFooter` + `CartDrawer`, and a per-gym `MemberLogin`
- ✅ Scheduler for expiring-membership notifications
- ✅ Member check-in system (manual search + QR scan, membership gating, daily dedupe, trends)
- ✅ Trainers CRUD (+photos), Trainer attendance (scan/toggle/export/late detection), Telegram bot (link accounts, deliver/re-deliver QR codes, set-webhook command), quick search
- ✅ Google OAuth login/register + emailed OTP verification (password-nullable accounts), `setup-gym` owner onboarding step
- ✅ Member self-service portal `/gym/{slug}/account` (profile edit with photo upload, personal QR, subscription/check-ins/bookings view, class self-enrollment)
- ✅ Self-service membership purchase via Bakong KHQR with API auto-verification
- ✅ **NEW Aug 24:** Class attendance tracking — roster screen per class per date, mark present/absent/permission, cross-references gym check-in records
- ✅ **NEW Aug 25:** Paid classes e-commerce — class price field, client-side cart (`useCart` composable + `CartDrawer`), KHQR checkout flow for paid classes, simulation endpoint for dev
- ✅ **NEW Aug 25:** Member self-registration — public `/gym/{slug}/register` page with Google OAuth + traditional form
- ✅ **NEW Aug 25:** MediaUrl helper + Cloudflare R2 support (`MEDIA_DISK` env)
- ✅ **NEW Aug 26:** Payment refunds (manual, admin-only) — `refunded_at/by/note`, refunded class orders auto-unenroll the member + mark order `refunded`; all revenue stats exclude refunded payments
- ✅ **NEW Aug 26:** Member-scoped notifications (`notifications.member_id`) with member-side bell + read/read-all; member lookup now self-heals tenant/user_id drift (`resolveMember` fallbacks); `members:check-integrity` artisan audit/fix command
- ✅ **NEW Aug 27–29:** Staff & salary module — `StaffProfile` (wraps a User or Trainer), `StaffAttendance` (QR toggle + export), `SalaryCalculationService` (fixed/hourly/commission preview), `SalaryPayment` (create → pending → paid), `SalaryReport` (month/6mo/year, prior/yoy, per-staff breakdown, old-pending alert), **`manager` role** gating staff/salary, and **`trainer` user role** for invited trainer logins
- ✅ **NEW Aug 28:** Staff self-service — per-profile QR + `TrainerSelfLoginController` magic link → `/my/staff` portal (edit own name/phone/photo, view attendance + salary) via `EnsureStaffAccess`
- ⏭️ **Planned (Phase A):** E-commerce — products, variants, cart, orders (admin confirm by hand; pickup at gym only; no reviews/shipping)
- ⏭️ **Phase B remainder:** ABA PayWay; manual admin approval UI for `SubscriptionPaymentRequest` (`receipt_path`/`approved_by` unused)
- ⏭️ Membership pause feature (columns exist, no UI/logic beyond the `upcomingExpiring` filter)
- 📌 Pending decision: splitting `themes/` and `lang/` into separate folders — not yet done; lang sits in flat `lang/en.js`, `lang/km.js`
- ⚠️ Housekeeping (pre-deploy): 3 stray empty files at project root (`first()`, `id])`, `isAutoVerifyEnabled($tenant)`) are 0-byte copy-paste artifacts — safe to delete. 3 migrations have a double `.php.php` extension (Laravel still runs them, but consider renaming). An orphan duplicate controller sits at `app/Services/StaffSelfServiceController.php`.

## 12. Notes for AI agents

- The user prefers to chat in **Khmer** (ខ្មែរ) from now on, and works **planning-first**: explain trade-offs and propose an approach *before* writing code.
- In this environment (opencode) the project is at `/mnt/f/Bunnengfile/laragon/www/gymsite` and files can be read/edited directly.
- The original Laravel `README.md` is untouched boilerplate — don't rely on it for project context.
- Code style: no inline comments unless clarifying logic; match existing controller/page patterns (validation → `inertia(...)` response or redirect with flash).
- Never commit or echo secrets from `.env` (Telegram bot token/webhook secret, Google client secret, R2 keys).
- When touching member-facing routes remember the lookup rule: `users.tenant_id` is NULL for members — always go through `members.user_id` with `withoutGlobalScopes()` (and prefer the self-healing `resolveMember()` fallback pattern rather than a bare `firstOrFail`).
- **Class pricing logic:** `GymClass::isPaid()` is the single source of truth. Null/0 = free (instant booking), >0 = paid (cart + KHQR checkout). Price is snapshot in `ClassOrderItem` at checkout.
- **Simulation safety:** `ClassCheckoutController::simulate()` hard-blocks (403) if tenant has a real `bakong_api_token`, preventing abuse in production.
- **Roles:** `manager` and `trainer` were added to the `users.role` enum (Aug 27/29). Membership is via the enum on `User`, not a separate pivot. The `role` gate uses middleware aliases — know which roles a block allows (`gym_admin,staff` vs `gym_admin,manager` vs `gym_admin`) before adding a route.
- **StaffProfile is a wrapper:** `StaffProfile` stores a `payable_type`/`payable_id` plain pair (User's id OR Trainer's id), NOT an Eloquent polymorphic relation. `name`/`photo_url` are derived accessors. Always resolve the payee via `->payable()`; phone lives on the StaffProfile, while name/photo live on the underlying payee.
- **Trainer login is session-only:** the trainer magic link never calls `Auth::login()` — it relies on `trainer_staff_id` in session + `EnsureStaffAccess`. Don't assume `auth.user` exists on `/my/*` trainer sessions; use the `staffViewer` prop instead.
- **Never delete paid salary records** guard server-side (`SalaryController::destroy` aborts 403 if `status = paid`).
- **Refunded payments are excluded from all revenue** totals via `whereNull('refunded_at')` — keep that predicate when writing new revenue queries.
