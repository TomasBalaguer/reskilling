<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Company;
use App\Models\CompanyUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Super Admin User
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@reskilling.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('admin123'),
                'email_verified_at' => now(),
            ]
        );

        // Create Demo Company
        $company = Company::firstOrCreate(
            ['email' => 'demo@company.com'],
            [
                'name' => 'Demo Company',
                'phone' => '+1234567890',
                'max_campaigns' => 10,
                'max_responses_per_campaign' => 500,
                'settings' => [
                    'theme_color' => '#3B82F6',
                    'allow_anonymous_responses' => true,
                    'address' => '123 Business Street, City, Country'
                ]
            ]
        );

        // Create Company User
        $companyUser = CompanyUser::firstOrCreate(
            ['email' => 'company@demo.com'],
            [
                'company_id' => $company->id,
                'name' => 'Company Admin',
                'password' => 'company123', // El mutador del modelo ya aplica bcrypt()
                'role' => 'admin',
                'email_verified_at' => now(),
            ]
        );

        // Run QuestionnaireSeeder
        $this->call([
            QuestionnaireSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('🔐 CREDENCIALES DE ACCESO:');
        $this->command->info('');
        $this->command->info('📋 SUPER ADMIN (/admin):');
        $this->command->info('   Email: admin@reskilling.com');
        $this->command->info('   Password: admin123');
        $this->command->info('');
        $this->command->info('🏢 COMPANY ADMIN (/company):');
        $this->command->info('   Email: company@demo.com');
        $this->command->info('   Password: company123');
        $this->command->info('');
    }
}
