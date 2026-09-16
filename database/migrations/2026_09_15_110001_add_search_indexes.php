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
        Schema::table('documents', function (Blueprint $table) {
            $table->index('status');
            $table->index('created_at');
            $table->index('updated_at');
        });

        Schema::table('document_category', function (Blueprint $table) {
            $table->index('category_id');
        });

        Schema::table('document_tag', function (Blueprint $table) {
            $table->index('tag_id');
        });

        Schema::table('document_metadata', function (Blueprint $table) {
            $table->index('value_string');
            $table->index('value_integer');
            $table->index('value_decimal');
            $table->index('value_date');
            $table->index('value_datetime');
        });

        // Add PostgreSQL specific GIN full-text index if running on pgsql
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX documents_fts_idx ON documents USING GIN (
                    to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(file_name, '') || ' ' || coalesce(description, ''))
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
            DB::statement('DROP INDEX IF EXISTS documents_fts_idx');
        }

        Schema::table('document_metadata', function (Blueprint $table) {
            $table->dropIndex(['value_string']);
            $table->dropIndex(['value_integer']);
            $table->dropIndex(['value_decimal']);
            $table->dropIndex(['value_date']);
            $table->dropIndex(['value_datetime']);
        });

        Schema::table('document_tag', function (Blueprint $table) {
            $table->dropIndex(['tag_id']);
        });

        Schema::table('document_category', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['updated_at']);
        });
    }
};
