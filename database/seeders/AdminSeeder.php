<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@shopkey.test'],
            [
                'name' => 'ShopKey Admin',
                'phone' => null,
                'role' => UserRole::Admin,
                'password' => 'Admin@12345',
                'email_verified_at' => now(),
                'locked_at' => null,
            ]
        );

        $admin->wallet()->firstOrCreate([], ['balance' => 0, 'currency' => 'VND']);
        $admin->cart()->firstOrCreate();
    }
}
