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
        Schema::create('campaign_questionnaires', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->onDelete('cascade');
            $table->foreignId('questionnaire_id')->constrained()->onDelete('cascade');
            $table->integer('order')->default(1);
            $table->boolean('is_required')->default(true);
            $table->timestamps();
            
            $table->unique(['campaign_id', 'questionnaire_id']);
            $table->index(['campaign_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_questionnaires');
    }
};
