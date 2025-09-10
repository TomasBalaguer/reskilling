<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignResponse;
use App\Models\Company;
use App\Services\ComprehensiveReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    protected $comprehensiveReportService;

    public function __construct(ComprehensiveReportService $comprehensiveReportService)
    {
        $this->comprehensiveReportService = $comprehensiveReportService;
    }

    /**
     * Dashboard principal del admin
     */
    public function dashboard()
    {
        $stats = [
            'total_companies' => Company::count(),
            'total_campaigns' => Campaign::count(),
            'total_responses' => CampaignResponse::count(),
            'completed_responses' => CampaignResponse::whereIn('processing_status', ['completed', 'analyzed'])->count(),
        ];

        $recentResponses = CampaignResponse::with(['campaign.company'])
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentResponses'));
    }

    /**
     * Listar todas las compañías
     */
    public function companies()
    {
        $companies = Company::withCount(['campaigns', 'campaigns as responses_count' => function ($query) {
            $query->join('campaign_responses', 'campaigns.id', '=', 'campaign_responses.campaign_id');
        }])->paginate(15);

        return view('admin.companies.index', compact('companies'));
    }

    /**
     * Ver detalle de una compañía
     */
    public function companyDetail($companyId)
    {
        $company = Company::with(['campaigns.responses'])->findOrFail($companyId);
        
        $totalResponses = $company->campaigns->sum(function($campaign) {
            return $campaign->responses->count();
        });
        
        $completedResponses = $company->campaigns->sum(function($campaign) {
            return $campaign->responses->whereIn('processing_status', ['completed', 'analyzed'])->count();
        });
        
        $pendingResponses = $totalResponses - $completedResponses;
        
        return view('admin.companies.detail', compact('company', 'totalResponses', 'completedResponses', 'pendingResponses'));
    }

    /**
     * Mostrar formulario para crear nueva empresa
     */
    public function createCompany()
    {
        return view('admin.companies.create');
    }

    /**
     * Guardar nueva empresa
     */
    public function storeCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'max_campaigns' => 'required|integer|min:1|max:1000',
            'max_responses_per_campaign' => 'required|integer|min:10|max:10000',
            'is_active' => 'boolean',
            'admin_email' => 'required|email|max:255|unique:company_users,email',
            'admin_password' => 'required|string|min:6',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $company = Company::create($validated);

        // Crear usuario administrador de la empresa
        \App\Models\CompanyUser::create([
            'company_id' => $company->id,
            'name' => $validated['name'] . ' Admin',
            'email' => $validated['admin_email'],
            'password' => $validated['admin_password'],
            'role' => 'admin',
            'is_active' => true,
        ]);

        return redirect()->route('admin.companies.detail', $company->id)
            ->with('success', 'Empresa y usuario administrador creados exitosamente.');
    }

    /**
     * Mostrar formulario para editar empresa
     */
    public function editCompany($companyId)
    {
        $company = Company::findOrFail($companyId);
        return view('admin.companies.edit', compact('company'));
    }

    /**
     * Actualizar empresa
     */
    public function updateCompany(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'max_campaigns' => 'required|integer|min:1|max:1000',
            'max_responses_per_campaign' => 'required|integer|min:10|max:10000',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $company->update($validated);

        return redirect()->route('admin.companies.detail', $company->id)
            ->with('success', 'Empresa actualizada exitosamente.');
    }

    /**
     * Eliminar empresa
     */
    public function deleteCompany($companyId)
    {
        $company = Company::findOrFail($companyId);
        
        // Verificar si tiene campañas activas
        if ($company->campaigns()->where('status', 'active')->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar una empresa con campañas activas.');
        }

        $company->delete();

        return redirect()->route('admin.companies')
            ->with('success', 'Empresa eliminada exitosamente.');
    }

    /**
     * Activar/Desactivar empresa
     */
    public function toggleCompanyStatus($companyId)
    {
        $company = Company::findOrFail($companyId);
        $company->update(['is_active' => !$company->is_active]);

        $status = $company->is_active ? 'activada' : 'desactivada';
        
        return redirect()->back()
            ->with('success', "Empresa {$status} exitosamente.");
    }

    /**
     * Listar todas las campañas
     */
    public function campaigns()
    {
        $campaigns = Campaign::with(['company'])
            ->withCount('responses')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.campaigns.index', compact('campaigns'));
    }

    /**
     * Ver detalle de una campaña y sus respuestas
     */
    public function campaignDetail($campaignId)
    {
        $campaign = Campaign::with([
            'company',
            'questionnaires',
            'responses' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])->findOrFail($campaignId);

        return view('admin.campaigns.detail', compact('campaign'));
    }

    /**
     * Mostrar formulario para crear nueva campaña
     */
    public function createCampaign(Request $request)
    {
        $companies = Company::active()->orderBy('name')->get();
        $selectedCompanyId = $request->get('company_id');
        $questionnaires = \App\Models\Questionnaire::active()->get();
        
        return view('admin.campaigns.create', compact('companies', 'selectedCompanyId', 'questionnaires'));
    }

    /**
     * Guardar nueva campaña
     */
    public function storeCampaign(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_responses' => 'required|integer|min:1|max:10000',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after:active_from',
            'status' => 'required|in:draft,active,paused,completed',
            'questionnaires' => 'required|array|min:1',
            'questionnaires.*' => 'exists:questionnaires,id',
        ]);

        // Verificar límites de la empresa
        $company = Company::findOrFail($validated['company_id']);
        if ($company->campaigns()->count() >= $company->max_campaigns) {
            return redirect()->back()
                ->with('error', 'La empresa ha alcanzado su límite máximo de campañas.')
                ->withInput();
        }

        if ($validated['max_responses'] > $company->max_responses_per_campaign) {
            return redirect()->back()
                ->with('error', "El máximo de respuestas no puede exceder {$company->max_responses_per_campaign} (límite de la empresa).")
                ->withInput();
        }

        // Generar código único de campaña
        do {
            $code = 'CAMP' . strtoupper(uniqid());
        } while (Campaign::where('code', $code)->exists());
        
        $validated['code'] = $code;

        $campaign = Campaign::create($validated);

        // Asociar cuestionarios seleccionados con la campaña
        $questionnaireData = [];
        foreach ($validated['questionnaires'] as $index => $questionnaireId) {
            $questionnaireData[$questionnaireId] = [
                'order' => $index + 1,
                'is_required' => true
            ];
        }
        $campaign->questionnaires()->attach($questionnaireData);

        return redirect()->route('admin.campaigns.detail', $campaign->id)
            ->with('success', 'Campaña creada exitosamente.');
    }

    /**
     * Mostrar formulario para editar campaña
     */
    public function editCampaign($campaignId)
    {
        $campaign = Campaign::with('company')->findOrFail($campaignId);
        $companies = Company::active()->orderBy('name')->get();
        
        return view('admin.campaigns.edit', compact('campaign', 'companies'));
    }

    /**
     * Actualizar campaña
     */
    public function updateCampaign(Request $request, $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_responses' => 'required|integer|min:1|max:10000',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after:active_from',
            'status' => 'required|in:draft,active,paused,completed',
        ]);

        // Verificar límite de respuestas de la empresa
        if ($validated['max_responses'] > $campaign->company->max_responses_per_campaign) {
            return redirect()->back()
                ->with('error', "El máximo de respuestas no puede exceder {$campaign->company->max_responses_per_campaign} (límite de la empresa).")
                ->withInput();
        }

        $campaign->update($validated);

        return redirect()->route('admin.campaigns.detail', $campaign->id)
            ->with('success', 'Campaña actualizada exitosamente.');
    }

    /**
     * Cambiar estado de campaña (activar/pausar/completar)
     */
    public function toggleCampaignStatus(Request $request, $campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        $newStatus = $request->input('status');
        
        if (!in_array($newStatus, ['draft', 'active', 'paused', 'completed'])) {
            return redirect()->back()->with('error', 'Estado no válido.');
        }

        $campaign->update(['status' => $newStatus]);
        
        $statusText = [
            'draft' => 'borrador',
            'active' => 'activa',
            'paused' => 'pausada',
            'completed' => 'completada'
        ];

        return redirect()->back()
            ->with('success', "Campaña marcada como {$statusText[$newStatus]} exitosamente.");
    }

    /**
     * Eliminar campaña
     */
    public function deleteCampaign($campaignId)
    {
        $campaign = Campaign::findOrFail($campaignId);
        
        // Verificar si tiene respuestas
        if ($campaign->responses()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar una campaña que tiene respuestas.');
        }

        $campaign->delete();

        return redirect()->route('admin.campaigns')
            ->with('success', 'Campaña eliminada exitosamente.');
    }

    /**
     * Ver todas las respuestas
     */
    public function responses()
    {
        $responses = CampaignResponse::with(['campaign.company'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.responses.index', compact('responses'));
    }

    /**
     * Ver detalle de una respuesta específica
     */
    public function responseDetail($responseId)
    {
        $response = CampaignResponse::with([
            'campaign.company',
            'questionnaire'
        ])->findOrFail($responseId);

        // Generar análisis comprehensivo si no existe
        if (!$response->comprehensive_report && $response->processing_status === 'analyzed') {
            try {
                $comprehensiveReport = $this->comprehensiveReportService->generateComprehensiveReport($response);
                $response->update(['comprehensive_report' => $comprehensiveReport]);
                $response->refresh();
            } catch (\Exception $e) {
                Log::error('Error generating comprehensive report', [
                    'response_id' => $responseId,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('admin.responses.detail', compact('response'));
    }

    /**
     * Generar reporte PDF de una respuesta
     */
    public function generateResponseReport($responseId)
    {
        $response = CampaignResponse::with([
            'campaign.company',
            'questionnaire'
        ])->findOrFail($responseId);

        // Asegurarse de que existe el reporte comprehensivo
        if (!$response->comprehensive_report) {
            $comprehensiveReport = $this->comprehensiveReportService->generateComprehensiveReport($response);
            $response->update(['comprehensive_report' => $comprehensiveReport]);
            $response->refresh();
        }

        // Generar PDF usando una vista específica
        $pdf = Pdf::loadView('admin.reports.pdf', compact('response'));
        
        $filename = sprintf(
            'reporte_%s_%s_%s.pdf',
            \Str::slug($response->campaign->name),
            \Str::slug($response->respondent_name),
            $response->created_at->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    /**
     * Re-procesar una respuesta
     */
    public function reprocessResponse($responseId)
    {
        $response = CampaignResponse::findOrFail($responseId);

        // Reset status and trigger reprocessing
        $response->update([
            'processing_status' => 'pending',
            'ai_analysis' => null,
            'ai_analysis_status' => null,
            'comprehensive_report' => null
        ]);

        // Dispatch reprocessing jobs
        \App\Jobs\GenerateAIInterpretationJob::dispatch($response->id)->onQueue('ai-processing');
        \App\Jobs\GenerateComprehensiveReportJob::dispatch($response->id)->onQueue('reports');

        return redirect()
            ->back()
            ->with('success', 'Respuesta enviada para re-procesamiento. Esto puede tomar unos minutos.');
    }

    /**
     * Eliminar una respuesta
     */
    public function deleteResponse($responseId)
    {
        $response = CampaignResponse::findOrFail($responseId);
        $response->delete();

        return redirect()
            ->route('admin.responses')
            ->with('success', 'Respuesta eliminada correctamente.');
    }

    /**
     * Exportar datos de una campaña a CSV
     */
    public function exportCampaignData($campaignId)
    {
        $campaign = Campaign::with(['responses.questionnaire'])->findOrFail($campaignId);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="campaign_' . $campaign->code . '_export.csv"',
        ];

        $callback = function() use ($campaign) {
            $file = fopen('php://output', 'w');
            
            // Headers CSV
            fputcsv($file, [
                'ID',
                'Nombre',
                'Email',
                'Fecha Respuesta',
                'Estado',
                'Cuestionario',
                'Tiempo Respuesta (min)',
                'Análisis IA Completado'
            ]);

            // Data rows
            foreach ($campaign->responses as $response) {
                fputcsv($file, [
                    $response->id,
                    $response->respondent_name,
                    $response->respondent_email,
                    $response->created_at->format('Y-m-d H:i:s'),
                    $response->processing_status,
                    $response->questionnaire->name ?? 'N/A',
                    $response->duration_minutes ?? 'N/A',
                    $response->ai_analysis ? 'Sí' : 'No'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Crear usuario de empresa
     */
    public function createCompanyUser(Request $request, $companyId)
    {
        $company = Company::findOrFail($companyId);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:company_users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:user,admin'
        ]);

        $validated['company_id'] = $company->id;
        $validated['is_active'] = true;

        \App\Models\CompanyUser::create($validated);

        return redirect()->back()
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Cambiar contraseña de usuario de empresa
     */
    public function resetCompanyUserPassword(Request $request, $companyId, $userId)
    {
        $company = Company::findOrFail($companyId);
        $user = $company->users()->findOrFail($userId);
        
        $validated = $request->validate([
            'password' => 'required|string|min:6|confirmed'
        ]);

        $user->update(['password' => $validated['password']]);

        return redirect()->back()
            ->with('success', 'Contraseña actualizada exitosamente.');
    }

    /**
     * Toggle status de usuario de empresa
     */
    public function toggleCompanyUserStatus($companyId, $userId)
    {
        $company = Company::findOrFail($companyId);
        $user = $company->users()->findOrFail($userId);
        
        $user->update(['is_active' => !$user->is_active]);
        
        $statusText = $user->is_active ? 'activado' : 'desactivado';
        
        return redirect()->back()
            ->with('success', "Usuario {$statusText} exitosamente.");
    }

    /**
     * Download audio file from S3
     */
    public function downloadAudio($filename)
    {
        // Check if file exists in S3
        $path = 'responses/' . $filename;
        
        if (Storage::disk('s3')->exists($path)) {
            // Generate a temporary signed URL (valid for 5 minutes)
            $url = Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(5));
            
            // Redirect to the signed URL
            return redirect($url);
        }
        
        // If not in main S3, try audio-storage disk
        if (Storage::disk('audio-storage')->exists($path)) {
            $url = Storage::disk('audio-storage')->temporaryUrl($path, now()->addMinutes(5));
            return redirect($url);
        }
        
        // Try without 'responses/' prefix
        if (Storage::disk('s3')->exists($filename)) {
            $url = Storage::disk('s3')->temporaryUrl($filename, now()->addMinutes(5));
            return redirect($url);
        }
        
        if (Storage::disk('audio-storage')->exists($filename)) {
            $url = Storage::disk('audio-storage')->temporaryUrl($filename, now()->addMinutes(5));
            return redirect($url);
        }
        
        return abort(404, 'Audio file not found');
    }
}