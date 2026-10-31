<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:bootstrap-super-admin')]
#[Description('Create the initial platform Super Admin account')]
class BootstrapSuperAdmin extends Command
{
    public function handle(): int
    {
        if (! Schema::hasColumn('users', 'is_super_admin') || ! Schema::hasColumn('users', 'tenant_id')) {
            $this->error('شغّل migrations أولاً باستخدام php artisan migrate.');
            return self::FAILURE;
        }

        if (User::withoutGlobalScopes()->where('is_super_admin', true)->exists()) {
            $this->error('يوجد Super Admin بالفعل؛ هذا الأمر يعمل لمرة واحدة فقط.');
            return self::FAILURE;
        }

        $name = trim((string) $this->ask('اسم Super Admin'));
        $email = strtolower(trim((string) $this->ask('البريد الإلكتروني')));
        $username = strtolower(trim((string) $this->ask('اسم المستخدم')));

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL) || $username === '') {
            $this->error('الاسم واسم المستخدم وبريد إلكتروني صالح مطلوبة.');
            return self::FAILURE;
        }

        if (User::withoutGlobalScopes()->where('email', $email)->exists()
            || User::withoutGlobalScopes()->where('username', $username)->exists()) {
            $this->error('البريد الإلكتروني أو اسم المستخدم مستخدم بالفعل.');
            return self::FAILURE;
        }

        $password = $this->secret('كلمة المرور (8 أحرف على الأقل)');
        $confirmation = $this->secret('تأكيد كلمة المرور');
        if (! is_string($password) || strlen($password) < 8 || $password !== $confirmation) {
            $this->error('كلمة المرور يجب أن تكون 8 أحرف على الأقل وأن يتطابق التأكيد.');
            return self::FAILURE;
        }

        User::withoutGlobalScopes()->create([
            'name' => $name,
            'email' => $email,
            'username' => $username,
            'password' => Hash::make($password),
            'password_hash' => Hash::make($password),
            'role' => 'admin',
            'status' => true,
            'tenant_id' => null,
            'is_super_admin' => true,
        ]);

        $this->info('تم إنشاء Super Admin. سجّل الدخول من صفحة الأدمن لإدارة الشركات.');
        return self::SUCCESS;
    }
}
