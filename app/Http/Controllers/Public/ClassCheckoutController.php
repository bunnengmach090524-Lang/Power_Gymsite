<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\ClassOrder;
use App\Models\GymClass;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Tenant;
use App\Services\BakongPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ClassCheckoutController extends Controller
{
    public function __construct(protected BakongPaymentService $bakongService)
    {
    }

    private function resolveMember(Request $request, Tenant $tenant): Member
    {
        $user = $request->user();

        abort_unless($user->role === 'member', 403);

        return Member::withoutGlobalScopes()
            ->where('user_id', $user->id)
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();
    }

    /**
     * Member's cart -> create a ClassOrder + items -> generate KHQR ->
     * render the payment pending page. Mirrors PlanPurchaseController's
     * pattern but supports multiple classes per order.
     */
    public function initiate(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $member = $this->resolveMember($request, $tenant);

        $validated = $request->validate([
            'class_ids' => 'required|array|min:1',
            'class_ids.*' => 'integer|exists:classes,id',
        ]);

        $classes = GymClass::where('tenant_id', $tenant->id)
            ->whereIn('id', $validated['class_ids'])
            ->get();

        if ($classes->count() !== count($validated['class_ids'])) {
            throw ValidationException::withMessages(['class_ids' => 'Class មួយចំនួនមិនត្រូវនឹង gym នេះទេ']);
        }

        // Guard against paying for a free class or a class already booked —
        // keeps the paid checkout flow exclusively for genuine paid add-ons.
        $alreadyBookedIds = ClassBooking::where('member_id', $member->id)
            ->whereIn('class_id', $classes->pluck('id'))
            ->pluck('class_id')
            ->all();

        foreach ($classes as $class) {
            if (! $class->isPaid()) {
                throw ValidationException::withMessages(['class_ids' => "{$class->name} ជា class ឥតគិតថ្លៃ — ចុះឈ្មោះដោយផ្ទាល់មិនចាំបាច់ទូទាត់ទេ"]);
            }
            if (in_array($class->id, $alreadyBookedIds, true)) {
                throw ValidationException::withMessages(['class_ids' => "អ្នកបានចុះឈ្មោះ {$class->name} រួចហើយ"]);
            }
        }

        abort_if(empty($tenant->bakong_account_id), 422, 'Gym មិនទាន់ setup payment method');

        $totalAmount = $classes->sum('price');
        $billNumber = 'CLASS' . $member->id . '-' . now()->timestamp;
        $qrData = $this->bakongService->generateQr($tenant, (float) $totalAmount, $billNumber);

        $order = DB::transaction(function () use ($tenant, $member, $classes, $totalAmount, $qrData) {
            $order = ClassOrder::create([
                'tenant_id' => $tenant->id,
                'member_id' => $member->id,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'khqr_qr_string' => $qrData['qr_string'],
                'khqr_md5' => $qrData['md5'],
            ]);

            foreach ($classes as $class) {
                $order->items()->create([
                    'class_id' => $class->id,
                    'price' => $class->price,
                ]);
            }

            return $order;
        });

        return Inertia::render('Client/ClassPaymentPending', [
            'tenant' => $tenant->only('id', 'name', 'slug'),
            'order' => [
                'id' => $order->id,
                'total_amount' => $order->total_amount,
                'items' => $classes->map(fn ($c) => ['name' => $c->name, 'price' => $c->price])->values(),
            ],
            'qrString' => $qrData['qr_string'],
            'autoVerifyEnabled' => $this->bakongService->isAutoVerifyEnabled($tenant),
            'canSimulate' => ! $this->bakongService->isAutoVerifyEnabled($tenant),
            'settings' => $tenant->websiteSetting,   // ថ្មី
            'heroImages' => $tenant->mediaImages()->where('type', 'hero_banner')->orderBy('display_order')->get(), // ថ្មី
        ]);
    }

    public function checkStatus(Request $request, string $slug, ClassOrder $order)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        abort_unless($order->tenant_id === $tenant->id, 403);

        if ($order->status === 'pending' && $this->bakongService->isAutoVerifyEnabled($tenant)) {
            $verified = $this->bakongService->verifyTransaction($tenant, $order->khqr_md5);

            if ($verified) {
                $this->completeOrder($order, 'bakong_api');
            }
        }

        return response()->json(['status' => $order->fresh()->status]);
    }

    /**
     * Dev/testing only — lets the flow be completed without a real Bakong
     * merchant account. Hard-blocked whenever the tenant has a real token,
     * regardless of what the frontend sends, so this can never be used to
     * skip a genuine payment in production.
     */
    public function simulate(Request $request, string $slug, ClassOrder $order)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        abort_unless($order->tenant_id === $tenant->id, 403);
        abort_if($this->bakongService->isAutoVerifyEnabled($tenant), 403, 'Simulate មិនអនុញ្ញាតទេ — gym នេះមាន Bakong account ពិតប្រាកដរួចហើយ');
        abort_unless($order->status === 'pending', 422);

        $this->completeOrder($order, 'simulation');

        return response()->json(['status' => 'verified']);
    }

    /**
     * Shared completion step: mark the order verified, create a booking for
     * every item, and record a Payment row for reporting — same shape as
     * PlanPurchaseController::activateSubscription().
     */
    protected function completeOrder(ClassOrder $order, string $method, ?int $approvedBy = null): void
    {
        $paymentMethod = match ($method) {
            'bakong_api' => 'bakong_khqr',
            'manual_admin' => 'cash',
            'simulation' => 'simulation',
            default => 'cash',
        };

        DB::transaction(function () use ($order, $method, $approvedBy, $paymentMethod) {
            $order->update([
                'status' => 'verified',
                'verified_method' => $method,
                'approved_by' => $approvedBy,
                'verified_at' => now(),
            ]);

            $classNames = [];
            foreach ($order->items as $item) {
                ClassBooking::firstOrCreate(
                    ['member_id' => $order->member_id, 'class_id' => $item->class_id],
                    ['booked_at' => now()]
                );
                if ($item->gymClass) {
                    $classNames[] = $item->gymClass->name;
                }
            }

            Payment::create([
                'tenant_id' => $order->tenant_id,
                'member_id' => $order->member_id,
                'amount' => $order->total_amount,
                'method' => $paymentMethod,
                'reference_type' => ClassOrder::class,
                'reference_id' => $order->id,
            ]);

            // 👇 Notification សម្រាប់ admin ពេលទូទាត់ paid class ជោគជ័យ
            Notification::create([
                'tenant_id' => $order->tenant_id,
                'type' => 'class_payment',
                'title' => 'ការទូទាត់ Class ជោគជ័យ',
                'message' => ($order->member->name ?? 'សមាជិក') . ' បានទូទាត់ $' . number_format($order->total_amount, 2)
                    . ' សម្រាប់ ' . (empty($classNames) ? 'class' : implode(', ', $classNames)),
                'link' => null,
            ]);
        });
    }
}