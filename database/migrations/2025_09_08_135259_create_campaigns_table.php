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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique(); // Código único para acceso público
            $table->text('description')->nullable();
            $table->integer('max_responses');
            $table->integer('responses_count')->default(0); // Counter cache
            $table->datetime('active_from');
            $table->datetime('active_until');
            $table->boolean('public_link_enabled')->default(true);
            $table->string('public_link_code')->unique()->nullable(); // Código adicional para mayor seguridad
            $table->json('settings')->nullable(); // Configuraciones adicionales
            $table->enum('status', ['draft', 'active', 'paused', 'completed', 'archived'])->default('draft');
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('code');
            $table->index('active_from');
            $table->index('active_until');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
