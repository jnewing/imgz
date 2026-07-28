<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class MakeAdminCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'imgz:admin {email : The user email} {--revoke : Revoke admin instead of granting}';

    /**
     * @var string
     */
    protected $description = 'Grant or revoke admin (moderation) access for a user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if ($user === null) {
            $this->error("No user found with email {$this->argument('email')}.");

            return self::FAILURE;
        }

        $user->is_admin = ! $this->option('revoke');
        $user->save();

        $this->info($this->option('revoke')
            ? "Revoked admin from {$user->email}."
            : "Granted admin to {$user->email}.");

        return self::SUCCESS;
    }
}
