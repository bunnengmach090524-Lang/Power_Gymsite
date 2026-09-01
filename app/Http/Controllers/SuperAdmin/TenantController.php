<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index(Request $request)
    {
        $tenants = Tenant::query()
            ->withCount('members')
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%"))
            ->when($request->status, fn ($q, $status) => $q->where('subscription_status', $status))
            ->latest()
            ->paginate(20);

        return inertia('SuperAdmin/Tenants/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Tenant $tenant)
    {
        return inertia('SuperAdmin/Tenants/Show', [
            'tenant' => $tenant->load('users', 'websiteSetting'),
            'stats' => [
                'memberCount' => $tenant->members()->count(),
                'monthlyRevenue' => $tenant->payments()->whereMonth('paid_at', now()->month)->sum('amount'),
            ],
        ]);
    }

    public function suspend(Tenant $tenant)
    {
        $tenant->update(['subscription_status' => 'expired']);

        return back()->with('success', "{$tenant->name} has been suspended.");
    }

    public function activate(Tenant $tenant)
    {
        $tenant->update(['subscription_status' => 'active']);

        return back()->with('success', "{$tenant->name} has been activated.");
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('superadmin.tenants.index');
    }
}