<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Mail\CampaignInvitation;
use App\Models\Campaign;
use App\Models\CampaignResponse;
use App\Models\Company;
use App\Services\ComprehensiveReportService;
use App\Services\EmailLoggerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    protected $comprehensiveReportService;

    public function __construct(ComprehensiveReportService $comprehensiveReportService)
    {
        $this->comprehensiveReportService = $comprehensiveReportService;
    }

    /**
     * Dashboard de la empresa
     */
    public function dashboard(Request $request)
    {
        // El middleware se encarga de verificar autenticación y company_id
        $companyId = $request->get('company_id');
        
        $company = Company::findOrFail($companyId);
        
        $stats = [
            'total_campaigns' => $company->campaigns()->count(),
            'total_responses' => CampaignResponse::whereHas('campaign', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })->count(),
            'completed_responses' => CampaignResponse::whereHas('campaign', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })->whereIn('processing_status', ['completed', 'analyzed'])->count(),
            'pending_responses' => CampaignResponse::whereHas('campaign', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })->where('processing_status', 'pending')->count(),
        ];

        $recentResponses = CampaignResponse::with(['campaign'])
            ->whereHas('campaign', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('company.dashboard', compact('stats', 'recentResponses', 'company'));
    }

    /**
     * Listar campañas de la empresa
     */
    public function campaigns(Request $request)
    {
        $companyId = $request->get('company_id');
        
        $company = Company::findOrFail($companyId);
        
        $campaigns = $company->campaigns()
            ->withCount('responses')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('company.campaigns.index', compact('campaigns', 'company'));
    }

    /**
     * Ver detalle de una campaña de la empresa
     */
    public function campaignDetail(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        
        $campaign = Campaign::with([
            'company',
            'questionnaires',
            'responses' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }
        ])
        ->where('company_id', $companyId)
        ->findOrFail($campaignId);

        return view('company.campaigns.detail', compact('campaign'));
    }

    /**
     * Ver todas las respuestas de la empresa
     */
    public function responses(Request $request)
    {
        $companyId = $request->get('company_id');
        
        $company = Company::findOrFail($companyId);
        
        $responses = CampaignResponse::with(['campaign'])
            ->whereHas('campaign', function($query) use ($companyId) {
                $query->where('company_id', $companyId);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('company.responses.index', compact('responses', 'company'));
    }

    /**
     * Ver detalle de una respuesta específica de la empresa
     */
    public function responseDetail(Request $request, $responseId)
    {
        $companyId = $request->get('company_id');
        
        $response = CampaignResponse::with([
            'campaign.company',
            'questionnaire'
        ])
        ->whereHas('campaign', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->findOrFail($responseId);

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

        return view('company.responses.detail', compact('response'));
    }

    /**
     * Generar reporte PDF de una respuesta de la empresa
     */
    public function generateResponseReport(Request $request, $responseId)
    {
        $companyId = $request->get('company_id');
        
        $response = CampaignResponse::with([
            'campaign.company',
            'questionnaire'
        ])
        ->whereHas('campaign', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
        ->findOrFail($responseId);

        // Asegurarse de que existe el reporte comprehensivo
        if (!$response->comprehensive_report) {
            $comprehensiveReport = $this->comprehensiveReportService->generateComprehensiveReport($response);
            $response->update(['comprehensive_report' => $comprehensiveReport]);
            $response->refresh();
        }

        // Generar PDF usando una vista específica
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('company.reports.pdf', compact('response'));
        
        $filename = sprintf(
            'reporte_%s_%s_%s.pdf',
            str_slug($response->campaign->name),
            str_slug($response->respondent_name),
            $response->created_at->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    /**
     * Exportar datos de una campaña de la empresa a CSV
     */
    public function exportCampaignData(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        
        $campaign = Campaign::with(['responses.questionnaire'])
            ->where('company_id', $companyId)
            ->findOrFail($campaignId);

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
                    $response->comprehensive_report ? 'Sí' : 'No'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Re-procesar una respuesta (solo para testing, podría restringirse)
     */
    public function reprocessResponse(Request $request, $responseId)
    {
        $companyId = $request->get('company_id');
        
        $response = CampaignResponse::whereHas('campaign', function($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })->findOrFail($responseId);

        // Reset status and trigger reprocessing
        $response->update([
            'processing_status' => 'pending',
            'ai_analysis' => null,
            'ai_analysis_status' => null,
            'comprehensive_report' => null
        ]);

        // Dispatch reprocessing job
        \App\Jobs\GenerateAIInterpretationJob::dispatch($response->id)->onQueue('ai-processing');

        return redirect()
            ->back()
            ->with('success', 'Respuesta enviada para re-procesamiento. Esto puede tomar unos minutos.');
    }

    /**
     * Mostrar formulario para crear nueva campaña
     */
    public function createCampaign(Request $request)
    {
        $companyId = $request->get('company_id');
        $company = Company::findOrFail($companyId);
        
        // Verificar si la empresa puede crear más campañas
        if ($company->campaigns()->count() >= $company->max_campaigns) {
            return redirect()->back()
                ->with('error', 'Ha alcanzado el límite máximo de campañas permitidas.')
                ->with('limit_info', "Límite: {$company->max_campaigns} campañas");
        }

        // Obtener cuestionarios activos
        $questionnaires = \App\Models\Questionnaire::active()->get();

        return view('company.campaigns.create', compact('company', 'questionnaires'));
    }

    /**
     * Guardar nueva campaña
     */
    public function storeCampaign(Request $request)
    {
        $companyId = $request->get('company_id');
        $company = Company::findOrFail($companyId);
        
        // Verificar límites nuevamente
        if ($company->campaigns()->count() >= $company->max_campaigns) {
            return redirect()->back()
                ->with('error', 'Ha alcanzado el límite máximo de campañas permitidas.')
                ->withInput();
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_responses' => 'required|integer|min:1|max:' . $company->max_responses_per_campaign,
            'access_type' => 'required|in:public_link,email_list',
            'allow_public_access' => 'nullable|boolean',
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after:active_from',
            'email_list' => 'nullable|file|mimes:csv,txt|max:2048',
            'questionnaires' => 'required|array|min:1',
            'questionnaires.*' => 'exists:questionnaires,id',
        ]);

        // Generar código único
        do {
            $code = 'CAMP' . strtoupper(uniqid());
        } while (Campaign::where('code', $code)->exists());

        // Determinar allow_public_access basado en el tipo de acceso
        $allowPublicAccess = true; // Por defecto true para public_link
        if ($validated['access_type'] === 'email_list') {
            $allowPublicAccess = $request->boolean('allow_public_access', true);
        }

        $campaignData = [
            'company_id' => $companyId,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'code' => $code,
            'max_responses' => $validated['max_responses'],
            'status' => 'active', // Las empresas crean campañas activas por defecto
            'active_from' => $validated['active_from'],
            'active_until' => $validated['active_until'],
            'access_type' => $validated['access_type'],
            'allow_public_access' => $allowPublicAccess,
        ];

        $campaign = Campaign::create($campaignData);

        // Asociar cuestionarios seleccionados con la campaña
        $questionnaireData = [];
        foreach ($validated['questionnaires'] as $index => $questionnaireId) {
            $questionnaireData[$questionnaireId] = [
                'order' => $index + 1,
                'is_required' => true // Por defecto todos son requeridos
            ];
        }
        $campaign->questionnaires()->attach($questionnaireData);

        // Si es por lista de emails, procesar el archivo CSV
        if ($validated['access_type'] === 'email_list' && $request->hasFile('email_list')) {
            $this->processEmailList($request->file('email_list'), $campaign);
        }

        return redirect()->route('company.campaigns.detail', $campaign->id)
            ->with('success', 'Campaña creada exitosamente.')
            ->with('company_id', $companyId);
    }

    /**
     * Procesar lista de emails desde CSV
     */
    private function processEmailList($file, Campaign $campaign)
    {
        $emails = [];
        $handle = fopen($file->getPathname(), 'r');
        
        // Leer primera línea para ver si tiene encabezados
        $firstLine = fgetcsv($handle);
        
        // Si la primera línea parece ser encabezados, saltarla
        if (!filter_var($firstLine[0], FILTER_VALIDATE_EMAIL)) {
            // Es un encabezado, continuar con las siguientes líneas
        } else {
            // La primera línea es un email válido, procesarla
            if (isset($firstLine[0])) $emails[] = ['email' => $firstLine[0], 'name' => $firstLine[1] ?? null];
        }
        
        // Procesar el resto de líneas
        while (($data = fgetcsv($handle)) !== false) {
            if (filter_var($data[0], FILTER_VALIDATE_EMAIL)) {
                $emails[] = [
                    'email' => $data[0],
                    'name' => $data[1] ?? null
                ];
            }
        }
        fclose($handle);

        // Guardar emails invitados
        foreach ($emails as $emailData) {
            \App\Models\CampaignInvitation::create([
                'campaign_id' => $campaign->id,
                'email' => $emailData['email'],
                'name' => $emailData['name'],
                'token' => \Str::random(64),
                'status' => 'pending',
            ]);
        }

        // Enviar emails de invitación automáticamente
        $this->sendInvitationEmails($campaign);
    }

    /**
     * Mostrar formulario para editar campaña (solo campañas sin respuestas)
     */
    public function editCampaign(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        $campaign = Campaign::where('company_id', $companyId)->findOrFail($campaignId);
        
        // Solo permitir editar si no tiene respuestas
        if ($campaign->responses()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede editar una campaña que ya tiene respuestas.');
        }

        $company = Company::findOrFail($companyId);
        
        return view('company.campaigns.edit', compact('campaign', 'company'));
    }

    /**
     * Actualizar campaña
     */
    public function updateCampaign(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        $campaign = Campaign::where('company_id', $companyId)->findOrFail($campaignId);
        $company = Company::findOrFail($companyId);
        
        // Solo permitir editar si no tiene respuestas
        if ($campaign->responses()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede editar una campaña que ya tiene respuestas.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'max_responses' => 'required|integer|min:1|max:' . $company->max_responses_per_campaign,
            'active_from' => 'nullable|date',
            'active_until' => 'nullable|date|after:active_from',
        ]);

        // Handle allow_public_access for email_list campaigns
        if ($campaign->access_type === 'email_list') {
            $validated['allow_public_access'] = $request->has('allow_public_access');
        }

        $campaign->update($validated);

        return redirect()->route('company.campaigns.detail', $campaign->id)
            ->with('success', 'Campaña actualizada exitosamente.')
            ->with('company_id', $companyId);
    }

    /**
     * Activar/Pausar campaña
     */
    public function toggleCampaignStatus(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        $campaign = Campaign::where('company_id', $companyId)->findOrFail($campaignId);
        
        $newStatus = $campaign->status === 'active' ? 'paused' : 'active';
        $campaign->update(['status' => $newStatus]);
        
        $statusText = $newStatus === 'active' ? 'activada' : 'pausada';
        
        return redirect()->back()
            ->with('success', "Campaña {$statusText} exitosamente.");
    }

    /**
     * Mostrar formulario de edición de perfil de empresa
     */
    public function editProfile(Request $request)
    {
        $companyId = $request->get('company_id');
        $company = Company::findOrFail($companyId);
        
        return view('company.profile.edit', compact('company'));
    }

    /**
     * Actualizar perfil de empresa
     */
    public function updateProfile(Request $request)
    {
        $companyId = $request->get('company_id');
        $company = Company::findOrFail($companyId);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Max 2MB
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ];

        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            
            // Generate unique filename
            $filename = 'logo_' . $company->id . '_' . time() . '.' . $logoFile->getClientOriginalExtension();
            
            // Store in public/company_logos directory
            $path = $logoFile->storeAs('company_logos', $filename, 'public');
            
            // Remove old logo if exists
            if ($company->logo_url && \Storage::disk('public')->exists($company->logo_url)) {
                \Storage::disk('public')->delete($company->logo_url);
            }
            
            $updateData['logo_url'] = $path;
        }

        $company->update($updateData);

        return redirect()->route('company.profile.edit')
            ->with('success', 'Perfil actualizado exitosamente.')
            ->with('company_id', $companyId);
    }

    /**
     * Eliminar logo de empresa
     */
    public function removeLogo(Request $request)
    {
        $companyId = $request->get('company_id');
        $company = Company::findOrFail($companyId);
        
        if ($company->logo_url) {
            // Remove file from storage
            if (\Storage::disk('public')->exists($company->logo_url)) {
                \Storage::disk('public')->delete($company->logo_url);
            }
            
            // Update database
            $company->update(['logo_url' => null]);
        }

        return redirect()->back()
            ->with('success', 'Logo eliminado exitosamente.');
    }

    /**
     * Enviar emails de invitación a todos los invitados de la campaña
     */
    private function sendInvitationEmails(Campaign $campaign)
    {
        $emailLogger = app(EmailLoggerService::class);
        $invitations = $campaign->invitations;
        
        foreach ($invitations as $invitation) {
            // Log email as queued
            $emailLog = $emailLogger->logEmailQueued($campaign, $invitation);
            
            try {
                Mail::to($invitation->email)->queue(
                    new CampaignInvitation($campaign, $invitation)
                );
                
                // Log email as sent and update invitation status
                $emailLogger->logEmailSent($emailLog);
                $invitation->update(['status' => 'opened']);
                
            } catch (\Exception $e) {
                // Log email as failed
                $emailLogger->logEmailFailed($emailLog, 'Error sending campaign invitation email: ' . $e->getMessage(), $e);
            }
        }
    }

    /**
     * Reenviar invitaciones por email (para uso desde admin)
     */
    public function resendInvitations(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        $campaign = Campaign::where('company_id', $companyId)->findOrFail($campaignId);
        
        // Si se especifica un email individual
        if ($request->has('single_email')) {
            $invitation = $campaign->invitations()->where('email', $request->single_email)->first();
            
            if ($invitation) {
                try {
                    Mail::to($invitation->email)->queue(
                        new CampaignInvitation($campaign, $invitation)
                    );
                    $invitation->update(['status' => 'opened']);
                    
                    return redirect()->back()
                        ->with('success', 'Invitación reenviada a ' . $invitation->email);
                } catch (\Exception $e) {
                    Log::error('Error sending individual invitation', [
                        'campaign_id' => $campaign->id,
                        'email' => $invitation->email,
                        'error' => $e->getMessage()
                    ]);
                    
                    return redirect()->back()
                        ->with('error', 'Error al enviar la invitación.');
                }
            }
            
            return redirect()->back()
                ->with('error', 'Invitación no encontrada.');
        }
        
        // Enviar a todas las invitaciones
        $this->sendInvitationEmails($campaign);
        
        return redirect()->back()
            ->with('success', 'Invitaciones reenviadas exitosamente.');
    }

    /**
     * Agregar una invitación individual
     */
    public function addSingleInvitation(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        $campaign = Campaign::where('company_id', $companyId)->findOrFail($campaignId);
        
        $validated = $request->validate([
            'email' => 'required|email',
            'name' => 'nullable|string|max:255'
        ]);
        
        // Verificar si ya existe una invitación con ese email
        $existingInvitation = $campaign->invitations()->where('email', $validated['email'])->first();
        
        if ($existingInvitation) {
            return redirect()->back()
                ->with('error', 'Ya existe una invitación para este email.');
        }
        
        // Crear nueva invitación
        $invitation = \App\Models\CampaignInvitation::create([
            'campaign_id' => $campaign->id,
            'email' => $validated['email'],
            'name' => $validated['name'],
            'token' => \Str::random(64),
            'status' => 'pending',
        ]);
        
        // Enviar email con logging
        $emailLogger = app(EmailLoggerService::class);
        $emailLog = $emailLogger->logEmailQueued($campaign, $invitation);
        
        try {
            Mail::to($invitation->email)->queue(
                new CampaignInvitation($campaign, $invitation)
            );
            
            $emailLogger->logEmailSent($emailLog);
            $invitation->update(['status' => 'opened']);
            
            return redirect()->back()
                ->with('success', 'Invitación enviada a ' . $invitation->email);
        } catch (\Exception $e) {
            $emailLogger->logEmailFailed($emailLog, 'Error sending individual invitation: ' . $e->getMessage(), $e);
            
            return redirect()->back()
                ->with('error', 'Error al enviar la invitación.');
        }
    }

    /**
     * Agregar invitaciones desde CSV
     */
    public function addCSVInvitations(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        $campaign = Campaign::where('company_id', $companyId)->findOrFail($campaignId);
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048'
        ]);
        
        $emails = [];
        $handle = fopen($request->file('csv_file')->getPathname(), 'r');
        
        // Leer primera línea para ver si tiene encabezados
        $firstLine = fgetcsv($handle);
        
        // Si la primera línea parece ser encabezados, saltarla
        if (!filter_var($firstLine[0], FILTER_VALIDATE_EMAIL)) {
            // Es un encabezado, continuar con las siguientes líneas
        } else {
            // La primera línea es un email válido, procesarla
            if (isset($firstLine[0])) $emails[] = ['email' => $firstLine[0], 'name' => $firstLine[1] ?? null];
        }
        
        // Procesar el resto de líneas
        while (($data = fgetcsv($handle)) !== false) {
            if (filter_var($data[0], FILTER_VALIDATE_EMAIL)) {
                $emails[] = [
                    'email' => $data[0],
                    'name' => $data[1] ?? null
                ];
            }
        }
        fclose($handle);
        
        $emailLogger = app(EmailLoggerService::class);
        $addedCount = 0;
        $skippedCount = 0;
        
        foreach ($emails as $emailData) {
            // Verificar si ya existe
            $existingInvitation = $campaign->invitations()->where('email', $emailData['email'])->first();
            
            if ($existingInvitation) {
                $skippedCount++;
                continue;
            }
            
            // Crear invitación
            $invitation = \App\Models\CampaignInvitation::create([
                'campaign_id' => $campaign->id,
                'email' => $emailData['email'],
                'name' => $emailData['name'],
                'token' => \Str::random(64),
                'status' => 'pending',
            ]);
            
            // Log y enviar email
            $emailLog = $emailLogger->logEmailQueued($campaign, $invitation, 'bulk');
            
            try {
                Mail::to($invitation->email)->queue(
                    new CampaignInvitation($campaign, $invitation)
                );
                
                $emailLogger->logEmailSent($emailLog);
                $invitation->update(['status' => 'opened']);
                $addedCount++;
            } catch (\Exception $e) {
                $emailLogger->logEmailFailed($emailLog, 'Error sending CSV invitation: ' . $e->getMessage(), $e);
            }
        }
        
        $message = "Se agregaron {$addedCount} nuevas invitaciones.";
        if ($skippedCount > 0) {
            $message .= " Se omitieron {$skippedCount} emails que ya estaban invitados.";
        }
        
        return redirect()->back()->with('success', $message);
    }

    /**
     * Ver logs de emails de una campaña
     */
    public function campaignEmailLogs(Request $request, $campaignId)
    {
        $companyId = $request->get('company_id');
        
        $campaign = Campaign::where('company_id', $companyId)->findOrFail($campaignId);
        
        $emailLogs = $campaign->emailLogs()
            ->with('invitation')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
            
        $emailLogger = app(EmailLoggerService::class);
        $stats = $emailLogger->getCampaignEmailStats($campaign);
        $recentFailures = $emailLogger->getRecentFailures($campaign, 5);
        
        return view('company.campaigns.email-logs', compact('campaign', 'emailLogs', 'stats', 'recentFailures'));
    }
}