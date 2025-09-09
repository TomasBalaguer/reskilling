<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\CompanyAuthController;

Route::get('/', function () {
    return view('welcome');
});

// Auth routes for Admin
Route::prefix('admin')->group(function () {
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login']);
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');
});

// Auth routes for Company
Route::prefix('company')->group(function () {
    Route::get('/login', [CompanyAuthController::class, 'showLoginForm'])->name('company.login');
    Route::post('/login', [CompanyAuthController::class, 'login']);
    Route::post('/logout', [CompanyAuthController::class, 'logout'])->name('company.logout');
});

// Admin routes (protected)
Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // Companies
    Route::get('/companies', [AdminController::class, 'companies'])->name('companies');
    Route::get('/companies/create', [AdminController::class, 'createCompany'])->name('companies.create');
    Route::post('/companies', [AdminController::class, 'storeCompany'])->name('companies.store');
    Route::get('/companies/{id}', [AdminController::class, 'companyDetail'])->name('companies.detail');
    Route::get('/companies/{id}/edit', [AdminController::class, 'editCompany'])->name('companies.edit');
    Route::put('/companies/{id}', [AdminController::class, 'updateCompany'])->name('companies.update');
    Route::delete('/companies/{id}', [AdminController::class, 'deleteCompany'])->name('companies.delete');
    Route::patch('/companies/{id}/toggle-status', [AdminController::class, 'toggleCompanyStatus'])->name('companies.toggle-status');
    
    // Campaigns  
    Route::get('/campaigns', [AdminController::class, 'campaigns'])->name('campaigns');
    Route::get('/campaigns/create', [AdminController::class, 'createCampaign'])->name('campaigns.create');
    Route::post('/campaigns', [AdminController::class, 'storeCampaign'])->name('campaigns.store');
    Route::get('/campaigns/{id}', [AdminController::class, 'campaignDetail'])->name('campaigns.detail');
    Route::get('/campaigns/{id}/edit', [AdminController::class, 'editCampaign'])->name('campaigns.edit');
    Route::put('/campaigns/{id}', [AdminController::class, 'updateCampaign'])->name('campaigns.update');
    Route::patch('/campaigns/{id}/status', [AdminController::class, 'toggleCampaignStatus'])->name('campaigns.toggle-status');
    Route::delete('/campaigns/{id}', [AdminController::class, 'deleteCampaign'])->name('campaigns.delete');
    Route::get('/campaigns/{id}/export', [AdminController::class, 'exportCampaignData'])->name('campaigns.export');
    
    // Responses
    Route::get('/responses', [AdminController::class, 'responses'])->name('responses');
    Route::get('/responses/{id}', [AdminController::class, 'responseDetail'])->name('responses.detail');
    Route::get('/responses/{id}/report', [AdminController::class, 'generateResponseReport'])->name('responses.report');
    Route::post('/responses/{id}/reprocess', [AdminController::class, 'reprocessResponse'])->name('responses.reprocess');
    Route::delete('/responses/{id}', [AdminController::class, 'deleteResponse'])->name('responses.delete');
});

// Company admin routes (protected)
Route::prefix('company')->name('company.')->middleware('company.auth')->group(function () {
    Route::get('/', [App\Http\Controllers\Company\CompanyController::class, 'dashboard'])->name('dashboard');
    
    // Campaigns  
    Route::get('/campaigns', [App\Http\Controllers\Company\CompanyController::class, 'campaigns'])->name('campaigns');
    Route::get('/campaigns/create', [App\Http\Controllers\Company\CompanyController::class, 'createCampaign'])->name('campaigns.create');
    Route::post('/campaigns', [App\Http\Controllers\Company\CompanyController::class, 'storeCampaign'])->name('campaigns.store');
    Route::get('/campaigns/{id}', [App\Http\Controllers\Company\CompanyController::class, 'campaignDetail'])->name('campaigns.detail');
    Route::get('/campaigns/{id}/edit', [App\Http\Controllers\Company\CompanyController::class, 'editCampaign'])->name('campaigns.edit');
    Route::put('/campaigns/{id}', [App\Http\Controllers\Company\CompanyController::class, 'updateCampaign'])->name('campaigns.update');
    Route::post('/campaigns/{id}/resend-invitations', [App\Http\Controllers\Company\CompanyController::class, 'resendInvitations'])->name('campaigns.resend-invitations');
    Route::post('/campaigns/{id}/add-invitation', [App\Http\Controllers\Company\CompanyController::class, 'addSingleInvitation'])->name('campaigns.add-invitation');
    Route::post('/campaigns/{id}/add-csv', [App\Http\Controllers\Company\CompanyController::class, 'addCSVInvitations'])->name('campaigns.add-csv');
    Route::get('/campaigns/{id}/email-logs', [App\Http\Controllers\Company\CompanyController::class, 'campaignEmailLogs'])->name('campaigns.email-logs');
    Route::patch('/campaigns/{id}/toggle-status', [App\Http\Controllers\Company\CompanyController::class, 'toggleCampaignStatus'])->name('campaigns.toggle-status');
    Route::get('/campaigns/{id}/export', [App\Http\Controllers\Company\CompanyController::class, 'exportCampaignData'])->name('campaigns.export');
    
    // Responses
    Route::get('/responses', [App\Http\Controllers\Company\CompanyController::class, 'responses'])->name('responses');
    Route::get('/responses/{id}', [App\Http\Controllers\Company\CompanyController::class, 'responseDetail'])->name('responses.detail');
    Route::get('/responses/{id}/report', [App\Http\Controllers\Company\CompanyController::class, 'generateResponseReport'])->name('responses.report');
    Route::post('/responses/{id}/reprocess', [App\Http\Controllers\Company\CompanyController::class, 'reprocessResponse'])->name('responses.reprocess');
    
    // Profile
    Route::get('/profile', [App\Http\Controllers\Company\CompanyController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile', [App\Http\Controllers\Company\CompanyController::class, 'updateProfile'])->name('profile.update');
    Route::delete('/profile/logo', [App\Http\Controllers\Company\CompanyController::class, 'removeLogo'])->name('profile.remove-logo');
});

// Public routes for campaign access (no authentication required)
Route::prefix('c')->name('public.campaign.')->group(function () {
    Route::get('/{code}', [App\Http\Controllers\Public\CampaignController::class, 'accessCampaign'])->name('access');
    Route::get('/{code}/info', [App\Http\Controllers\Public\CampaignController::class, 'showRespondentForm'])->name('respondent-form');
    Route::post('/{code}/info', [App\Http\Controllers\Public\CampaignController::class, 'saveRespondentInfo'])->name('save-respondent');
    Route::get('/{code}/q/{questionnaireId}', [App\Http\Controllers\Public\CampaignController::class, 'showQuestionnaire'])->name('questionnaire');
    Route::post('/{code}/q/{questionnaireId}/submit', [App\Http\Controllers\Public\CampaignController::class, 'submitResponse'])->name('submit');
    Route::get('/{code}/thank-you', [App\Http\Controllers\Public\CampaignController::class, 'thankYou'])->name('thank-you');
});

// Public routes for invitation access
Route::prefix('i')->name('public.invitation.')->group(function () {
    Route::get('/{token}', [App\Http\Controllers\Public\CampaignController::class, 'accessByInvitation'])->name('access');
});

// Test routes (development only)
Route::get('/test/audio', function () {
    return view('public.test.audio-test');
})->name('test.audio');
