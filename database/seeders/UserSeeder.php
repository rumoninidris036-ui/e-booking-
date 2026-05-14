<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Admin User',
                'email' => 'admin@ebooking.test',
                'role' => 'admin',
            ],
            [
                'name' => 'Owner User',
                'email' => 'owner@ebooking.test',
                'role' => 'owner',
            ],
            [
                'name' => 'Customer User',
                'email' => 'customer@ebooking.test',
                'role' => 'customer',
            ],
        ];

        foreach ($users as $payload) {
            $user = User::query()->updateOrCreate(
                ['email' => $payload['email']],
                [
                    'name' => $payload['name'],
                    'password' => 'password',
                ],
            );

            $user->syncRoles([$payload['role']]);
        }
    }
}
