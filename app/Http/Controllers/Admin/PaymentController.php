<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassBooking;
use App\Models\ClassOrder;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index()
    {
        // Summary stats for the cards row above the table. Kept separate
        // from the paginated query below since these totals must reflect
        // ALL payments, not just the current page of 20.
        $stats = [
            'total_revenue' => Payment::whereNull('refunded_at')->sum('amount'),
            'total_refunded' => Payment::whereNotNull('refunded_at')->sum('amount'),
            'this_month_revenue' => Payment::whereNull('refunded_at')
                ->whereMonth('paid_at', now()->month)
                ->whereYear('paid_at', now()->year)
                ->sum('amount'),
        ];

        return inertia('Admin/Payments/Index', [
            'payments' => Payment::with(['member', 'refundedBy'])->latest('paid_at')->paginate(20),
            'stats' => $stats,
        ]);
    }

    public function create()
    {
        return inertia('Admin/Payments/Create', [
            'members' => Member::orderBy('name')->get(['id', 'name']),
            'subscriptions' => MemberSubscription::with('membershipPlan:id,name')
                ->get(['id', 'member_id', 'membership_plan_id', 'final_price', 'status', 'start_date', 'end_date']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'member_id' => 'required|exists:members,id',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,aba_payway,bakong_khqr',
            'paid_at' => 'required|date',
            'reference_id' => 'nullable|exists:member_subscriptions,id',
        ]);

        $member = Member::findOrFail($validated['member_id']);

        Payment::create([
            'tenant_id' => $member->tenant_id,
            'member_id' => $validated['member_id'],
            'amount' => $validated['amount'],
            'method' => $validated['method'],
            'paid_at' => $validated['paid_at'],
            'reference_type' => $validated['reference_id'] ? MemberSubscription::class : null,
            'reference_id' => $validated['reference_id'] ?? null,
        ]);

        return redirect()->route('dashboard.payments.index')->with('success', 'ការទូទាត់ត្រូវបានកត់ត្រាជោគជ័យ');
    }

    /**
     * Manual refund — admin-triggered only (no auto-refund flow exists).
     * Marks the payment as refunded for bookkeeping; does not touch any
     * booking/subscription state, since refund reasons vary case-by-case.
     */

    public function refund(Request $request, Payment $payment)
    {
        abort_if($payment->refunded_at, 400, 'ការទូទាត់នេះត្រូវបាន refund រួចហើយ');

        $validated = $request->validate([
            'refund_note' => 'nullable|string|max:255',
        ]);

        $unenrolledClasses = [];

        DB::transaction(function () use ($payment, $validated, $request, &$unenrolledClasses) {
            $payment->update([
                'refunded_at' => now(),
                'refunded_by' => $request->user()->id,
                'refund_note' => $validated['refund_note'] ?? null,
            ]);

            // ប្រសិនបើ payment នេះមកពី paid class checkout —
            // ដកចេញពី class ដែលទាក់ទងផងដែរ ព្រោះលុយត្រូវបានសងវិញ
            if ($payment->reference_type === ClassOrder::class) {
                $order = ClassOrder::with('items.gymClass')->find($payment->reference_id);

                if ($order) {
                    foreach ($order->items as $item) {
                        $deleted = ClassBooking::where('member_id', $order->member_id)
                            ->where('class_id', $item->class_id)
                            ->delete();

                        if ($deleted && $item->gymClass) {
                            $unenrolledClasses[] = $item->gymClass->name;
                        }
                    }

                    $order->update(['status' => 'refunded']);
                }
            }
        });

        $message = 'ការទូទាត់ត្រូវបាន refund ជោគជ័យ';
        if (!empty($unenrolledClasses)) {
            $message .= ' — សមាជិកត្រូវបានដកចេញពី: ' . implode(', ', $unenrolledClasses);
        }

        return back()->with('success', $message);
    }
}