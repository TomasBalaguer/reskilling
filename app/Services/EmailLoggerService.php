<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignInvitation;
use App\Models\CampaignEmailLog;
use Illuminate\Support\Facades\Log;

class EmailLoggerService
{
    /**
     * Log email queued for sending
     */
    public function logEmailQueued(Campaign $campaign, CampaignInvitation $invitation, string $type = 'invitation'): CampaignEmailLog
    {
        $emailLog = CampaignEmailLog::create([
            'campaign_id' => $campaign->id,
            'campaign_invitation_id' => $invitation->id,
            'recipient_email' => $invitation->email,
            'recipient_name' => $invitation->name,
            'type' => $type,
            'status' => 'queued',
            'queued_at' => now(),
            'metadata' => [
                'mail_driver' => config('mail.default'),
                'attempt' => 1,
                'campaign_name' => $campaign->name,
                'company_id' => $campaign->company_id
            ]
        ]);

        Log::info('Email queued for sending', [
            'email_log_id' => $emailLog->id,
            'campaign_id' => $campaign->id,
            'campaign_name' => $campaign->name,
            'recipient_email' => $invitation->email,
            'type' => $type
        ]);

        return $emailLog;
    }

    /**
     * Log email sent successfully 
     */
    public function logEmailSent(CampaignEmailLog $emailLog): void
    {
        $emailLog->markAsSent();
        
        Log::info('Email sent successfully', [
            'email_log_id' => $emailLog->id,
            'campaign_id' => $emailLog->campaign_id,
            'recipient_email' => $emailLog->recipient_email,
            'sent_at' => $emailLog->sent_at
        ]);
    }

    /**
     * Log email failed
     */
    public function logEmailFailed(CampaignEmailLog $emailLog, string $errorMessage, \Exception $exception = null): void
    {
        $metadata = [
            'error_type' => $exception ? get_class($exception) : 'Unknown',
            'error_file' => $exception?->getFile(),
            'error_line' => $exception?->getLine(),
            'mail_driver' => config('mail.default')
        ];

        $emailLog->markAsFailed($errorMessage, $metadata);
        
        Log::error('Email sending failed', [
            'email_log_id' => $emailLog->id,
            'campaign_id' => $emailLog->campaign_id,
            'recipient_email' => $emailLog->recipient_email,
            'error_message' => $errorMessage,
            'exception' => $exception?->getMessage(),
            'metadata' => $metadata
        ]);
    }

    /**
     * Get email statistics for a campaign
     */
    public function getCampaignEmailStats(Campaign $campaign): array
    {
        $logs = $campaign->emailLogs();
        
        return [
            'total' => $logs->count(),
            'queued' => $logs->where('status', 'queued')->count(),
            'sent' => $logs->where('status', 'sent')->count(),
            'failed' => $logs->where('status', 'failed')->count(),
            'bounced' => $logs->where('status', 'bounced')->count(),
            'last_sent' => $logs->whereNotNull('sent_at')->latest('sent_at')->first()?->sent_at,
            'last_failed' => $logs->whereNotNull('failed_at')->latest('failed_at')->first()?->failed_at
        ];
    }

    /**
     * Get recent email failures for debugging
     */
    public function getRecentFailures(Campaign $campaign, int $limit = 10): array
    {
        return $campaign->emailLogs()
            ->where('status', 'failed')
            ->latest('failed_at')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'email' => $log->recipient_email,
                    'failed_at' => $log->failed_at,
                    'error_message' => $log->error_message,
                    'metadata' => $log->metadata
                ];
            })
            ->toArray();
    }
}