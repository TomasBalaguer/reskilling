<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignInvitation;
use App\Models\CampaignResponse;
use App\Events\QuestionnaireResponseSubmitted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    /**
     * Acceder a una campaña por código público
     */
    public function accessCampaign($code)
    {
        $campaign = Campaign::with(['questionnaires', 'company'])
            ->where('code', $code)
            ->where('status', 'active')
            ->first();

        if (!$campaign) {
            return view('public.campaign.not-found', compact('code'));
        }

        // Verificar si la campaña permite acceso público
        if ($campaign->access_type !== 'public_link') {
            return view('public.campaign.not-available', compact('campaign'));
        }

        // Verificar si la campaña está en el período activo
        if ($campaign->active_from && $campaign->active_from > now()) {
            return view('public.campaign.not-started', compact('campaign'));
        }

        if ($campaign->active_until && $campaign->active_until < now()) {
            return view('public.campaign.expired', compact('campaign'));
        }

        // Verificar si ya está llena
        if ($campaign->is_full) {
            return view('public.campaign.full', compact('campaign'));
        }

        // Crear sesión para trackear respuestas
        $sessionKey = 'campaign_' . $campaign->id;
        if (!Session::has($sessionKey)) {
            Session::put($sessionKey, [
                'started_at' => now(),
                'respondent_info' => null,
                'completed_questionnaires' => []
            ]);
        }

        // Obtener progreso del usuario si ya proporcionó email
        $userProgress = null;
        $sessionData = Session::get($sessionKey, []);
        $respondentInfo = $sessionData['respondent_info'] ?? null;
        
        if ($respondentInfo && isset($respondentInfo['email'])) {
            $userProgress = $this->checkUserParticipation($campaign, $respondentInfo['email']);
        }

        return view('public.campaign.index', compact('campaign', 'userProgress'));
    }

    /**
     * Acceder por token de invitación
     */
    public function accessByInvitation($token)
    {
        $invitation = CampaignInvitation::with(['campaign.questionnaires', 'campaign.company'])
            ->where('token', $token)
            ->where('status', 'pending')
            ->first();

        if (!$invitation) {
            return view('public.campaign.invitation-invalid');
        }

        // Verificar expiración
        if ($invitation->expires_at && $invitation->expires_at < now()) {
            return view('public.campaign.invitation-expired', compact('invitation'));
        }

        $campaign = $invitation->campaign;

        // Marcar invitación como abierta
        $invitation->update([
            'opened_at' => now(),
            'status' => 'opened'
        ]);

        // Crear sesión con info del invitado
        $sessionKey = 'campaign_' . $campaign->id;
        Session::put($sessionKey, [
            'started_at' => now(),
            'invitation_token' => $token,
            'respondent_info' => [
                'name' => $invitation->name,
                'email' => $invitation->email
            ],
            'completed_questionnaires' => []
        ]);

        return view('public.campaign.index', compact('campaign', 'invitation'));
    }

    /**
     * Mostrar un cuestionario específico
     */
    public function showQuestionnaire($code, $questionnaireId)
    {
        $campaign = Campaign::with(['questionnaires', 'company'])
            ->where('code', $code)
            ->where('status', 'active')
            ->first();

        if (!$campaign) {
            return redirect()->route('public.campaign.not-found', $code);
        }

        $questionnaire = $campaign->questionnaires()->where('questionnaires.id', $questionnaireId)->first();

        if (!$questionnaire) {
            return redirect()->route('public.campaign.access', $code)
                ->with('error', 'Cuestionario no encontrado');
        }

        // Verificar si ya fue completado (sesión y base de datos)
        $sessionKey = 'campaign_' . $campaign->id;
        $sessionData = Session::get($sessionKey, []);
        $completedQuestionnaires = $sessionData['completed_questionnaires'] ?? [];

        // Verificar también en base de datos si tenemos email del usuario
        $respondentInfo = $sessionData['respondent_info'] ?? null;
        if ($respondentInfo && isset($respondentInfo['email'])) {
            $existingResponse = CampaignResponse::where('campaign_id', $campaign->id)
                ->where('questionnaire_id', $questionnaireId)
                ->where('respondent_email', $respondentInfo['email'])
                ->first();

            if ($existingResponse) {
                return redirect()->route('public.campaign.access', $code)
                    ->with('info', 'Este cuestionario ya fue completado anteriormente');
            }
        }

        // Verificar en sesión actual
        if (in_array($questionnaireId, $completedQuestionnaires)) {
            return redirect()->route('public.campaign.access', $code)
                ->with('info', 'Este cuestionario ya fue completado en esta sesión');
        }

        // Cargar estructura del cuestionario
        $structure = $questionnaire->buildStructure();

        return view('public.campaign.questionnaire', compact('campaign', 'questionnaire', 'structure'));
    }

    /**
     * Mostrar formulario de información del respondente
     */
    public function showRespondentForm($code)
    {
        $campaign = Campaign::where('code', $code)->where('status', 'active')->first();

        if (!$campaign) {
            return redirect()->route('public.campaign.not-found', $code);
        }

        return view('public.campaign.respondent-form', compact('campaign'));
    }

    /**
     * Guardar información del respondente
     */
    public function saveRespondentInfo(Request $request, $code)
    {
        $campaign = Campaign::where('code', $code)->where('status', 'active')->first();

        if (!$campaign) {
            return redirect()->route('public.campaign.not-found', $code);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        // Verificar si el usuario ya participó en esta campaña
        $participationStatus = $this->checkUserParticipation($campaign, $validated['email']);

        if ($participationStatus['is_fully_completed']) {
            // Si ya completó toda la campaña, mostrar mensaje
            return view('public.campaign.already-completed', [
                'campaign' => $campaign,
                'email' => $validated['email'],
                'completed_questionnaires' => $participationStatus['completed_questionnaires']
            ]);
        }

        // Si tiene cuestionarios pendientes o es primera vez, continuar
        $sessionKey = 'campaign_' . $campaign->id;
        $sessionData = Session::get($sessionKey, []);
        $sessionData['respondent_info'] = $validated;
        
        // Marcar cuestionarios ya completados en la sesión
        if (!empty($participationStatus['completed_questionnaires'])) {
            $sessionData['completed_questionnaires'] = array_keys($participationStatus['completed_questionnaires']);
        }
        
        Session::put($sessionKey, $sessionData);

        return redirect()->route('public.campaign.access', $code);
    }

    /**
     * Enviar respuestas del cuestionario
     */
    public function submitResponse(Request $request, $code, $questionnaireId)
    {
        $campaign = Campaign::where('code', $code)->where('status', 'active')->first();

        if (!$campaign) {
            return response()->json(['error' => 'Campaña no encontrada'], 404);
        }

        $questionnaire = $campaign->questionnaires()->where('questionnaires.id', $questionnaireId)->first();

        if (!$questionnaire) {
            return response()->json(['error' => 'Cuestionario no encontrado'], 404);
        }

        // Obtener información del respondente desde la sesión
        $sessionKey = 'campaign_' . $campaign->id;
        $sessionData = Session::get($sessionKey, []);
        $respondentInfo = $sessionData['respondent_info'] ?? null;

        if (!$respondentInfo) {
            return response()->json(['error' => 'Información del respondente requerida'], 400);
        }

        try {
            // Crear la respuesta
            $response = CampaignResponse::create([
                'campaign_id' => $campaign->id,
                'questionnaire_id' => $questionnaire->id,
                'session_id' => $request->session()->getId(),
                'respondent_name' => $respondentInfo['name'],
                'respondent_email' => $respondentInfo['email'],
                'responses' => $request->input('responses', []),
                'audio_files' => $this->handleAudioFiles($request),
                'duration_minutes' => $request->input('duration_minutes', 0),
                'processing_status' => 'pending',
                'started_at' => $sessionData['started_at'] ?? now(),
                'session_data' => [
                    'user_agent' => $request->userAgent(),
                    'ip_address' => $request->ip(),
                    'invitation_token' => $sessionData['invitation_token'] ?? null
                ]
            ]);

            // Actualizar contador de respuestas de campaña
            $campaign->incrementResponseCount();

            // Disparar evento para procesamiento en cola
            QuestionnaireResponseSubmitted::dispatch($response);

            // Marcar cuestionario como completado en la sesión
            $sessionData['completed_questionnaires'][] = $questionnaireId;
            Session::put($sessionKey, $sessionData);

            // Si es una invitación, actualizar su estado
            if (!empty($sessionData['invitation_token'])) {
                $invitation = CampaignInvitation::where('token', $sessionData['invitation_token'])->first();
                if ($invitation) {
                    $invitation->update([
                        'completed_at' => now(),
                        'status' => 'completed'
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Respuestas guardadas exitosamente',
                'response_id' => $response->id
            ]);

        } catch (\Exception $e) {
            \Log::error('Error saving campaign response: ' . $e->getMessage());
            return response()->json(['error' => 'Error al guardar las respuestas'], 500);
        }
    }

    /**
     * Página de agradecimiento
     */
    public function thankYou($code)
    {
        $campaign = Campaign::with('company')->where('code', $code)->first();

        if (!$campaign) {
            return redirect()->route('public.campaign.not-found', $code);
        }

        // Limpiar sesión
        $sessionKey = 'campaign_' . $campaign->id;
        Session::forget($sessionKey);

        return view('public.campaign.thank-you', compact('campaign'));
    }

    /**
     * Manejar archivos de audio subidos
     */
    private function handleAudioFiles(Request $request)
    {
        $audioFiles = [];

        if ($request->hasFile('audio_files')) {
            foreach ($request->file('audio_files') as $key => $file) {
                if ($file->isValid()) {
                    $filename = 'audio_' . Str::random(10) . '_' . time() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('campaign_responses/audio', $filename, 'public');
                    
                    $audioFiles[$key] = [
                        'filename' => $filename,
                        'path' => $path,
                        'url' => Storage::url($path),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType()
                    ];
                }
            }
        }

        return $audioFiles;
    }

    /**
     * Verificar el estado de participación de un usuario en una campaña
     */
    private function checkUserParticipation(Campaign $campaign, string $email)
    {
        // Obtener todas las respuestas del usuario para esta campaña
        $userResponses = CampaignResponse::where('campaign_id', $campaign->id)
            ->where('respondent_email', $email)
            ->get()
            ->keyBy('questionnaire_id');

        // Obtener todos los cuestionarios de la campaña
        $campaignQuestionnaires = $campaign->questionnaires()->get()->keyBy('id');

        $completedQuestionnaires = [];
        $pendingQuestionnaires = [];

        foreach ($campaignQuestionnaires as $questionnaireId => $questionnaire) {
            if (isset($userResponses[$questionnaireId])) {
                $completedQuestionnaires[$questionnaireId] = [
                    'questionnaire' => $questionnaire,
                    'response' => $userResponses[$questionnaireId],
                    'completed_at' => $userResponses[$questionnaireId]->created_at
                ];
            } else {
                $pendingQuestionnaires[$questionnaireId] = $questionnaire;
            }
        }

        return [
            'is_fully_completed' => empty($pendingQuestionnaires),
            'has_started' => !empty($completedQuestionnaires),
            'completed_questionnaires' => $completedQuestionnaires,
            'pending_questionnaires' => $pendingQuestionnaires,
            'total_questionnaires' => count($campaignQuestionnaires),
            'completed_count' => count($completedQuestionnaires),
            'pending_count' => count($pendingQuestionnaires)
        ];
    }
}