<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Trainer;

class EnsureStaffAccess
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user()) {
            return $next($request);
        }

        $trainerId = session('trainer_staff_id');
        if ($trainerId && Trainer::withoutGlobalScopes()->find($trainerId)) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}