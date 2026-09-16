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
        Schema::create('document_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->cascadeOnDelete();
            $table->string('permission', 50)->default('view');
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('shared_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->foreignId('revoked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('organization_id');
            $table->index('document_id');
            $table->index(['user_id', 'revoked_at']);
            $table->index(['group_id', 'revoked_at']);
            $table->index('expires_at');
        });

        // Enforce XOR constraint on user_id vs group_id
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_shares ADD CONSTRAINT chk_document_share_user_xor_group CHECK ((user_id IS NOT NULL AND group_id IS NULL) OR (user_id IS NULL AND group_id IS NOT NULL))');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TRIGGER chk_doc_share_insert BEFORE INSERT ON document_shares
                BEGIN
                    SELECT CASE
                        WHEN NOT ((NEW.user_id IS NOT NULL AND NEW.group_id IS NULL) OR (NEW.user_id IS NULL AND NEW.group_id IS NOT NULL))
                        THEN RAISE(ABORT, "CHECK constraint failed: user_id XOR group_id required")
                    END;
                END;');
            DB::statement('CREATE TRIGGER chk_doc_share_update BEFORE UPDATE ON document_shares
                BEGIN
                    SELECT CASE
                        WHEN NOT ((NEW.user_id IS NOT NULL AND NEW.group_id IS NULL) OR (NEW.user_id IS NULL AND NEW.group_id IS NOT NULL))
                        THEN RAISE(ABORT, "CHECK constraint failed: user_id XOR group_id required")
                    END;
                END;');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_shares');
    }
};
