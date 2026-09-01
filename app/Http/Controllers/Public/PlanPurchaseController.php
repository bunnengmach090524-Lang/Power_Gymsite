<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\MembershipPlan;
use App\Models\SubscriptionPaymentRequest;
use App\Services\BakongPaymentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlanPurchaseController extends Controller
{
    public function __construct(protected BakongPaymentService $bakongService)
    {
    }

    /**
     * User click "ជ្រើសរើស Plan" -> generate QR -> show payment pending page
     */
    public function initiate(Request $request, string $slug, MembershipPlan $plan)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();
        $user = $request->user();

        abort_unless($user->role === 'member', 403);

        $member = $user->member()->where('tenant_id', $tenant->id)->firstOrFail();

        // ត្រូវប្រាកដថា tenant configure bakong_account_id រួច (settings step)
        abort_if(empty($tenant->bakong_account_id), 422, 'Gym mិនទាន់ setup payment method');

        $billNumber = 'MEMBER' . $member->id . '-' . now()->timestamp;

        $qrData = $this->bakongService->generateQr($tenant, (float) $plan->price, $billNumber);

        $paymentRequest = SubscriptionPaymentRequest::create([
            'tenant_id' => $tenant->id,
            'member_id' => $member->id,
            'membership_plan_id' => $plan->id,
            'amount' => $plan->price,
            'khqr_md5' => $qrData['md5'],
            'khqr_qr_string' => $qrData['qr_string'],
            'status' => 'pending',
        ]);

        return Inertia::render('Client/PaymentPending', [
            'tenant' => $tenant->only('id', 'name', 'slug'),
            'plan' => $plan->only('id', 'name', 'price', 'duration_days'),
            'qrString' => $qrData['qr_string'],
            'paymentRequestId' => $paymentRequest->id,
            'autoVerifyEnabled' => $this->bakongService->isAutoVerifyEnabled($tenant),
            'settings' => $tenant->websiteSetting,   // ថ្មី
            'heroImages' => $tenant->mediaImages()->where('type', 'hero_banner')->orderBy('display_order')->get(), // ថ្មី
        ]);
    }

    /**
     * Frontend poll endpoint - check payment status (auto-verify via Bakong API)
     */
    public function checkStatus(Request $request, string $slug, SubscriptionPaymentRequest $paymentRequest)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        abort_unless($paymentRequest->tenant_id === $tenant->id, 403);

        if ($paymentRequest->status === 'pending' && $this->bakongService->isAutoVerifyEnabled($tenant)) {
            $verified = $this->bakongService->verifyTransaction($tenant, $paymentRequest->khqr_md5);

            if ($verified) {
                $this->activateSubscription($paymentRequest, 'bakong_api');
            }
        }

        return response()->json(['status' => $paymentRequest->fresh()->status]);
    }

    /**
     * ការ activate subscription ពិតប្រាកដ (ត្រូវហៅតែម្តងគត់ ពេល verify ជោគជ័យ)
     */
    protected function activateSubscription(SubscriptionPaymentRequest $paymentRequest, string $method): void
    {
        $plan = $paymentRequest->plan;

        $paymentRequest->update([
            'status' => 'verified',
            'verified_method' => $method,
            'verified_at' => now(),
        ]);

        $subscription = \App\Models\MemberSubscription::create([
            'member_id' => $paymentRequest->member_id,
            'membership_plan_id' => $paymentRequest->membership_plan_id,
            'promotion_id' => $paymentRequest->promotion_id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays($plan->duration_days ?? 30)->toDateString(),
            'final_price' => $paymentRequest->amount,
            'status' => 'active',
        ]);

        \App\Models\Payment::create([
            'tenant_id' => $paymentRequest->tenant_id,
            'member_id' => $paymentRequest->member_id,
            'amount' => $paymentRequest->amount,
            'method' => 'bakong_khqr',
            'reference_type' => SubscriptionPaymentRequest::class,
            'reference_id' => $paymentRequest->id,
        ]);
    }
}