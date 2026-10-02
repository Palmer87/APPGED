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
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL partial unique index: unique (name, parent_id) for active, non-deleted document types
            DB::statement('
                CREATE UNIQUE INDEX idx_folders_unique_document_type_name_parent
                ON folders (organization_id, parent_id, name)
                WHERE folder_type = \'document_type\' AND deleted_at IS NULL
            ');
        } else {
            // Fallback for SQLite / MySQL: unique index across organization_id, parent_id, name, folder_type
            // Note: in SQLite / testing, we create standard unique index
            Schema::table('folders', function (Blueprint $table) {
                $table->unique(['organization_id', 'parent_id', 'name', 'folder_type'], 'folders_doc_type_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_folders_unique_document_type_name_parent');
        } else {
            Schema::table('folders', function (Blueprint $table) {
                $table->dropUnique('folders_doc_type_unique');
            });
        }
    }
};
