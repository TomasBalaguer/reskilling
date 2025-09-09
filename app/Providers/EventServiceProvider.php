<?php

namespace App\Providers;

use App\Events\AIAnalysisCompleted;
use App\Events\AudioTranscriptionCompleted;
use App\Events\QuestionnaireResponseSubmitted;
use App\Listeners\ProcessAIAnalysisResults;
use App\Listeners\ProcessQuestionnaireResponse;
use App\Listeners\ProcessTranscriptionResults;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Questionnaire Processing Events
        QuestionnaireResponseSubmitted::class => [
            ProcessQuestionnaireResponse::class,
        ],

        AudioTranscriptionCompleted::class => [
            ProcessTranscriptionResults::class,
        ],

        AIAnalysisCompleted::class => [
            ProcessAIAnalysisResults::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        parent::boot();

        // Register event listeners for debugging in development
        if (app()->environment('local', 'development')) {
            $this->registerDevelopmentEventListeners();
        }

        // Register event listeners for monitoring and logging
        $this->registerMonitoringEventListeners();
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Register development-specific event listeners
     */
    protected function registerDevelopmentEventListeners(): void
    {
        // Log all questionnaire processing events in development
        Event::listen([
            QuestionnaireResponseSubmitted::class,
            AudioTranscriptionCompleted::class,
            AIAnalysisCompleted::class,
        ], function ($event) {
            \Illuminate\Support\Facades\Log::channel('development')->info(
                'Event triggered: ' . get_class($event),
                [
                    'event_data' => method_exists($event, 'getMetadata') ? $event->getMetadata() : 'No metadata available',
                    'timestamp' => now()
                ]
            );
        });
    }

    /**
     * Register monitoring and logging event listeners
     */
    protected function registerMonitoringEventListeners(): void
    {
        // Monitor processing failures
        Event::listen([
            'Illuminate\Queue\Events\JobFailed',
        ], function ($event) {
            if (str_contains($event->job->resolveName(), 'App\Jobs')) {
                \Illuminate\Support\Facades\Log::error('Questionnaire processing job failed', [
                    'job' => $event->job->resolveName(),
                    'exception' => $event->exception->getMessage(),
                    'data' => $event->data
                ]);

                // Could send notifications to administrators here
                // Notification::route('mail', config('app.admin_email'))
                //     ->notify(new ProcessingJobFailedNotification($event));
            }
        });

        // Monitor processing performance
        Event::listen([
            'Illuminate\Queue\Events\JobProcessed',
        ], function ($event) {
            if (str_contains($event->job->resolveName(), 'App\Jobs')) {
                $processingTime = microtime(true) - LARAVEL_START;
                
                if ($processingTime > 300) { // Log if processing takes more than 5 minutes
                    \Illuminate\Support\Facades\Log::warning('Long running questionnaire job', [
                        'job' => $event->job->resolveName(),
                        'processing_time' => $processingTime,
                        'data' => $event->data
                    ]);
                }
            }
        });
    }
}