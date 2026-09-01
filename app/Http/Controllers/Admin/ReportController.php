<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Member;
use App\Models\MemberSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected array $methodLabels = [
        'cash' => 'សាច់ប្រាក់',
        'aba_payway' => 'ABA PayWay',
        'bakong_khqr' => 'Bakong KHQR',
    ];

    protected function dateRange(Request $request): array
    {
        $start = $request->query('start_date')
            ? Carbon::parse($request->query('start_date'))->startOfDay()
            : now()->startOfMonth();

        $end = $request->query('end_date')
            ? Carbon::parse($request->query('end_date'))->endOfDay()
            : now()->endOfDay();

        return [$start, $end];
    }

    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        [$start, $end] = $this->dateRange($request);

        $paymentsInRange = Payment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$start, $end]);

        $totalRevenue = (clone $paymentsInRange)->sum('amount');
        $totalPayments = (clone $paymentsInRange)->count();
        $newMembers = Member::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$start, $end])
            ->count();
        $avgPayment = $totalPayments > 0 ? $totalRevenue / $totalPayments : 0;

        // ចំណូលតាមកញ្ចប់សមាជិកភាព (តែ payment ដែលភ្ជាប់ជាមួយ MemberSubscription ប៉ុណ្ណោះ)
        $revenueByPlan = Payment::query()
            ->where('payments.tenant_id', $tenantId)
            ->whereBetween('payments.paid_at', [$start, $end])
            ->where('payments.reference_type', MemberSubscription::class)
            ->join('member_subscriptions', 'payments.reference_id', '=', 'member_subscriptions.id')
            ->join('membership_plans', 'member_subscriptions.membership_plan_id', '=', 'membership_plans.id')
            ->selectRaw('membership_plans.name as label, SUM(payments.amount) as total, COUNT(*) as count')
            ->groupBy('membership_plans.id', 'membership_plans.name')
            ->orderByDesc('total')
            ->get();

        // ចំណូលតាមមធ្យោបាយទូទាត់
        $revenueByMethod = (clone $paymentsInRange)
            ->selectRaw('method, SUM(amount) as total, COUNT(*) as count')
            ->groupBy('method')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                $row->label = $this->methodLabels[$row->method] ?? $row->method;
                return $row;
            });

        return inertia('Admin/Reports/Index', [
            'startDate' => $start->toDateString(),
            'endDate' => $end->toDateString(),
            'stats' => [
                'totalRevenue' => round($totalRevenue, 2),
                'totalPayments' => $totalPayments,
                'newMembers' => $newMembers,
                'avgPayment' => round($avgPayment, 2),
            ],
            'revenueByPlan' => $revenueByPlan,
            'revenueByMethod' => $revenueByMethod,
        ]);
    }

    public function export(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        [$start, $end] = $this->dateRange($request);

        $payments = Payment::with('member')
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$start, $end])
            ->orderBy('paid_at')
            ->get();

        $filename = 'revenue-report_' . $start->toDateString() . '_' . $end->toDateString() . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Member', 'Amount', 'Method']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->paid_at->toDateTimeString(),
                    $payment->member->name ?? '—',
                    $payment->amount,
                    $this->methodLabels[$payment->method] ?? $payment->method,
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}