<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SalaryPayment;
use App\Models\StaffProfile;
use App\Services\SalaryCalculationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SalaryController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $profiles = StaffProfile::where('tenant_id', $tenantId)
            ->where('active', true)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'photo_url' => $p->photo_url,
                'position' => $p->position,
                'salary_type' => $p->salary_type,
                'commission_source' => $p->commission_source,
                'payable_type' => $p->payable_type,
            ]);

        $payments = SalaryPayment::with('staffProfile')
            ->where('tenant_id', $tenantId)
            ->latest('period_start')
            ->get()
            ->map(fn ($sp) => [
                'id' => $sp->id,
                'staff_name' => $sp->staffProfile?->name,
                'period_start' => $sp->period_start->toDateString(),
                'period_end' => $sp->period_end->toDateString(),
                'base_amount' => $sp->base_amount,
                'bonus' => $sp->bonus,
                'deduction' => $sp->deduction,
                'total' => $sp->total,
                'status' => $sp->status,
                'paid_at' => $sp->paid_at?->toDateString(),
            ]);

        return inertia('Admin/Salary/Index', [
            'profiles' => $profiles,
            'payments' => $payments,
        ]);
    }

    public function calculate(Request $request, SalaryCalculationService $calculator)
    {
        $validated = $request->validate([
            'staff_profile_id' => ['required', 'exists:staff_profiles,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);

        $staff = StaffProfile::where('tenant_id', $request->user()->tenant_id)
            ->findOrFail($validated['staff_profile_id']);

        $result = $calculator->calculate(
            $staff,
            Carbon::parse($validated['period_start']),
            Carbon::parse($validated['period_end'])
        );

        return response()->json($result);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'staff_profile_id' => ['required', 'exists:staff_profiles,id'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'base_amount' => ['required', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
        ]);

        $base = (float) $validated['base_amount'];
        $bonus = (float) ($validated['bonus'] ?? 0);
        $deduction = (float) ($validated['deduction'] ?? 0);

        SalaryPayment::create([
            'tenant_id' => $request->user()->tenant_id,
            'staff_profile_id' => $validated['staff_profile_id'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'base_amount' => $base,
            'bonus' => $bonus,
            'deduction' => $deduction,
            'total' => $base + $bonus - $deduction,
            'status' => 'pending',
        ]);

        return back()->with('success', 'ការគណនាប្រាក់ខែត្រូវបានបង្កើត');
    }

    public function markPaid(Request $request, SalaryPayment $salaryPayment)
    {
        abort_unless($salaryPayment->tenant_id === $request->user()->tenant_id, 403);

        $salaryPayment->update([
            'status' => 'paid',
            'paid_at' => now(),
            'paid_by' => $request->user()->id,
        ]);

        return back()->with('success', 'ប្រាក់ខែត្រូវបានកត់ត្រាថាបានបង់រួច');
    }

    public function destroy(Request $request, SalaryPayment $salaryPayment)
    {
        abort_unless($salaryPayment->tenant_id === $request->user()->tenant_id, 403);
        abort_if($salaryPayment->status === 'paid', 403, 'Cannot delete a paid salary record.');

        $salaryPayment->delete();

        return back()->with('success', 'ការគណនាប្រាក់ខែត្រូវបានលុប');
    }
}