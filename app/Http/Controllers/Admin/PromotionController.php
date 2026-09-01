<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MembershipPlan;
use App\Models\Promotion;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Promotion::class);

        // Eager-load the plan so the frontend can show its name/price
        // without triggering a lazy-load per row (N+1).
        $promotions = Promotion::with('applicablePlan')->latest()->get();

        // Plans that already have a *specific* promotion tied to them
        // (applicable_plan_id set) — used to compute which plans still
        // have no discount yet, for the quick-add section below the table.
        // Promotions with a null applicable_plan_id apply to ALL plans and
        // don't "claim" any single plan, so they're excluded from this set.
        $linkedPlanIds = $promotions->pluck('applicable_plan_id')->filter()->unique();

        $availablePlans = MembershipPlan::whereNotIn('id', $linkedPlanIds)->get();

        return inertia('Admin/Promotions/Index', [
            'promotions' => $promotions,
            'availablePlans' => $availablePlans,
        ]);
    }

    public function create(Request $request)
    {
        $this->authorize('create', Promotion::class);

        return inertia('Admin/Promotions/Create', [
            'membershipPlans' => MembershipPlan::all(),
            // Pre-select a plan when arriving from the "+ Add Discount"
            // quick-action on a specific plan card (Index.vue passes ?plan_id=).
            'preselectedPlanId' => $request->integer('plan_id') ?: null,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Promotion::class);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'applicable_plan_id' => 'nullable|exists:membership_plans,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'active' => 'boolean',
        ]);

        Promotion::create([
            'tenant_id' => auth()->user()->tenant_id,
            ...$validated,
        ]);

        return redirect()->route('dashboard.promotions.index')->with('success', 'Promotion created.');
    }

    public function edit(Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        return inertia('Admin/Promotions/Edit', [
            'promotion' => $promotion,
            'membershipPlans' => MembershipPlan::all(),
        ]);
    }

    public function update(Request $request, Promotion $promotion)
    {
        $this->authorize('update', $promotion);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'active' => 'boolean',
        ]);

        $promotion->update($validated);

        return back()->with('success', 'Promotion updated.');
    }

    public function destroy(Promotion $promotion)
    {
        $this->authorize('delete', $promotion);

        $promotion->delete();

        return back()->with('success', 'Promotion removed.');
    }

    public function show(Promotion $promotion)
    {
        $this->authorize('view', $promotion);

        return inertia('Admin/Promotions/Show', ['promotion' => $promotion]);
    }
}