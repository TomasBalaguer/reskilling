<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create super admin user for Filament admin panel
        User::firstOrCreate([
            'email' => 'admin@reskiling.com'
        ], [
            'name' => 'Super Admin',
            'email' => 'admin@reskiling.com',
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
        ]);

        $this->command->info('Super admin user created: admin@reskiling.com / password123');
    }
}