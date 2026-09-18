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
        Schema::create('document_ocrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained('document_versions')->cascadeOnDelete();

            $table->string('status', 30)->default('pending');
            $table->longText('extracted_text')->nullable();
            $table->text('error_message')->nullable();

            $table->unsignedInteger('word_count')->default(0);
            $table->float('confidence')->nullable();
            $table->string('language', 30)->default('fra+eng');
            $table->unsignedInteger('execution_time_ms')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['document_id', 'document_version_id']);
            $table->index('organization_id');
            $table->index('status');
        });

        // Add PostgreSQL GIN full-text index on extracted_text if running on pgsql
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX document_ocrs_fts_idx ON document_ocrs USING GIN (
                    to_tsvector('simple', coalesce(extracted_text, ''))
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS document_ocrs_fts_idx');
        }

        Schema::dropIfExists('document_ocrs');
    }
};
