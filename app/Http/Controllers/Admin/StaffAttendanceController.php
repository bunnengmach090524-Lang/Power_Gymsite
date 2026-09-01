<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffAttendance;
use App\Models\StaffProfile;
use Carbon\Carbon;
use Illuminate\Http\Request;

class StaffAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : today();
        $isToday = $date->isToday();

        $profiles = StaffProfile::where('active', true)
            ->get(['id', 'payable_type', 'payable_id', 'position'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'photo_url' => $p->photo_url,
                'position' => $p->position,
                'payable_type' => $p->payable_type,
            ]);

        $dayAttendance = StaffAttendance::whereDate('checked_in_at', $date)
            ->with('staffProfile')
            ->orderByDesc('checked_in_at')
            ->get()
            ->map(fn ($record) => [
                'id' => $record->id,
                'staff_profile_id' => $record->staff_profile_id,
                'name' => $record->staffProfile?->name ?? 'Unknown',
                'checked_in_at' => $record->checked_in_at,
                'checked_out_at' => $record->checked_out_at,
            ]);

        $activeByProfile = $isToday
            ? $dayAttendance->whereNull('checked_out_at')->keyBy('staff_profile_id')
            : collect();

        return inertia('Admin/StaffAttendance/Index', [
            'profiles' => $profiles,
            'dayAttendance' => $dayAttendance->values(),
            'activeProfileIds' => $activeByProfile->keys()->values(),
            'presentCount' => $dayAttendance->pluck('staff_profile_id')->unique()->count(),
            'totalProfiles' => $profiles->count(),
            'selectedDate' => $date->format('Y-m-d'),
            'isToday' => $isToday,
        ]);
    }

    public function scanPage(Request $request)
    {
        $profiles = StaffProfile::where('active', true)->get(['id', 'payable_type', 'payable_id', 'position'])
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'photo_url' => $p->photo_url, 'position' => $p->position]);

        $activeProfileIds = StaffAttendance::whereDate('checked_in_at', today())
            ->whereNull('checked_out_at')
            ->pluck('staff_profile_id');

        return inertia('Admin/StaffAttendance/Scan', [
            'profiles' => $profiles,
            'activeProfileIds' => $activeProfileIds,
        ]);
    }

    public function scan(Request $request)
    {
        $validated = $request->validate(['qr_token' => 'required|string']);

        $profile = StaffProfile::where('qr_token', $validated['qr_token'])->first();

        if (! $profile) {
            return back()->withErrors(['qr_token' => 'QR Code មិនត្រឹមត្រូវ']);
        }

        $message = $this->toggle($profile, $request->user()->id);

        return back()->with('success', $message);
    }

    public function toggleByProfile(Request $request)
    {
        $validated = $request->validate(['staff_profile_id' => 'required|exists:staff_profiles,id']);

        $profile = StaffProfile::findOrFail($validated['staff_profile_id']);

        $message = $this->toggle($profile, $request->user()->id);

        return back()->with('success', $message);
    }

    private function toggle(StaffProfile $profile, int $recordedBy): string
    {
        $activeRecord = StaffAttendance::where('staff_profile_id', $profile->id)
            ->whereDate('checked_in_at', today())
            ->whereNull('checked_out_at')
            ->first();

        if ($activeRecord) {
            $activeRecord->update(['checked_out_at' => now()]);
            return $profile->name . ' បាន check-out';
        }

        StaffAttendance::create([
            'tenant_id' => $profile->tenant_id,
            'staff_profile_id' => $profile->id,
            'checked_in_at' => now(),
            'recorded_by' => $recordedBy,
        ]);

        return $profile->name . ' បាន check-in';
    }

    public function destroy(StaffAttendance $attendance)
    {
        $attendance->delete();

        return back()->with('success', 'កំណត់ត្រាត្រូវបានលុប');
    }

    public function export(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : today();

        $records = StaffAttendance::whereDate('checked_in_at', $date)
            ->with('staffProfile')
            ->orderBy('checked_in_at')
            ->get();

        $filename = 'staff-attendance-' . $date->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Staff', 'Check-in', 'Check-out', 'Duration (min)', 'Status']);

            foreach ($records as $record) {
                $durationMin = $record->checked_out_at
                    ? $record->checked_in_at->diffInMinutes($record->checked_out_at)
                    : '—';

                fputcsv($handle, [
                    $record->staffProfile?->name ?? '—',
                    $record->checked_in_at->format('Y-m-d H:i'),
                    $record->checked_out_at?->format('Y-m-d H:i') ?? '—',
                    $durationMin,
                    $record->checked_out_at ? 'Finished' : 'Working',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}