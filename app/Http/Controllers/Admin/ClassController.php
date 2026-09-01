<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GymClass;
use App\Models\Trainer;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', GymClass::class);

        return inertia('Admin/Classes/Index', [
            'classes' => GymClass::with('trainer', 'bookings')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', GymClass::class);

        return inertia('Admin/Classes/Create', [
            'trainers' => Trainer::all(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', GymClass::class);

        $validated = $request->validate([
            'trainer_id' => 'nullable|exists:trainers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule_day' => 'required|in:mon,tue,wed,thu,fri,sat,sun',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'capacity' => 'required|integer|min:1',
            // null/0 = free class included in subscription; > 0 = paid add-on
            'price' => 'nullable|numeric|min:0|max:9999.99',
            'image' => 'nullable|image|max:3072',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_url'] = $request->file('image')->store('classes', config('filesystems.media_disk', 'public'));
        }
        unset($validated['image']);

        GymClass::create([
            'tenant_id' => auth()->user()->tenant_id,
            ...$validated,
        ]);

        return redirect()->route('dashboard.classes.index')->with('success', 'Class created.');
    }

    public function edit(GymClass $class)
    {
        $this->authorize('update', $class);

        return inertia('Admin/Classes/Edit', [
            // NOTE: prop key can't be "class" — Vue treats "class" as the
            // reserved HTML class-list attribute, not a normal prop, and
            // silently stringifies the object into its own attribute keys.
            'gymClass' => $class,
            'trainers' => Trainer::all(),
        ]);
    }

    public function update(Request $request, GymClass $class)
    {
        $this->authorize('update', $class);

        $validated = $request->validate([
            'trainer_id' => 'nullable|exists:trainers,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'schedule_day' => 'required|in:mon,tue,wed,thu,fri,sat,sun',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'capacity' => 'required|integer|min:1',
            'price' => 'nullable|numeric|min:0|max:9999.99',
            'image' => 'nullable|image|max:3072',
        ]);

        if ($request->hasFile('image')) {
            \App\Support\MediaUrl::delete($class->getRawOriginal('image_url'));
            $validated['image_url'] = $request->file('image')->store('classes', config('filesystems.media_disk', 'public'));
        }
        unset($validated['image']);

        $class->update($validated);

        return back()->with('success', 'Class updated.');
    }

    public function destroy(GymClass $class)
    {
        $this->authorize('delete', $class);

        $class->delete();

        return back()->with('success', 'Class removed.');
    }

    public function show(GymClass $class)
    {
        $this->authorize('view', $class);

        return inertia('Admin/Classes/Show', ['class' => $class->load('trainer', 'bookings.member')]);
    }
}