<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\CampaignController;
use App\Http\Controllers\API\QuestionnaireResponseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Campaign public access routes
Route::prefix('campaigns')->group(function () {
    Route::get('{code}', [CampaignController::class, 'getByCode']);
    Route::post('{code}/verify', [CampaignController::class, 'verifyCode']);
});

// Campaign invitation routes
Route::prefix('invitations')->group(function () {
    Route::get('{token}', [CampaignController::class, 'getByInvitation']);
});

// Questionnaire response routes (public access)
Route::prefix('campaigns/{campaignCode}/questionnaires/{questionnaireId}')->group(function () {
    Route::post('responses', [QuestionnaireResponseController::class, 'submitByCampaignCode']);
});

// Questionnaire response routes (invitation access)
Route::prefix('invitations/{token}/questionnaires/{questionnaireId}')->group(function () {
    Route::post('responses', [QuestionnaireResponseController::class, 'submitByInvitation']);
});

// Response status and analysis routes
Route::prefix('responses')->group(function () {
    Route::get('{responseId}/status', [QuestionnaireResponseController::class, 'getResponseStatus']);
    Route::get('{responseId}/analysis', [QuestionnaireResponseController::class, 'getResponseAnalysis']);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now(),
        'version' => '1.0.0'
    ]);
});
