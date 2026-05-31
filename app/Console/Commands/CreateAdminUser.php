<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'mailadmin:create-admin {--email=} {--password=}';
    protected $description = '创建或更新默认管理员账号';

    public function handle(): int
    {
        $email = $this->option('email') ?: env('ADMIN_EMAIL', 'admin@example.com');
        $password = $this->option('password') ?: env('ADMIN_PASSWORD', '');
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || $password === 'change-this-password') {
            $this->error('请设置合法 ADMIN_EMAIL 和至少 12 位的 ADMIN_PASSWORD。');
            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $email],
            ['name' => '管理员', 'password' => Hash::make($password), 'role' => 'admin']
        );
        $this->info('管理员账号已创建或更新。');
        return self::SUCCESS;
    }
}
