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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans');
            $table->string('billing_cycle', 20)->default('monthly'); // monthly, annual
            $table->string('status', 30)->default('trialing'); // trialing, active, past_due, cancelled, expired, suspended

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('trial_starts_at')->nullable();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->boolean('auto_renew')->default(true);
            $table->string('provider', 50)->default('manual'); // manual, stripe, cinetpay, flutterwave
            $table->string('provider_subscription_id')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
