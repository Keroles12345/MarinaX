<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class FixSuperAdminPassword extends Command
{
    protected $signature = 'app:fix-super-admin-password';

    protected $description = 'Fix the existing Super Admin password';

    public function handle(): int
    {
        $email = strtolower(trim($this->ask('Super Admin email')));

        $user = User::withoutGlobalScopes()
            ->where('email', $email)
            ->where('is_super_admin', true)
            ->first();

        if (! $user) {
            $this->error('Super Admin account not found.');
            return self::FAILURE;
        }

        $password = $this->secret('Enter new password (minimum 8 characters)');
        $confirmation = $this->secret('Confirm new password');

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Password must be at least 8 characters.');
            return self::FAILURE;
        }

        if ($password !== $confirmation) {
            $this->error('Passwords do not match.');
            return self::FAILURE;
        }

        $hash = Hash::make($password);

        $user->password = $hash;
        $user->password_hash = $hash;
        $user->save();

        $this->info('Super Admin password updated successfully.');

        return self::SUCCESS;
    }
}
