<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('campaign_email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('campaign_invitation_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('recipient_email');
            $table->string('recipient_name')->nullable();
            $table->enum('type', ['invitation', 'reminder', 'bulk'])->default('invitation');
            $table->enum('status', ['queued', 'sent', 'failed', 'bounced'])->default('queued');
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Email details, attempt number, etc.
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            $table->index(['campaign_id', 'status']);
            $table->index(['recipient_email', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_email_logs');
    }
};
