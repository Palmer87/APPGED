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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();

            $table->string('invoice_number', 50)->unique();
            $table->unsignedBigInteger('amount'); // Total amount in cents/whole FCFA (e.g. 19000)
            $table->string('currency', 10)->default('XOF');
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('subtotal');
            $table->unsignedBigInteger('total');

            $table->string('status', 30)->default('pending'); // draft, pending, paid, failed, cancelled
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('due_at')->nullable();

            $table->string('provider', 50)->default('manual');
            $table->string('provider_payment_id')->nullable();
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
        Schema::dropIfExists('invoices');
    }
};
