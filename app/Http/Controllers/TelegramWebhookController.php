<?php

namespace App\Http\Controllers;

use App\Models\Trainer;
use App\Models\Member;
use App\Models\StaffProfile;
use App\Services\TelegramService;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request, string $secret, TelegramService $telegram)
    {
        if (! hash_equals(config('services.telegram.webhook_secret'), $secret)) {
            abort(404);
        }
        \Illuminate\Support\Facades\Log::info('Telegram webhook received', $request->all());

        $text = $request->input('message.text', '');
        $chatId = $request->input('message.chat.id');

        if (! $chatId) {
            return response()->json(['ok' => true]);
        }

        if (str_starts_with($text, '/start')) {
            return $this->handleStart($text, $chatId, $telegram);
        }

        if (str_starts_with($text, '/myqr')) {
            return $this->handleMyQr($chatId, $telegram);
        }

        return response()->json(['ok' => true]);
    }

    private function handleStart(string $text, int|string $chatId, TelegramService $telegram)
    {
        $payload = trim(substr($text, strlen('/start')));

        if ($payload === '') {
            $telegram->sendMessage($chatId, 'Please open this bot using the link provided by your gym admin.');
            return response()->json(['ok' => true]);
        }

        $trainer = Trainer::withoutGlobalScopes()->where('telegram_link_token', $payload)->first();
        if ($trainer) {
            $trainer->update(['telegram_chat_id' => (string) $chatId, 'telegram_link_token' => null]);
            $this->sendTrainerQrLink($trainer, $telegram, "✅ You're connected, {$trainer->name}!\nHere is your check-in QR code:");
            return response()->json(['ok' => true]);
        }

        $member = Member::withoutGlobalScopes()->where('telegram_link_token', $payload)->first();
        if ($member) {
            $member->update(['telegram_chat_id' => (string) $chatId, 'telegram_link_token' => null]);
            $this->sendMemberQrLink($member, $telegram, "✅ បានភ្ជាប់ជោគជ័យ {$member->name}!\nនេះជា QR check-in របស់អ្នក:");
            return response()->json(['ok' => true]);
        }

        $staff = StaffProfile::withoutGlobalScopes()->where('telegram_link_token', $payload)->first();
        if ($staff) {
            $staff->update(['telegram_chat_id' => (string) $chatId, 'telegram_link_token' => null]);
            $this->sendStaffQrLink($staff, $telegram, "✅ You're connected, {$staff->name}!\nHere is your check-in QR code:");
            return response()->json(['ok' => true]);
        }

        $telegram->sendMessage($chatId, 'This link is invalid or has expired. Please ask your gym admin for a new one.');
        return response()->json(['ok' => true]);
    }

    private function handleMyQr(int|string $chatId, TelegramService $telegram)
    {
        $chatIdStr = (string) $chatId;

        $trainer = Trainer::withoutGlobalScopes()->where('telegram_chat_id', $chatIdStr)->first();
        if ($trainer) {
            $this->sendTrainerQrLink($trainer, $telegram, 'Here is your check-in QR code:');
            return response()->json(['ok' => true]);
        }

        $member = Member::withoutGlobalScopes()->where('telegram_chat_id', $chatIdStr)->first();
        if ($member) {
            $this->sendMemberQrLink($member, $telegram, 'នេះជា QR check-in របស់អ្នក:');
            return response()->json(['ok' => true]);
        }

        $staff = StaffProfile::withoutGlobalScopes()->where('telegram_chat_id', $chatIdStr)->first();
        if ($staff) {
            $this->sendStaffQrLink($staff, $telegram, 'Here is your check-in QR code:');
            return response()->json(['ok' => true]);
        }

        $telegram->sendMessage($chatId, 'Your account is not linked yet. Please ask your gym admin for a connect link.');
        return response()->json(['ok' => true]);
    }

    private function sendTrainerQrLink(Trainer $trainer, TelegramService $telegram, string $prefix): void
    {
        if (! $trainer->qr_token) {
            $trainer->update(['qr_token' => \Illuminate\Support\Str::random(24)]);
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($trainer->qr_token)
            ->size(300)
            ->margin(10)
            ->build();

        $qrPng = $result->getString();
        $qrUrl = route('dashboard.trainers.qr', $trainer);

        $telegram->sendPhoto(
            $trainer->telegram_chat_id,
            $qrPng,
            "{$prefix}\n\n{$qrUrl}\n\nSend /myqr anytime to get this QR again."
        );
    }

    private function sendMemberQrLink(Member $member, TelegramService $telegram, string $prefix): void
    {
        if (! $member->qr_token) {
            $member->update(['qr_token' => \Illuminate\Support\Str::random(24)]);
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($member->qr_token)
            ->size(300)
            ->margin(10)
            ->build();

        $qrPng = $result->getString();
        $qrUrl = route('dashboard.members.qr', $member);

        $telegram->sendPhoto(
            $member->telegram_chat_id,
            $qrPng,
            "{$prefix}\n\n{$qrUrl}\n\nវាយ /myqr ពេលណាក៏បានដើម្បីទទួល QR នេះម្តងទៀត"
        );
    }

    private function sendStaffQrLink(StaffProfile $staff, TelegramService $telegram, string $prefix): void
    {
        if (! $staff->qr_token) {
            $staff->update(['qr_token' => \Illuminate\Support\Str::random(24)]);
        }

        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($staff->qr_token)
            ->size(300)
            ->margin(10)
            ->build();

        $qrPng = $result->getString();
        $qrUrl = route('dashboard.staff.qr', $staff);

        $telegram->sendPhoto(
            $staff->telegram_chat_id,
            $qrPng,
            "{$prefix}\n\n{$qrUrl}\n\nSend /myqr anytime to get this QR again."
        );
    }
}