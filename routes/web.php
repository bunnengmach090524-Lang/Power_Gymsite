<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TenantSetupController;
use App\Http\Controllers\Admin\OverviewController;
use App\Http\Controllers\Public\GymSiteController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard.overview') : redirect()->route('login');
})->name('home');

Route::middleware('tenant.identify')->prefix('gym/{slug}')->group(function () {
    Route::get('/', [GymSiteController::class, 'home'])->name('public.home');
    Route::get('/pricing', [GymSiteController::class, 'pricing'])->name('public.pricing');
    Route::get('/trainers', [GymSiteController::class, 'trainers'])->name('public.trainers');
    Route::get('/trainers/{trainerId}', [GymSiteController::class, 'trainerShow'])->name('public.trainers.show');
    Route::get('/classes', [GymSiteController::class, 'classes'])->name('public.classes');
    Route::get('/classes/{classId}', [GymSiteController::class, 'classShow'])->name('public.classes.show');
    Route::get('/gallery', [GymSiteController::class, 'gallery'])->name('public.gallery');
    Route::get('/contact', [GymSiteController::class, 'contact'])->name('public.contact');
    Route::post('/inquiries', [GymSiteController::class, 'storeInquiry'])->name('public.inquiries.store');
     Route::get('/login', [GymSiteController::class, 'login'])->name('public.login'); // ← ថ្មី
});

Route::middleware(['tenant.identify', 'guest'])->prefix('gym/{slug}')->group(function () {
    Route::get('register', [\App\Http\Controllers\Public\MemberRegistrationController::class, 'create'])
        ->name('member.register');
    Route::post('register', [\App\Http\Controllers\Public\MemberRegistrationController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('member.register.store');
    Route::get('login', [\App\Http\Controllers\Public\GymSiteController::class, 'login'])
        ->name('public.login');
});

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1'); // 5 ដងក្នុង 1 នាទី
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1'); // 5 ដងក្នុង 1 នាទី តាម IP

    // Google OAuth login/register
    Route::get('auth/google/redirect', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'redirect'])->name('google.redirect');
    Route::get('auth/google/callback', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'callback'])->name('google.callback');
    Route::get('auth/google/verify', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'verifyPage'])->name('google.verify');
    Route::post('auth/google/verify', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'verify'])->name('google.verify.store');
    Route::post('auth/google/resend', [\App\Http\Controllers\Auth\GoogleAuthController::class, 'resend'])->name('google.verify.resend')->middleware('throttle:3,1');

    Route::get('forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'create'])
        ->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Auth\PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:3,1')
        ->name('password.email');
    Route::get('reset-password/{token}', [\App\Http\Controllers\Auth\NewPasswordController::class, 'create'])
        ->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Auth\NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('setup-gym', [TenantSetupController::class, 'show'])->name('setup-gym');
    Route::post('setup-gym', [TenantSetupController::class, 'store'])->name('setup-gym.store');
});

Route::get('invite/{user}/accept', [\App\Http\Controllers\Auth\InvitationController::class, 'show'])
    ->middleware('signed')
    ->name('invite.accept');
Route::post('invite/{user}/accept', [\App\Http\Controllers\Auth\InvitationController::class, 'store'])
    ->middleware('signed')
    ->name('invite.store');

Route::middleware(['auth', 'superadmin'])->prefix('super-admin')->name('superadmin.')->group(function () {
    Route::get('/', [\App\Http\Controllers\SuperAdmin\DashboardController::class, 'index'])->name('dashboard');

    Route::get('tenants', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'index'])->name('tenants.index');
    Route::get('tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'show'])->name('tenants.show');
    Route::patch('tenants/{tenant}/suspend', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'suspend'])->name('tenants.suspend');
    Route::patch('tenants/{tenant}/activate', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'activate'])->name('tenants.activate');
    Route::delete('tenants/{tenant}', [\App\Http\Controllers\SuperAdmin\TenantController::class, 'destroy'])->name('tenants.destroy');
});

Route::middleware(['auth', 'tenant.member'])->prefix('dashboard')->name('dashboard.')->group(function () {

    // 👇 គ្រប់ role ដែល login ចូល dashboard អាចកែ profile ខ្លួនឯង (ចេតនា — profile
    //     មិនមែនទិន្នន័យអាជីវកម្មទេ ដូច្នេះមិនចាំបាច់ដាក់ role restriction)
    Route::get('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('profile', [\App\Http\Controllers\Admin\ProfileController::class, 'update'])->name('profile.update');

    // ============================================================
    // role:gym_admin,staff — ការងារប្រចាំថ្ងៃ (member, check-in, class)
    // ⚠️ Trainer management (resource CRUD + QR + Telegram) ត្រូវបានផ្លាស់ទីទៅ
    //     group role:gym_admin ខាងក្រោម ដើម្បីកុំឲ្យ staff កែព័ត៌មាន trainer បាន។
    //     staff នៅតែអាច check-in/check-out trainer បានធម្មតា (trainer-attendance.*)។
    //
    // ⚠️ FIX (Aug 26): 'overview' ត្រូវផ្លាស់ទីមកទីនេះពី outside the role group —
    //     ពីមុនវាមាន middleware ត្រឹមតែ 'auth' + 'tenant.member' ដូច្នេះ member
    //     ណាមួយក៏អាចមើលឃើញ Overview dashboard (revenue, member counts ។ល។)
    //     ដែលជាទិន្នន័យអាជីវកម្មសម្រាប់ admin/staff ប៉ុណ្ណោះ។
    // ============================================================
    Route::middleware('role:gym_admin,staff')->group(function () {
        Route::get('/', [OverviewController::class, 'index'])->name('overview');

        Route::resource('members', \App\Http\Controllers\Admin\MemberController::class);
        // 👇 Admin/staff add/remove member ចេញ/ចូល class ដោយផ្ទាល់
        Route::post('members/{member}/classes', [\App\Http\Controllers\Admin\MemberController::class, 'bookClass'])->name('members.classes.store');
        Route::delete('members/{member}/classes/{booking}', [\App\Http\Controllers\Admin\MemberController::class, 'unbookClass'])->name('members.classes.destroy');
        Route::resource('classes', \App\Http\Controllers\Admin\ClassController::class);
        Route::get('classes/{class}/roster', [\App\Http\Controllers\Admin\ClassAttendanceController::class, 'roster'])->name('classes.roster');
        Route::post('classes/{class}/roster/mark', [\App\Http\Controllers\Admin\ClassAttendanceController::class, 'mark'])->name('classes.roster.mark');

        Route::get('check-in', [\App\Http\Controllers\Admin\CheckInController::class, 'index'])->name('checkin.index');
        Route::get('check-in/search', [\App\Http\Controllers\Admin\CheckInController::class, 'search'])->name('checkin.search');
        Route::post('check-in', [\App\Http\Controllers\Admin\CheckInController::class, 'store'])->name('checkin.store');
        Route::delete('check-in/{checkIn}', [\App\Http\Controllers\Admin\CheckInController::class, 'destroy'])->name('checkin.destroy');
        Route::get('check-in/scan', [\App\Http\Controllers\Admin\CheckInController::class, 'scanPage'])->name('checkin.scan');
        Route::post('check-in/scan', [\App\Http\Controllers\Admin\CheckInController::class, 'scan'])->name('checkin.scan.store');

        Route::get('members/{member}/qr', [\App\Http\Controllers\Admin\MemberController::class, 'qrCode'])->name('members.qr');
        Route::get('members/{member}/connect-telegram', [\App\Http\Controllers\Admin\MemberController::class, 'connectTelegram'])->name('members.connect-telegram');
        Route::post('members/{member}/resend-telegram-qr', [\App\Http\Controllers\Admin\MemberController::class, 'resendTelegramQr'])->name('members.resendTelegramQr');

        Route::get('trainer-attendance', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'index'])->name('trainer-attendance.index');
        Route::post('trainer-attendance/check-in', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'checkIn'])->name('trainer-attendance.checkin');
        Route::patch('trainer-attendance/{attendance}/check-out', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'checkOut'])->name('trainer-attendance.checkout');
        Route::delete('trainer-attendance/{attendance}', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'destroy'])->name('trainer-attendance.destroy');
        Route::get('trainer-attendance/scan', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'scanPage'])->name('trainer-attendance.scan');
        Route::post('trainer-attendance/scan', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'scan'])->name('trainer-attendance.scan.store');
        Route::post('trainer-attendance/toggle', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'toggleByTrainer'])->name('trainer-attendance.toggle');
        Route::get('trainer-attendance/export', [\App\Http\Controllers\Admin\TrainerAttendanceController::class, 'export'])->name('trainer-attendance.export');

        Route::get('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/{notification}/read', [\App\Http\Controllers\Admin\NotificationController::class, 'markRead'])->name('notifications.read');
        Route::patch('notifications/read-all', [\App\Http\Controllers\Admin\NotificationController::class, 'markAllRead'])->name('notifications.readAll');
        Route::delete('notifications/{notification}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroy'])->name('notifications.destroy');

        Route::get('search', [\App\Http\Controllers\Admin\QuickSearchController::class, 'index'])->name('search');
    });

    // ============================================================
    // role:gym_admin ប៉ុណ្ណោះ — website, team, promotions, payments,
    // reports, ព្រមទាំង trainer management ទាំងមូល (CRUD + QR + Telegram)
    // ============================================================
    Route::middleware('role:gym_admin')->group(function () {
        Route::get('website-editor', [\App\Http\Controllers\Admin\WebsiteSettingsController::class, 'edit'])->name('website.edit');
        Route::patch('website-editor', [\App\Http\Controllers\Admin\WebsiteSettingsController::class, 'update'])->name('website.update');
        Route::post('media-images', [\App\Http\Controllers\Admin\MediaImageController::class, 'store'])->name('media.store');
        Route::delete('media-images/{mediaImage}', [\App\Http\Controllers\Admin\MediaImageController::class, 'destroy'])->name('media.destroy');

        Route::get('team', [\App\Http\Controllers\Admin\TeamController::class, 'index'])->name('team.index');
        Route::post('team/invite', [\App\Http\Controllers\Admin\TeamController::class, 'invite'])->name('team.invite');
        Route::post('team/{member}/resend', [\App\Http\Controllers\Admin\TeamController::class, 'resend'])->name('team.resend');
        Route::patch('team/{member}/role', [\App\Http\Controllers\Admin\TeamController::class, 'updateRole'])->name('team.updateRole');
        Route::delete('team/{member}', [\App\Http\Controllers\Admin\TeamController::class, 'destroy'])->name('team.destroy');

        Route::resource('promotions', \App\Http\Controllers\Admin\PromotionController::class);
        Route::resource('plans', \App\Http\Controllers\Admin\MembershipPlanController::class)->except(['show']);
        Route::get('payments', [\App\Http\Controllers\Admin\PaymentController::class, 'index'])->name('payments.index');
        Route::get('payments/create', [\App\Http\Controllers\Admin\PaymentController::class, 'create'])->name('payments.create');
        Route::post('payments', [\App\Http\Controllers\Admin\PaymentController::class, 'store'])->name('payments.store');
        Route::patch('payments/{payment}/refund', [\App\Http\Controllers\Admin\PaymentController::class, 'refund'])->name('payments.refund');

        // ចំណាំ: route ជាក់លាក់ (reports/export) ត្រូវចុះឈ្មោះមុន route ទូទៅ/resource ដែលអាចប៉ះទង្គិច
        Route::get('reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [\App\Http\Controllers\Admin\ReportController::class, 'export'])->name('reports.export');

        // Trainer management (admin-only) — resource CRUD + QR + Telegram
        // ចំណាំ: 'trainers/{trainer}/qr' ត្រូវចុះឈ្មោះនៅទីនេះម្តងគត់ (មិនស្ទួនក្នុង staff group ទៀតទេ)
        Route::resource('trainers', \App\Http\Controllers\Admin\TrainerController::class);
        Route::get('trainers/{trainer}/qr', [\App\Http\Controllers\Admin\TrainerController::class, 'qrCode'])->name('trainers.qr');
        Route::get('trainers/{trainer}/connect-telegram', [\App\Http\Controllers\Admin\TrainerController::class, 'connectTelegram'])->name('trainers.connect-telegram');
        Route::post('trainers/{trainer}/resend-telegram-qr', [\App\Http\Controllers\Admin\TrainerController::class, 'resendTelegramQr'])->name('trainers.resendTelegramQr');
    });

    // ============================================================
    // role:gym_admin,manager — staff/salary management. Manager can run
    // day-to-day HR (attendance, salary) without needing full gym_admin
    // access to team/trainer/business-settings routes above.
    // ============================================================
    Route::middleware('role:gym_admin,manager')->group(function () {
        Route::get('staff', [\App\Http\Controllers\Admin\StaffController::class, 'index'])->name('staff.index');
        Route::post('staff', [\App\Http\Controllers\Admin\StaffController::class, 'store'])->name('staff.store');
        Route::patch('staff/{staffProfile}', [\App\Http\Controllers\Admin\StaffController::class, 'update'])->name('staff.update');
        Route::delete('staff/{staffProfile}', [\App\Http\Controllers\Admin\StaffController::class, 'destroy'])->name('staff.destroy');
        Route::get('staff/{staffProfile}/qr', [\App\Http\Controllers\Admin\StaffController::class, 'qrCode'])->name('staff.qr');
        Route::get('staff/{staffProfile}/edit', [\App\Http\Controllers\Admin\StaffController::class, 'edit'])->name('staff.edit');
        Route::get('staff/{staffProfile}/connect-telegram', [\App\Http\Controllers\Admin\StaffController::class, 'connectTelegram'])->name('staff.connect-telegram');
        Route::post('staff/{staffProfile}/resend-telegram-qr', [\App\Http\Controllers\Admin\StaffController::class, 'resendTelegramQr'])->name('staff.resendTelegramQr');
        

        Route::get('staff-attendance', [\App\Http\Controllers\Admin\StaffAttendanceController::class, 'index'])->name('staff-attendance.index');
        Route::get('staff-attendance/scan', [\App\Http\Controllers\Admin\StaffAttendanceController::class, 'scanPage'])->name('staff-attendance.scan');
        Route::post('staff-attendance/scan', [\App\Http\Controllers\Admin\StaffAttendanceController::class, 'scan'])->name('staff-attendance.scan.store');
        Route::post('staff-attendance/toggle', [\App\Http\Controllers\Admin\StaffAttendanceController::class, 'toggleByProfile'])->name('staff-attendance.toggle');
        Route::get('staff-attendance/export', [\App\Http\Controllers\Admin\StaffAttendanceController::class, 'export'])->name('staff-attendance.export');
        Route::delete('staff-attendance/{attendance}', [\App\Http\Controllers\Admin\StaffAttendanceController::class, 'destroy'])->name('staff-attendance.destroy');

        Route::get('salary', [\App\Http\Controllers\Admin\SalaryController::class, 'index'])->name('salary.index');
        Route::post('salary/calculate', [\App\Http\Controllers\Admin\SalaryController::class, 'calculate'])->name('salary.calculate');
        Route::post('salary', [\App\Http\Controllers\Admin\SalaryController::class, 'store'])->name('salary.store');
        Route::patch('salary/{salaryPayment}/mark-paid', [\App\Http\Controllers\Admin\SalaryController::class, 'markPaid'])->name('salary.markPaid');
        Route::delete('salary/{salaryPayment}', [\App\Http\Controllers\Admin\SalaryController::class, 'destroy'])->name('salary.destroy');

        Route::get('staff/{staffProfile}/self-service-qr', [\App\Http\Controllers\Admin\StaffController::class, 'selfServiceQr'])->name('staff.selfServiceQr');
        Route::post('staff/{staffProfile}/invite-login', [\App\Http\Controllers\Admin\StaffController::class, 'inviteToLogin'])->name('staff.inviteToLogin');

        Route::get('salary-report', [\App\Http\Controllers\Admin\SalaryReportController::class, 'index'])->name('salary-report.index');
        Route::get('salary-report/export', [\App\Http\Controllers\Admin\SalaryReportController::class, 'export'])->name('salary-report.export');
    });
});

// នៅខាងក្រៅ middleware ទាំងអស់ (Telegram servers ហៅដោយគ្មាន session/CSRF)
Route::post('telegram/webhook/{secret}', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('telegram.webhook');


// External cron trigger (cron-job.org) — replaces server-level crontab
// since Render free tier has no Cron Job service. Token compared with
// hash_equals to prevent unauthorized triggering of scheduled tasks.
Route::get('/cron/run-schedule/{token}', function (string $token) {
    abort_unless(hash_equals(config('app.cron_secret') ?? '', $token), 403);
    \Illuminate\Support\Facades\Artisan::call('schedule:run');
    return response('OK');
});

Route::middleware(['tenant.identify', 'auth'])->prefix('gym/{slug}')->group(function () {
    Route::get('/account', [\App\Http\Controllers\Public\MemberAccountController::class, 'show'])->name('member.account');
    Route::post('/account', [\App\Http\Controllers\Public\MemberAccountController::class, 'update'])->name('member.account.update');
    Route::get('/account/qr', [\App\Http\Controllers\Public\MemberAccountController::class, 'qrCode'])->name('member.account.qr');

    Route::post('/account/classes/{class}/book', [\App\Http\Controllers\Public\MemberAccountController::class, 'bookClass'])->name('member.account.book');
    Route::delete('/account/classes/{class}/unbook', [\App\Http\Controllers\Public\MemberAccountController::class, 'unbookClass'])->name('member.account.unbook');

    Route::patch('/account/notifications/{notification}/read', [\App\Http\Controllers\Public\MemberAccountController::class, 'markNotificationRead'])->name('member.account.notifications.read');
    Route::patch('/account/notifications/read-all', [\App\Http\Controllers\Public\MemberAccountController::class, 'markAllNotificationsRead'])->name('member.account.notifications.readAll');

    Route::get('/plans/{plan}/purchase', [\App\Http\Controllers\Public\PlanPurchaseController::class, 'initiate'])->name('plan.purchase');
    Route::get('/payment-requests/{paymentRequest}/status', [\App\Http\Controllers\Public\PlanPurchaseController::class, 'checkStatus'])->name('payment.status');

    // 👇 Class checkout (paid classes — cart → KHQR)
    Route::post('/classes/checkout', [\App\Http\Controllers\Public\ClassCheckoutController::class, 'initiate'])->name('class.checkout.initiate');
    Route::get('/class-orders/{order}/status', [\App\Http\Controllers\Public\ClassCheckoutController::class, 'checkStatus'])->name('class.checkout.status');
    Route::post('/class-orders/{order}/simulate', [\App\Http\Controllers\Public\ClassCheckoutController::class, 'simulate'])->name('class.checkout.simulate');
});

Route::get('/my/trainer-login/{token}', [App\Http\Controllers\TrainerSelfLoginController::class, 'login'])
    ->name('trainer.self.login');

Route::middleware(['staff.access'])->prefix('my')->name('my.')->group(function () {
    Route::get('/staff', [App\Http\Controllers\StaffSelfServiceController::class, 'index'])->name('staff.index');
    Route::post('/staff', [App\Http\Controllers\StaffSelfServiceController::class, 'update'])->name('staff.update'); // ⚠️ NEW
    Route::get('/staff/qr', [App\Http\Controllers\StaffSelfServiceController::class, 'qr'])->name('staff.qr');
});