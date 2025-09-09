<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignInvitation;
use App\Http\Resources\QuestionnaireAssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CampaignController extends Controller
{
    /**
     * Get campaign details by code for public access
     */
    public function getByCode(string $code)
    {
        $campaign = Campaign::with(['questionnaires', 'company'])
            ->where('code', $code)
            ->where('status', 'active')
            ->where('active_from', '<=', now())
            ->where('active_until', '>=', now())
            ->first();

        if (!$campaign) {
            return response()->json(['error' => 'Campaña no encontrada o no activa'], 404);
        }

        // Check if campaign has reached max responses
        if ($campaign->responses_count >= $campaign->max_responses) {
            return response()->json([
                'error' => 'Esta campaña ha alcanzado el máximo de respuestas permitidas'
            ], 423);
        }

        return QuestionnaireAssignmentResource::forCampaign($campaign, $campaign->code);
    }


    /**
     * Verify additional security code if required
     */
    public function verifyCode(Request $request, string $code): JsonResponse
    {
        $request->validate([
            'security_code' => 'nullable|string|max:50'
        ]);

        $campaign = Campaign::where('code', $code)
            ->where('status', 'active')
            ->first();

        if (!$campaign) {
            return response()->json(['error' => 'Campaña no encontrada'], 404);
        }

        // If campaign has additional security code, verify it
        if ($campaign->public_link_code) {
            if (!$request->security_code || $request->security_code !== $campaign->public_link_code) {
                return response()->json([
                    'error' => 'Código de seguridad incorrecto'
                ], 401);
            }
        }

        return response()->json([
            'verified' => true,
            'message' => 'Acceso autorizado'
        ]);
    }

    /**
     * Get campaign details by invitation token
     */
    public function getByInvitation(string $token)
    {
        $invitation = CampaignInvitation::with(['campaign.questionnaires', 'campaign.company'])
            ->where('token', $token)
            ->notExpired()
            ->first();

        if (!$invitation) {
            return response()->json(['error' => 'Invitación no encontrada o expirada'], 404);
        }

        $campaign = $invitation->campaign;

        // Check campaign status
        if ($campaign->status !== 'active' || 
            $campaign->active_from > now() || 
            $campaign->active_until < now()) {
            return response()->json(['error' => 'Campaña no activa'], 404);
        }

        // Mark invitation as opened if first time
        if ($invitation->status === 'pending') {
            $invitation->markAsOpened();
        }

        return QuestionnaireAssignmentResource::forInvitation($invitation);
    }
}