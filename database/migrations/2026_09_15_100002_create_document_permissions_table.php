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
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('groups')->cascadeOnDelete();
            $table->string('permission', 50);
            $table->timestamps();

            $table->unique(['document_id', 'user_id', 'permission'], 'idx_doc_perm_user_unique');
            $table->unique(['document_id', 'group_id', 'permission'], 'idx_doc_perm_group_unique');
        });

        // Enforce XOR constraint on user_id vs group_id
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE document_permissions ADD CONSTRAINT chk_doc_perm_user_xor_group CHECK ((user_id IS NOT NULL AND group_id IS NULL) OR (user_id IS NULL AND group_id IS NOT NULL))');
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement('CREATE TRIGGER chk_doc_perm_insert BEFORE INSERT ON document_permissions
                BEGIN
                    SELECT CASE
                        WHEN NOT ((NEW.user_id IS NOT NULL AND NEW.group_id IS NULL) OR (NEW.user_id IS NULL AND NEW.group_id IS NOT NULL))
                        THEN RAISE(ABORT, "CHECK constraint failed: user_id XOR group_id required")
                    END;
                END;');
            DB::statement('CREATE TRIGGER chk_doc_perm_update BEFORE UPDATE ON document_permissions
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
        Schema::dropIfExists('document_permissions');
    }
};
