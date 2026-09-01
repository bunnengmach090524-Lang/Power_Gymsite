<?php

namespace App\Services;

use App\Models\StaffProfile;
use App\Models\StaffAttendance;
use App\Models\ClassBooking;
use Carbon\Carbon;

class SalaryCalculationService
{
    /**
     * Calculate a suggested salary breakdown for a staff profile over a
     * period. Returned as a preview — the admin can adjust before saving.
     */
    public function calculate(StaffProfile $staff, Carbon $start, Carbon $end): array
    {
        $baseAmount = $this->calculateBaseAmount($staff, $start, $end);
        $commission = $this->calculateCommission($staff, $start, $end);
        $hoursWorked = $staff->salary_type === 'hourly' ? $this->hoursWorked($staff, $start, $end) : null;

        return [
            'base_amount' => round($baseAmount, 2),
            'commission' => round($commission['amount'], 2),
            'commission_note' => $commission['note'],
            'hours_worked' => $hoursWorked !== null ? round($hoursWorked, 2) : null,
            'suggested_total' => round($baseAmount + $commission['amount'], 2),
        ];
    }

    protected function calculateBaseAmount(StaffProfile $staff, Carbon $start, Carbon $end): float
    {
        return match ($staff->salary_type) {
            'fixed', 'fixed_commission' => (float) $staff->base_salary,
            'hourly' => $this->hoursWorked($staff, $start, $end) * (float) $staff->hourly_rate,
            default => 0.0,
        };
    }

    protected function hoursWorked(StaffProfile $staff, Carbon $start, Carbon $end): float
    {
        $attendances = StaffAttendance::withoutGlobalScopes()
            ->where('staff_profile_id', $staff->id)
            ->whereNotNull('checked_out_at')
            ->whereBetween('checked_in_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->get();

        $totalMinutes = $attendances->sum(
            fn ($a) => $a->checked_in_at->diffInMinutes($a->checked_out_at)
        );

        return $totalMinutes / 60;
    }

    /**
     * Commission auto-calculates only for 'class_booking', the only source
     * with a clear trainer -> class -> booking chain in the data model.
     * 'pt_session' and 'payment_referred' have no tracking table yet, so
     * they return 0 with a note telling the frontend to prompt manual entry.
     */
    protected function calculateCommission(StaffProfile $staff, Carbon $start, Carbon $end): array
    {
        if (! in_array($staff->salary_type, ['commission', 'fixed_commission'])) {
            return ['amount' => 0.0, 'note' => null];
        }

        if ($staff->commission_source === 'class_booking' && $staff->payable_type === 'trainer') {
            $revenue = ClassBooking::query()
                ->whereHas('gymClass', fn ($q) => $q->where('trainer_id', $staff->payable_id))
                ->whereBetween('booked_at', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->with('gymClass')
                ->get()
                ->sum(fn ($booking) => (float) ($booking->gymClass->price ?? 0));

            $amount = $revenue * ((float) $staff->commission_percent / 100);

            return ['amount' => $amount, 'note' => null];
        }

        return [
            'amount' => 0.0,
            'note' => 'manual_required', // frontend shows a hint: "enter commission manually"
        ];
    }
}