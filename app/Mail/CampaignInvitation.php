<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Models\CampaignInvitation as CampaignInvitationModel;
use App\Models\CampaignEmailLog;
use App\Services\EmailLoggerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CampaignInvitation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Campaign $campaign;
    public CampaignInvitationModel $invitation;
    public string $invitationUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(Campaign $campaign, CampaignInvitationModel $invitation)
    {
        $this->campaign = $campaign->load('company');
        $this->invitation = $invitation;
        $this->invitationUrl = config('app.frontend_url') . '/i/' . $invitation->token;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Diagnóstico de Habilidades Blandas - ' . $this->campaign->company->name,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign-invitation',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
