<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Models\TrainerAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TrainerAttendanceController extends Controller
{
    private const GRACE_MINUTES = 15;

    public function index(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : today();
        $isToday = $date->isToday();

        $trainers = Trainer::orderBy('name')->get(['id', 'name', 'photo_url', 'specialty', 'shift_start_time']);

        $dayAttendance = TrainerAttendance::whereDate('checked_in_at', $date)
            ->with('trainer:id,name,photo_url,shift_start_time')
            ->orderByDesc('checked_in_at')
            ->get()
            ->map(function ($record) {
                $record->is_late = $this->isLate($record);
                return $record;
            });

        $activeByTrainer = $isToday
            ? $dayAttendance->whereNull('checked_out_at')->keyBy('trainer_id')
            : collect();

        return inertia('Admin/TrainerAttendance/Index', [
            'trainers' => $trainers,
            'todayAttendance' => $dayAttendance->values(),
            'activeTrainerIds' => $activeByTrainer->keys()->values(),
            'presentCount' => $dayAttendance->pluck('trainer_id')->unique()->count(),
            'totalTrainers' => $trainers->count(),
            'selectedDate' => $date->format('Y-m-d'),
            'isToday' => $isToday,
        ]);
    }

    private function isLate(TrainerAttendance $record): bool
    {
        $shiftStart = $record->trainer?->shift_start_time;

        if (! $shiftStart) {
            return false;
        }

        $expected = Carbon::parse($record->checked_in_at->format('Y-m-d') . ' ' . $shiftStart)
            ->addMinutes(self::GRACE_MINUTES);

        return $record->checked_in_at->gt($expected);
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'trainer_id' => 'required|exists:trainers,id',
        ]);

        $alreadyActive = TrainerAttendance::where('trainer_id', $validated['trainer_id'])
            ->whereDate('checked_in_at', today())
            ->whereNull('checked_out_at')
            ->exists();

        if ($alreadyActive) {
            return back()->withErrors(['trainer_id' => 'គ្រូបង្វឹកនេះកំពុងបំពេញការងារនៅឡើយ']);
        }

        TrainerAttendance::create([
            'tenant_id' => $request->user()->tenant_id,
            'trainer_id' => $validated['trainer_id'],
            'checked_in_at' => now(),
            'recorded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'បាន check-in ជោគជ័យ');
    }

    public function checkOut(Request $request, TrainerAttendance $attendance)
    {
        if ($attendance->checked_out_at) {
            return back()->withErrors(['attendance' => 'បាន check-out រួចហើយ']);
        }

        $attendance->update(['checked_out_at' => now()]);

        return back()->with('success', 'បាន check-out ជោគជ័យ');
    }

    public function destroy(TrainerAttendance $attendance)
    {
        $attendance->delete();

        return back()->with('success', 'កំណត់ត្រាត្រូវបានលុប');
    }

    public function scanPage(Request $request)
    {
        $trainers = Trainer::orderBy('name')->get(['id', 'name', 'photo_url']);

        $activeTrainerIds = TrainerAttendance::whereDate('checked_in_at', today())
            ->whereNull('checked_out_at')
            ->pluck('trainer_id');

        return inertia('Admin/TrainerAttendance/Scan', [
            'trainers' => $trainers,
            'activeTrainerIds' => $activeTrainerIds,
        ]);
    }

    public function scan(Request $request)
    {
        $validated = $request->validate(['qr_token' => 'required|string']);

        $trainer = Trainer::where('qr_token', $validated['qr_token'])->first();

        if (! $trainer) {
            return back()->withErrors(['qr_token' => 'QR Code មិនត្រឹមត្រូវ']);
        }

        $activeRecord = TrainerAttendance::where('trainer_id', $trainer->id)
            ->whereDate('checked_in_at', today())
            ->whereNull('checked_out_at')
            ->first();

        if ($activeRecord) {
            $activeRecord->update(['checked_out_at' => now()]);
            $message = $trainer->name . ' បាន check-out';
        } else {
            TrainerAttendance::create([
                'tenant_id' => $request->user()->tenant_id,
                'trainer_id' => $trainer->id,
                'checked_in_at' => now(),
                'recorded_by' => $request->user()->id,
            ]);
            $message = $trainer->name . ' បាន check-in';
        }

        return back()->with('success', $message);
    }

    public function toggleByTrainer(Request $request)
    {
        $validated = $request->validate(['trainer_id' => 'required|exists:trainers,id']);

        $trainer = Trainer::findOrFail($validated['trainer_id']);

        $activeRecord = TrainerAttendance::where('trainer_id', $trainer->id)
            ->whereDate('checked_in_at', today())
            ->whereNull('checked_out_at')
            ->first();

        if ($activeRecord) {
            $activeRecord->update(['checked_out_at' => now()]);
            $message = $trainer->name . ' បាន check-out';
        } else {
            TrainerAttendance::create([
                'tenant_id' => $request->user()->tenant_id,
                'trainer_id' => $trainer->id,
                'checked_in_at' => now(),
                'recorded_by' => $request->user()->id,
            ]);
            $message = $trainer->name . ' បាន check-in';
        }

        return back()->with('success', $message);
    }

    public function export(Request $request)
    {
        $date = $request->query('date')
            ? Carbon::parse($request->query('date'))->startOfDay()
            : today();

        $records = TrainerAttendance::whereDate('checked_in_at', $date)
            ->with('trainer:id,name,shift_start_time')
            ->orderBy('checked_in_at')
            ->get();

        $filename = 'trainer-attendance-' . $date->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Trainer', 'Check-in', 'Check-out', 'Duration (min)', 'Status', 'Late']);

            foreach ($records as $record) {
                $durationMin = $record->checked_out_at
                    ? $record->checked_in_at->diffInMinutes($record->checked_out_at)
                    : '—';

                fputcsv($handle, [
                    $record->trainer->name ?? '—',
                    $record->checked_in_at->format('Y-m-d H:i'),
                    $record->checked_out_at?->format('Y-m-d H:i') ?? '—',
                    $durationMin,
                    $record->checked_out_at ? 'Finished' : 'Working',
                    $this->isLate($record) ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }
}