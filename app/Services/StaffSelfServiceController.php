<?php

namespace App\Http\Controllers;

use App\Models\StaffProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class StaffSelfServiceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $profile = StaffProfile::where('payable_type', 'user')
            ->where('payable_id', $user->id)
            ->first();

        if (!$profile && $user->trainer) {
            $profile = StaffProfile::where('payable_type', 'trainer')
                ->where('payable_id', $user->trainer->id)
                ->first();
        }

        if (!$profile) {
            abort(403, 'No staff profile linked to this account.');
        }

        $payments = $profile->salaryPayments()
            ->orderByDesc('period_start')
            ->get();

        $attendances = $profile->attendances()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return Inertia::render('Staff/SelfService', [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'position' => $profile->position,
                'photo_url' => $profile->photo_url,
            ],
            'payments' => $payments,
            'attendances' => $attendances,
        ]);
    }
}