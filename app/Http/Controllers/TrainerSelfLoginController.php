<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use Illuminate\Http\Request;

class TrainerSelfLoginController extends Controller
{
    public function login(Request $request, string $token)
    {
        $trainer = Trainer::withoutGlobalScopes()->where('self_service_token', $token)->first();

        abort_unless($trainer, 404, 'Invalid or expired link.');

        $request->session()->regenerate();
        $request->session()->put('trainer_staff_id', $trainer->id);

        return redirect()->route('my.staff.index');
    }
}