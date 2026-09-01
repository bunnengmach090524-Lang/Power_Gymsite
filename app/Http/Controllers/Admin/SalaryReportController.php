<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\SalaryPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryReportController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $month = $request->input('month', now()->format('Y-m'));
        $range = $request->input('range', 'month'); // month | 6months | year
        $compare = $request->input('compare', 'prior'); // prior | yoy

        $monthsBack = match ($range) {
            '6months' => 6,
            'year' => 12,
            default => 1,
        };

        $anchorStart = Carbon::parse($month . '-01')->startOfMonth();
        $periodEnd = $anchorStart->copy()->endOfMonth();
        $periodStart = $anchorStart->copy()->subMonths($monthsBack - 1)->startOfMonth();

        if ($compare === 'yoy') {
            $prevStart = $periodStart->copy()->subYear();
            $prevEnd = $periodEnd->copy()->subYear();
        } else {
            $prevEnd = $periodStart->copy()->subDay()->endOfMonth();
            $prevStart = $prevEnd->copy()->subMonths($monthsBack - 1)->startOfMonth();
        }

        $summary = $this->summaryForRange($tenantId, $periodStart, $periodEnd);
        $prevSummary = $this->summaryForRange($tenantId, $prevStart, $prevEnd);

        $summary['revenue_growth'] = $this->growthPercent($prevSummary['revenue'], $summary['revenue']);
        $summary['salary_growth'] = $this->growthPercent($prevSummary['total_salary'], $summary['total_salary']);
        $summary['net_growth'] = $this->growthPercent($prevSummary['net'], $summary['net']);

        // Per-staff breakdown across the whole selected range, each with
        // its individual salary payment records for drill-down.
        $salaryPayments = SalaryPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('period_start', [$periodStart, $periodEnd])
                    ->orWhereBetween('period_end', [$periodStart, $periodEnd]);
            })
            ->with('staffProfile')
            ->orderByDesc('period_start')
            ->get();

        $byStaff = $salaryPayments
            ->groupBy('staff_profile_id')
            ->map(function ($group) {
                $profile = $group->first()->staffProfile;

                return [
                    'staff_profile_id' => $profile?->id,
                    'name' => $profile?->name ?? 'Unknown',
                    'photo_url' => $profile?->photo_url,
                    'position' => $profile?->position,
                    'total' => (float) $group->sum('total'),
                    'status' => $group->contains('status', 'pending') ? 'pending' : 'paid',
                    'payments' => $group->map(fn ($p) => [
                        'id' => $p->id,
                        'period_start' => $p->period_start->toDateString(),
                        'period_end' => $p->period_end->toDateString(),
                        'total' => (float) $p->total,
                        'status' => $p->status,
                    ])->values(),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $staffCount = $byStaff->count();
        $summary['staff_count'] = $staffCount;
        $summary['avg_per_staff'] = $staffCount > 0 ? round($summary['total_salary'] / $staffCount, 2) : 0;
        $summary['top_earner'] = $byStaff->first();
        $summary['paid_count'] = $salaryPayments->where('status', 'paid')->count();
        $summary['pending_count'] = $salaryPayments->where('status', 'pending')->count();

        // Old pending alert: unpaid salary payments older than 30 days,
        // independent of the selected report period — this should always
        // surface so admins don't lose track of a forgotten payment.
        $oldPending = SalaryPayment::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('created_at', '<', now()->subDays(30))
            ->with('staffProfile')
            ->orderBy('created_at')
            ->get();

        $oldPendingAlert = $oldPending->isNotEmpty() ? [
            'count' => $oldPending->count(),
            'oldest_name' => $oldPending->first()->staffProfile?->name,
            'oldest_days' => (int) $oldPending->first()->created_at->diffInDays(now()),
            'total' => (float) $oldPending->sum('total'),
        ] : null;

        // Trend: one point per month across the selected range.
        $trend = [];
        for ($i = $monthsBack - 1; $i >= 0; $i--) {
            $cursor = $anchorStart->copy()->subMonths($i);
            $mStart = $cursor->copy()->startOfMonth();
            $mEnd = $cursor->copy()->endOfMonth();

            $mRevenue = $this->revenueForRange($tenantId, $mStart, $mEnd);
            $mSalary = (float) SalaryPayment::where('tenant_id', $tenantId)
                ->where(function ($q) use ($mStart, $mEnd) {
                    $q->whereBetween('period_start', [$mStart, $mEnd])
                        ->orWhereBetween('period_end', [$mStart, $mEnd]);
                })
                ->sum('total');

            $trend[] = [
                'month' => $cursor->format('Y-m'),
                'revenue' => $mRevenue,
                'salary' => $mSalary,
            ];
        }

        return inertia('Admin/Salary/Report', [
            'month' => $month,
            'range' => $range,
            'compare' => $compare,
            'summary' => $summary,
            'byStaff' => $byStaff,
            'trend' => $trend,
            'oldPendingAlert' => $oldPendingAlert,
        ]);
    }

    public function export(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $month = $request->input('month', now()->format('Y-m'));
        $range = $request->input('range', 'month');
        $monthsBack = match ($range) {
            '6months' => 6,
            'year' => 12,
            default => 1,
        };

        $anchorStart = Carbon::parse($month . '-01')->startOfMonth();
        $periodEnd = $anchorStart->copy()->endOfMonth();
        $periodStart = $anchorStart->copy()->subMonths($monthsBack - 1)->startOfMonth();

        $payments = SalaryPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('period_start', [$periodStart, $periodEnd])
                    ->orWhereBetween('period_end', [$periodStart, $periodEnd]);
            })
            ->with('staffProfile')
            ->get();

        $filename = "salary-report-{$periodStart->format('Y-m')}-to-{$periodEnd->format('Y-m')}.csv";

        return response()->streamDownload(function () use ($payments) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Staff Name', 'Position', 'Period Start', 'Period End', 'Base', 'Bonus', 'Deduction', 'Total', 'Status']);

            foreach ($payments as $p) {
                fputcsv($out, [
                    $p->staffProfile?->name,
                    $p->staffProfile?->position,
                    $p->period_start->toDateString(),
                    $p->period_end->toDateString(),
                    $p->base_amount,
                    $p->bonus,
                    $p->deduction,
                    $p->total,
                    $p->status,
                ]);
            }

            fclose($out);
        }, $filename);
    }

    private function summaryForRange(string $tenantId, Carbon $start, Carbon $end): array
    {
        $revenue = $this->revenueForRange($tenantId, $start, $end);
        $refunds = (float) Payment::where('tenant_id', $tenantId)
            ->whereBetween('refunded_at', [$start, $end])
            ->sum('amount');

        $salaryPayments = SalaryPayment::where('tenant_id', $tenantId)
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('period_start', [$start, $end])
                    ->orWhereBetween('period_end', [$start, $end]);
            })
            ->get();

        $totalSalary = (float) $salaryPayments->sum('total');
        $paidSalary = (float) $salaryPayments->where('status', 'paid')->sum('total');
        $pendingSalary = (float) $salaryPayments->where('status', 'pending')->sum('total');
        $ratio = $revenue > 0 ? round(($totalSalary / $revenue) * 100, 1) : null;
        $net = $revenue - $totalSalary;

        return [
            'revenue' => $revenue,
            'refunds' => $refunds,
            'total_salary' => $totalSalary,
            'paid_salary' => $paidSalary,
            'pending_salary' => $pendingSalary,
            'ratio' => $ratio,
            'net' => $net,
        ];
    }

    private function revenueForRange(string $tenantId, Carbon $start, Carbon $end): float
    {
        return (float) Payment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$start, $end])
            ->whereNull('refunded_at')
            ->sum('amount');
    }

    private function growthPercent(float $prev, float $current): ?float
    {
        if ($prev == 0.0) {
            return $current > 0 ? 100.0 : null;
        }

        return round((($current - $prev) / abs($prev)) * 100, 1);
    }
}