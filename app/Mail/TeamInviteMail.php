<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class TeamInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $acceptUrl;

    public function __construct(public User $invitedUser, public string $inviterName, public string $tenantName)
    {
        $this->acceptUrl = URL::temporarySignedRoute(
            'invite.accept',
            now()->addDays(7),
            ['user' => $invitedUser->id]
        );
    }

    public function build()
    {
        return $this->subject("អ្នកត្រូវបានអញ្ជើញចូលរួម {$this->tenantName}")
            ->view('emails.team-invite');
    }
}