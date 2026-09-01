<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Tenant;

class DashboardController extends Controller
{
    public function index()
    {
        return inertia('SuperAdmin/Dashboard', [
            'stats' => [
                'totalGyms' => Tenant::count(),
                'activeGyms' => Tenant::where('subscription_status', 'active')->count(),
                'trialGyms' => Tenant::where('subscription_status', 'trial')->count(),
                'expiredGyms' => Tenant::where('subscription_status', 'expired')->count(),
                'platformRevenueThisMonth' => Payment::whereMonth('paid_at', now()->month)->sum('amount'),
            ],
            'recentSignups' => Tenant::latest()->take(5)->get(['id', 'name', 'slug', 'subscription_status', 'created_at']),
        ]);
    }
}