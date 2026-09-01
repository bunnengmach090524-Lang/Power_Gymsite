<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CheckIn;
use App\Models\ClassAttendance;
use App\Models\ClassBooking;
use App\Models\GymClass;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ClassAttendanceController extends Controller
{
    // schedule_day code -> lowercase day name, for comparing against a picked date
    protected array $dayNameByCode = [
        'mon' => 'monday', 'tue' => 'tuesday', 'wed' => 'wednesday',
        'thu' => 'thursday', 'fri' => 'friday', 'sat' => 'saturday', 'sun' => 'sunday',
    ];

    /**
     * Roster screen: bookings for this class, on a given occurrence date,
     * with each booking's attendance status (existing or 'pending' default).
     */
    public function roster(Request $request, GymClass $class)
    {
        $this->authorize('view', $class);

        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))
            : Carbon::now();

        $isMatchingDay = strtolower($date->format('l')) === ($this->dayNameByCode[$class->schedule_day] ?? null);

        $class->load('bookings.member');

        $rows = $class->bookings->map(function (ClassBooking $booking) use ($date) {
            $existing = ClassAttendance::where('class_booking_id', $booking->id)
                ->where('occurred_on', $date->toDateString())
                ->first();

            // Non-authoritative hint only — did this member check in to the gym
            // within the class's time window on this date? Trainer still confirms;
            // this never auto-sets the final status.
            $checkedInHint = CheckIn::where('member_id', $booking->member_id)
                ->whereDate('checked_in_at', $date->toDateString())
                ->whereTime('checked_in_at', '>=', $booking->gymClass->start_time)
                ->whereTime('checked_in_at', '<=', $booking->gymClass->end_time)
                ->exists();

            return [
                'booking_id' => $booking->id,
                'member' => $booking->member?->only(['id', 'name', 'photo_url', 'phone']),
                'status' => $existing->status ?? 'pending',
                'note' => $existing->note ?? null,
                'marked_by_name' => $existing?->markedBy?->name,
                'marked_at' => $existing?->marked_at?->toIso8601String(),
                'checked_in_hint' => $checkedInHint,
            ];
        })->values();

        return inertia('Admin/Classes/Roster', [
            'gymClass' => $class->only('id', 'name', 'schedule_day', 'start_time', 'end_time'),
            'date' => $date->toDateString(),
            'isMatchingDay' => $isMatchingDay,
            'rows' => $rows,
        ]);
    }

    /**
     * Mark (or update) one booking's attendance status for a specific occurrence date.
     */
    public function mark(Request $request, GymClass $class)
    {
        $this->authorize('view', $class);

        $validated = $request->validate([
            'booking_id' => 'required|exists:class_bookings,id',
            'occurred_on' => 'required|date',
            'status' => 'required|in:pending,present,absent,permission',
            'note' => 'nullable|string|max:255',
        ]);

        // Scope through the (tenant-safe, route-bound) $class so we can never
        // touch a booking belonging to another gym/tenant.
        $booking = $class->bookings()->findOrFail($validated['booking_id']);

        ClassAttendance::updateOrCreate(
            [
                'class_booking_id' => $booking->id,
                'occurred_on' => $validated['occurred_on'],
            ],
            [
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'marked_by' => $request->user()->id,
                'marked_at' => now(),
            ]
        );

        return back()->with('success', 'វត្តមានត្រូវបានកត់ត្រា');
    }
}