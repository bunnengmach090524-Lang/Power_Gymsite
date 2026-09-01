<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use Illuminate\Http\Request;

class MembershipPlanController extends Controller
{
    public function index()
    {
        $plans = MembershipPlan::withCount([
            'memberSubscriptions as active_subscribers_count' => fn ($q) => $q->where('status', 'active'),
        ])->orderBy('price')->get();

        return inertia('Admin/Plans/Index', [
            'plans' => $plans,
            'stats' => [
                'totalPlans' => $plans->count(),
                'avgPrice' => $plans->count() ? round($plans->avg('price'), 2) : 0,
                'totalSubscribers' => $plans->sum('active_subscribers_count'),
            ],
        ]);
    }

    public function create()
    {
        return inertia('Admin/Plans/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:99999.99',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        MembershipPlan::create([
            'tenant_id' => auth()->user()->tenant_id,
            ...$validated,
        ]);

        return redirect()->route('dashboard.plans.index')->with('success', 'Plan created.');
    }

    public function edit(MembershipPlan $plan)
    {
        return inertia('Admin/Plans/Edit', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, MembershipPlan $plan)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0|max:99999.99',
            'duration_days' => 'required|integer|min:1',
            'description' => 'nullable|string|max:1000',
        ]);

        $plan->update($validated);

        return redirect()->route('dashboard.plans.index')->with('success', 'Plan updated.');
    }

    public function destroy(MembershipPlan $plan)
    {
        $plan->delete();

        return back()->with('success', 'Plan deleted.');
    }
}