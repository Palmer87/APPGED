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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('monthly_price')->nullable();
            $table->unsignedBigInteger('annual_price')->nullable();
            $table->string('currency', 10)->default('XOF');

            // Resource limits (null = unlimited)
            $table->unsignedInteger('max_users')->nullable();
            $table->unsignedBigInteger('max_storage_bytes')->nullable();
            $table->unsignedInteger('max_directions')->nullable();
            $table->unsignedInteger('max_document_types')->nullable();
            $table->unsignedInteger('max_ocr_pages_month')->nullable();

            // Feature flags
            $table->boolean('has_api')->default(false);
            $table->boolean('has_workflows')->default(false);
            $table->boolean('has_advanced_audit')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_dedicated_support')->default(false);
            $table->boolean('has_sla')->default(false);
            $table->boolean('has_custom_migration')->default(false);
            $table->boolean('has_custom_integrations')->default(false);

            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index('is_active');
            $table->index('sort_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
