<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Campaign;
use App\Models\Questionnaire;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create demo companies
        $techCorp = Company::firstOrCreate(
            ['subdomain' => 'techcorp'],
            [
                'name' => 'TechCorp Solutions',
                'email' => 'hr@techcorp.com',
                'phone' => '+54 11 1234-5678',
                'max_campaigns' => 10,
                'max_responses_per_campaign' => 50,
                'is_active' => true,
            ]
        );

        $globalInc = Company::firstOrCreate(
            ['subdomain' => 'globalinc'],
            [
                'name' => 'Global Inc',
                'email' => 'rrhh@globalinc.com',
                'phone' => '+54 11 8765-4321',
                'max_campaigns' => 15,
                'max_responses_per_campaign' => 400,
                'is_active' => true,
            ]
        );

        // Create company users
        CompanyUser::firstOrCreate(
            ['email' => 'maria@techcorp.com'],
            [
                'company_id' => $techCorp->id,
                'name' => 'María García',
                'password' => 'password123',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        CompanyUser::firstOrCreate(
            ['email' => 'carlos@globalinc.com'],
            [
                'company_id' => $globalInc->id,
                'name' => 'Carlos Rodríguez',
                'password' => 'password123',
                'role' => 'admin',
                'is_active' => true,
            ]
        );

        // Get questionnaires
        $questionnaires = Questionnaire::all();

        if ($questionnaires->count() > 0) {
            // Create demo campaigns for TechCorp
            $techCampaign1 = Campaign::create([
                'company_id' => $techCorp->id,
                'name' => 'Evaluación Equipo Desarrollo',
                'description' => 'Evaluación de habilidades blandas para el equipo de desarrollo',
                'max_responses' => 20,
                'active_from' => now(),
                'active_until' => now()->addDays(30),
                'status' => 'active',
            ]);

            $techCampaign2 = Campaign::create([
                'company_id' => $techCorp->id,
                'name' => 'Liderazgo Gerentes',
                'description' => 'Evaluación de potencial de liderazgo para gerentes',
                'max_responses' => 10,
                'active_from' => now()->addDays(7),
                'active_until' => now()->addDays(37),
                'status' => 'draft',
            ]);

            // Create demo campaigns for GlobalInc
            $globalCampaign1 = Campaign::create([
                'company_id' => $globalInc->id,
                'name' => 'Evaluación Masiva 2025',
                'description' => 'Evaluación anual de todos los empleados',
                'max_responses' => 350,
                'active_from' => now(),
                'active_until' => now()->addDays(60),
                'status' => 'active',
            ]);

            $globalCampaign2 = Campaign::create([
                'company_id' => $globalInc->id,
                'name' => 'Nuevos Ingresos Q1',
                'description' => 'Evaluación de empleados nuevos del primer trimestre',
                'max_responses' => 50,
                'active_from' => now()->addDays(-10),
                'active_until' => now()->addDays(20),
                'status' => 'active',
            ]);

            // Assign questionnaires to campaigns
            $reflectiveQuestionnaire = $questionnaires->where('scoring_type', 'REFLECTIVE_QUESTIONS')->first();
            $personalityQuestionnaire = $questionnaires->where('scoring_type', 'PERSONALITY_ASSESSMENT')->first();
            $leadershipQuestionnaire = $questionnaires->where('scoring_type', 'LEADERSHIP_POTENTIAL')->first();

            if ($reflectiveQuestionnaire) {
                // TechCorp campaigns
                $techCampaign1->questionnaires()->attach($reflectiveQuestionnaire->id, ['order' => 1, 'is_required' => true]);
                if ($personalityQuestionnaire) {
                    $techCampaign1->questionnaires()->attach($personalityQuestionnaire->id, ['order' => 2, 'is_required' => true]);
                }

                // Leadership campaign
                if ($leadershipQuestionnaire) {
                    $techCampaign2->questionnaires()->attach($leadershipQuestionnaire->id, ['order' => 1, 'is_required' => true]);
                }

                // GlobalInc campaigns
                $globalCampaign1->questionnaires()->attach($reflectiveQuestionnaire->id, ['order' => 1, 'is_required' => true]);
                if ($personalityQuestionnaire) {
                    $globalCampaign1->questionnaires()->attach($personalityQuestionnaire->id, ['order' => 2, 'is_required' => true]);
                }

                $globalCampaign2->questionnaires()->attach($reflectiveQuestionnaire->id, ['order' => 1, 'is_required' => true]);
            }
        }

        $this->command->info('Datos de demo creados exitosamente:');
        $this->command->info('- Empresas: TechCorp Solutions, Global Inc');
        $this->command->info('- Usuarios empresa: maria@techcorp.com, carlos@globalinc.com');
        $this->command->info('- Contraseña: password123');
        $this->command->info('- Campañas: 4 campañas de prueba');
        $this->command->info('');
        $this->command->info('URLs de acceso:');
        $this->command->info('- Admin: http://127.0.0.1:8001/admin');
        $this->command->info('- Empresas: http://127.0.0.1:8001/company');
    }
}