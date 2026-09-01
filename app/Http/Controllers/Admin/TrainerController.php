<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Trainer;
use App\Services\TelegramService;
use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TrainerController extends Controller
{
    public function index(Request $request)
    {
        $trainers = Trainer::withCount('classes')->latest()->get();

        return inertia('Admin/Trainers/Index', ['trainers' => $trainers]);
    }

    public function show(Trainer $trainer)
    {
        return inertia('Admin/Trainers/Show', [
            'trainer' => $trainer->loadCount('classes'),
            'tenant' => auth()->user()->tenant->only('name', 'logo_url'),
        ]);
    }

    public function create()
    {
        return inertia('Admin/Trainers/Create');
    }

    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('trainers', 'email')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'specialty' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:3072',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo_url'] = $request->file('photo')->store('trainers', config('filesystems.media_disk', 'public'));
        }
        unset($validated['photo']);

        $request->user()->tenant->trainers()->create($validated);

        return redirect()->route('dashboard.trainers.index')->with('success', 'គ្រូបង្វឹកត្រូវបានបន្ថែម');
    }

    public function edit(Trainer $trainer)
    {
        $telegramLink = null;

        if ($trainer->telegram_link_token && ! $trainer->telegram_chat_id) {
            $botUsername = config('services.telegram.bot_username');
            $telegramLink = "https://t.me/{$botUsername}?start={$trainer->telegram_link_token}";
        }

        return inertia('Admin/Trainers/Edit', [
            'trainer' => $trainer,
            'telegramLink' => $telegramLink,
        ]);
    }

    public function update(Request $request, Trainer $trainer)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('trainers', 'email')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($trainer->id),
            ],
            'specialty' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:2000',
            'photo' => 'nullable|image|max:3072',
        ]);

        if ($request->hasFile('photo')) {
            // Fixed bug: was deleting using the resolved full URL, which
            // silently failed. Now deletes using the raw stored path.
            MediaUrl::delete($trainer->getRawOriginal('photo_url'));
            $validated['photo_url'] = $request->file('photo')->store('trainers', config('filesystems.media_disk', 'public'));
        }
        unset($validated['photo']);

        $trainer->update($validated);

        return back()->with('success', 'ព័ត៌មានគ្រូបង្វឹកត្រូវបានកែប្រែ');
    }

    public function destroy(Trainer $trainer)
    {
        if ($trainer->classes()->exists()) {
            return back()->withErrors(['trainer' => 'មិនអាចលុបគ្រូបង្វឹកនេះបានទេ ព្រោះមាន class ភ្ជាប់ជាមួយ']);
        }

        MediaUrl::delete($trainer->getRawOriginal('photo_url'));

        $trainer->delete();

        return back()->with('success', 'គ្រូបង្វឹកត្រូវបានលុប');
    }

    public function qrCode(Trainer $trainer)
    {
        if (! $trainer->qr_token) {
            $trainer->qr_token = \Illuminate\Support\Str::random(24);
            $trainer->save();
        }

        return response(
            \SimpleSoftwareIO\QrCode\Facades\QrCode::size(300)->generate($trainer->qr_token)
        )->header('Content-Type', 'image/svg+xml')
        ->header('Content-Disposition', 'inline; filename="trainer-' . $trainer->id . '-qr.svg"');
    }

    public function connectTelegram(Trainer $trainer)
    {
        $token = \Illuminate\Support\Str::random(20);
        $trainer->update(['telegram_link_token' => $token]);

        $botUsername = config('services.telegram.bot_username');
        $link = "https://t.me/{$botUsername}?start={$token}";

        return redirect()->away($link)->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    public function resendTelegramQr(Trainer $trainer, TelegramService $telegram)
    {
        if (! $trainer->telegram_chat_id) {
            return back()->withErrors(['telegram' => 'This trainer has not connected Telegram yet.']);
        }

        if (! $trainer->qr_token) {
            $trainer->update(['qr_token' => \Illuminate\Support\Str::random(24)]);
        }

        $qrUrl = route('dashboard.trainers.qr', $trainer);

        $telegram->sendMessage(
            $trainer->telegram_chat_id,
            "Here is your check-in QR code link: {$qrUrl}\n\n(Open this link and screenshot it, or ask your admin to print it.)"
        );

        return back()->with('success', 'QR code link sent via Telegram.');
    }
}