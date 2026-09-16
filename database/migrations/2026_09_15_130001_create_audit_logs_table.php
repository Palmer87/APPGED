<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('target_type')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description')->nullable();

            // PostgreSQL supports jsonb, SQLite fallback to json
            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('old_values')->nullable();
                $table->jsonb('new_values')->nullable();
                $table->jsonb('metadata')->nullable();
            } else {
                $table->json('old_values')->nullable();
                $table->json('new_values')->nullable();
                $table->json('metadata')->nullable();
            }

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('result', 20)->default('success'); // 'success' | 'failure'
            $table->timestamp('created_at')->useCurrent();

            // Indexes for fast lookup and filtering
            $table->index('organization_id');
            $table->index('user_id');
            $table->index('action');
            $table->index('result');
            $table->index('created_at');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['organization_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
