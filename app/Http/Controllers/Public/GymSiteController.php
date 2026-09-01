<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Notification;
use Illuminate\Http\Request;

class GymSiteController extends Controller
{

    public function login(string $slug)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])
            ->where('slug', $slug)
            ->firstOrFail();

        return inertia('Client/MemberLogin', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
        ]);
}
    public function home(string $slug)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])
            ->where('slug', $slug)
            ->firstOrFail();

        return inertia('Client/GymHome', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
            'heroImages' => $tenant->mediaImages()->where('type', 'hero_banner')->orderBy('display_order')->get(),
            'gallery' => $tenant->mediaImages()->where('type', 'gallery')->orderBy('display_order')->get(),
            'plans' => $tenant->membershipPlans,
            'activePromotions' => $tenant->promotions()->currentlyLive()->get(),
            'trainers' => $tenant->trainers,
            'classes' => $tenant->classes()->with('trainer:id,name')->orderBy('schedule_day')->orderBy('start_time')->get()->each->append('spots_left'),
        ]);
    }

    public function pricing(string $slug)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])->where('slug', $slug)->firstOrFail();

        return inertia('Client/GymPricing', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
            'plans' => $tenant->membershipPlans,
            'activePromotions' => $tenant->promotions()->currentlyLive()->get(),
        ]);
    }

    public function trainers(string $slug)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])->where('slug', $slug)->firstOrFail();

        return inertia('Client/GymTrainers', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
            'trainers' => $tenant->trainers,
        ]);
    }

    public function trainerShow(string $slug, int $trainerId)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Scoped to this tenant so a trainer ID from another gym can't be viewed here.
        $trainer = $tenant->trainers()->findOrFail($trainerId);

        $classes = $tenant->classes()
            ->where('trainer_id', $trainer->id)
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->get()
            ->each->append('spots_left');

        return inertia('Client/GymTrainerDetail', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
            'trainer' => $trainer,
            'classes' => $classes,
        ]);
    }

    public function classes(Request $request, string $slug)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])->where('slug', $slug)->firstOrFail();

        $bookedClassIds = [];
        $user = $request->user();
        if ($user && $user->role === 'member') {
            $member = \App\Models\Member::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('tenant_id', $tenant->id)
                ->first();
            if ($member) {
                $bookedClassIds = $member->classBookings()->pluck('class_id')->all();
            }
        }

        

        return inertia('Client/GymClasses', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
            'classes' => $tenant->classes()->with('trainer:id,name')->orderBy('schedule_day')->orderBy('start_time')->get()->each->append('spots_left'),
            'bookedClassIds' => $bookedClassIds,
            'isLoggedInMember' => $user && $user->role === 'member',
        ]);
    }

    public function classShow(Request $request, string $slug, int $classId)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])
            ->where('slug', $slug)
            ->firstOrFail();

        // Scoped to this tenant so a class ID from another gym can't be viewed here.
        $gymClass = $tenant->classes()
            ->with('trainer:id,name,specialty,photo_url')
            ->findOrFail($classId);
        $gymClass->append('spots_left');

        $bookedClassIds = [];
        $user = $request->user();
        if ($user && $user->role === 'member') {
            $member = \App\Models\Member::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->where('tenant_id', $tenant->id)
                ->first();
            if ($member) {
                $bookedClassIds = $member->classBookings()->pluck('class_id')->all();
            }
        }

        return inertia('Client/GymClassDetail', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
            'gymClass' => $gymClass,
            'bookedClassIds' => $bookedClassIds,
            'isLoggedInMember' => $user && $user->role === 'member',
        ]);
    }

    public function gallery(string $slug)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])->where('slug', $slug)->firstOrFail();

        return inertia('Client/GymGallery', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
            'gallery' => $tenant->mediaImages()->where('type', 'gallery')->orderBy('display_order')->get(),
        ]);
    }

    public function contact(string $slug)
    {
        $tenant = Tenant::with(['websiteSetting.logoImage', 'websiteSetting.heroBannerImage'])->where('slug', $slug)->firstOrFail();

        return inertia('Client/GymContact', [
            'tenant' => $tenant,
            'settings' => $tenant->websiteSetting,
        ]);
    }

    public function storeInquiry(Request $request, string $slug)
    {
        $tenant = Tenant::where('slug', $slug)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
        ]);

        $tenant->members()->create([
            ...$validated,
            'joined_date' => now(),
        ]);

        Notification::create([
            'tenant_id' => $tenant->id,
            'type' => 'new_inquiry',
            'title' => 'មានអ្នកចាប់អារម្មណ៍ថ្មី',
            'message' => "{$validated['name']} ({$validated['phone']}) បានផ្ញើសំណើទំនាក់ទំនង",
            'link' => '/dashboard/members',
        ]);

        return back()->with('success', 'Thanks! The gym will contact you soon.');
    }
}