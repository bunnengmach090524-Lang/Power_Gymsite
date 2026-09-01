<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckMemberTenantIntegrity extends Command
{
    /**
     * php artisan members:check-integrity          → report only, no writes
     * php artisan members:check-integrity --fix     → report + auto-heal
     */
    protected $signature = 'members:check-integrity
        {--fix : Automatically repair Case 1/2/3 mismatches (linking/syncing existing Member rows)}
        {--create-missing : Also create a brand new Member row for Case 4 orphans (User has role=member + tenant_id, but no Member row exists at all). Use only after confirming these are genuine active accounts.}';

    protected $description = 'Audit User.tenant_id vs Member.tenant_id/user_id consistency for all member accounts, optionally fixing mismatches.';

    public function handle(): int
    {
        $fix = (bool) $this->option('fix');
        $createMissing = (bool) $this->option('create-missing');

        $members = User::where('role', 'member')->get();

        $case1 = []; // no Member row linked by user_id at all
        $case2 = []; // Member row exists via user_id, but tenant_id mismatch
        $case3 = []; // Member row exists via (tenant_id + email) but user_id not linked
        $case4 = []; // no Member row found by any strategy — true orphan
        $ok = 0;

        foreach ($members as $user) {
            $byUserId = Member::withoutGlobalScopes()
                ->where('user_id', $user->id)
                ->first();

            if ($byUserId) {
                if ($user->tenant_id && $byUserId->tenant_id !== $user->tenant_id) {
                    $case2[] = [$user, $byUserId];
                } else {
                    $ok++;
                }

                continue;
            }

            // No member linked by user_id — try tenant_id + email.
            $byEmail = $user->tenant_id
                ? Member::withoutGlobalScopes()
                    ->where('tenant_id', $user->tenant_id)
                    ->where('email', $user->email)
                    ->first()
                : null;

            if ($byEmail) {
                $case3[] = [$user, $byEmail];
                continue;
            }

            // Last resort: any Member row with this email, regardless of tenant.
            $byEmailAny = Member::withoutGlobalScopes()
                ->where('email', $user->email)
                ->first();

            if ($byEmailAny) {
                $case1[] = [$user, $byEmailAny];
                continue;
            }

            $case4[] = $user;
        }

        $this->info("Total member users scanned: {$members->count()}");
        $this->line("OK (fully consistent): {$ok}");
        $this->newLine();

        $this->reportCase1($case1, $fix);
        $this->reportCase2($case2, $fix);
        $this->reportCase3($case3, $fix);
        $this->reportCase4($case4, $createMissing);

        if (! $fix && (count($case1) + count($case2) + count($case3)) > 0) {
            $this->newLine();
            $this->warn('Run again with --fix to repair the Case 1/2/3 mismatches listed above.');
        }

        if (! $createMissing && count($case4) > 0) {
            $this->newLine();
            $this->warn('Case 4 rows were NOT touched. If you have confirmed these are genuine active member accounts (not stale test data), run again with --create-missing to create their Member rows.');
        }

        return self::SUCCESS;
    }

    /**
     * Case 1: Member found only by email (no tenant match, no user_id link).
     * Fix: link user_id AND sync Member.tenant_id to User.tenant_id.
     */
    private function reportCase1(array $rows, bool $fix): void
    {
        if (empty($rows)) {
            return;
        }

        $this->error('Case 1 — Member found by email only (tenant_id AND user_id both mismatched): ' . count($rows));

        foreach ($rows as [$user, $member]) {
            $this->line("  User #{$user->id} ({$user->email}) tenant_id={$user->tenant_id} <-> Member #{$member->id} tenant_id={$member->tenant_id} user_id={$member->user_id}");

            if ($fix) {
                DB::transaction(function () use ($user, $member) {
                    $member->user_id = $user->id;
                    if ($user->tenant_id) {
                        $member->tenant_id = $user->tenant_id;
                    }
                    $member->save();
                });
                $this->line('    -> fixed: linked user_id and synced tenant_id');
            }
        }

        $this->newLine();
    }

    /**
     * Case 2: Member.user_id correctly points to this user, but
     * Member.tenant_id disagrees with User.tenant_id.
     * Fix: sync Member.tenant_id to User.tenant_id (source of truth).
     */
    private function reportCase2(array $rows, bool $fix): void
    {
        if (empty($rows)) {
            return;
        }

        $this->error('Case 2 — Member linked by user_id, but tenant_id mismatch: ' . count($rows));

        foreach ($rows as [$user, $member]) {
            $this->line("  User #{$user->id} ({$user->email}) tenant_id={$user->tenant_id} <-> Member #{$member->id} tenant_id={$member->tenant_id}");

            if ($fix) {
                $member->tenant_id = $user->tenant_id;
                $member->save();
                $this->line('    -> fixed: Member.tenant_id synced to User.tenant_id');
            }
        }

        $this->newLine();
    }

    /**
     * Case 3: Member found by (tenant_id + email) but user_id was never linked.
     * Fix: link user_id.
     */
    private function reportCase3(array $rows, bool $fix): void
    {
        if (empty($rows)) {
            return;
        }

        $this->error('Case 3 — Member matches tenant_id + email, but user_id not linked: ' . count($rows));

        foreach ($rows as [$user, $member]) {
            $this->line("  User #{$user->id} ({$user->email}) <-> Member #{$member->id} (tenant_id={$member->tenant_id}, user_id was " . ($member->user_id ?? 'null') . ')');

            if ($fix) {
                $member->user_id = $user->id;
                $member->save();
                $this->line('    -> fixed: linked user_id');
            }
        }

        $this->newLine();
    }

    /**
     * Case 4: no Member row could be found by any strategy — a genuine
     * orphan User with role=member and no matching Member record at all.
     *
     * By default this is left for manual review, since a User in this
     * state could mean either (a) a real member account whose Member row
     * was never created due to a registration-flow gap, or (b) stale/test
     * data that shouldn't get a Member row fabricated for it.
     *
     * Pass --create-missing once you've confirmed (a) is the case — this
     * creates a Member row from the User's own name/email/tenant_id and
     * links it back via user_id, so resolveMember() in
     * MemberAccountController will find it on the very next request.
     */
    private function reportCase4(array $users, bool $createMissing): void
    {
        if (empty($users)) {
            return;
        }

        $this->error('Case 4 — No Member row found at all: ' . count($users));

        foreach ($users as $user) {
            $this->line("  User #{$user->id} ({$user->email}) tenant_id=" . ($user->tenant_id ?? 'null') . ' name=' . ($user->name ?? 'null'));

            if ($createMissing) {
                if (! $user->tenant_id) {
                    $this->line('    -> SKIPPED: User has no tenant_id, cannot create a Member row without one.');
                    continue;
                }

                $member = Member::withoutGlobalScopes()->create([
                    'tenant_id' => $user->tenant_id,
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'joined_date' => $user->created_at ?? now(),
                ]);

                $this->line("    -> created Member #{$member->id}");
            }
        }

        $this->newLine();
    }
}