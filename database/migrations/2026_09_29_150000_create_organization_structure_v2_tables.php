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
        // 1. Directions table
        Schema::create('directions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'name']);
        });

        // 2. Services table
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('direction_id')->constrained('directions')->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'direction_id']);
            $table->index(['organization_id', 'is_active']);
            $table->index(['organization_id', 'name']);
        });

        // 3. User & Service pivot table (Associated services)
        Schema::create('service_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'service_id']);
            $table->index(['service_id', 'user_id']);
        });

        // 4. Primary service on users table
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('primary_service_id')->nullable()->after('organization_id')->constrained('services')->nullOnDelete();
        });

        // 5. Access scopes (Périmètres d'accès)
        Schema::create('access_scopes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('scope_type', 50); // organization, direction, service, document_type, folder, document
            $table->foreignId('direction_id')->nullable()->constrained('directions')->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['organization_id', 'user_id']);
            $table->index(['user_id', 'scope_type']);
        });

        // 6. Add direction_id & service_id to folders table
        Schema::table('folders', function (Blueprint $table) {
            $table->foreignId('direction_id')->nullable()->after('folder_type')->constrained('directions')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->after('direction_id')->constrained('services')->nullOnDelete();
        });

        // 7. Add direction_id & service_id to documents table
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('direction_id')->nullable()->after('document_type_id')->constrained('directions')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->after('direction_id')->constrained('services')->nullOnDelete();
        });

        // 8. Backward-compatible Data Backfill from existing department folders
        $departmentFolders = DB::table('folders')
            ->where('folder_type', 'department')
            ->get();

        foreach ($departmentFolders as $folder) {
            $directionId = DB::table('directions')->insertGetId([
                'organization_id' => $folder->organization_id,
                'name' => $folder->name,
                'description' => $folder->description,
                'folder_id' => $folder->id,
                'is_active' => $folder->is_active ?? true,
                'created_at' => $folder->created_at ?? now(),
                'updated_at' => $folder->updated_at ?? now(),
            ]);

            DB::table('folders')
                ->where('id', $folder->id)
                ->update(['direction_id' => $directionId]);

            // Also associate direct child folders (like document types) to this direction
            DB::table('folders')
                ->where('parent_id', $folder->id)
                ->update(['direction_id' => $directionId]);

            // Update existing documents located in this department folder or its child document types
            $childFolderIds = DB::table('folders')
                ->where('parent_id', $folder->id)
                ->pluck('id')
                ->push($folder->id)
                ->all();

            DB::table('documents')
                ->whereIn('folder_id', $childFolderIds)
                ->orWhereIn('document_type_id', $childFolderIds)
                ->update(['direction_id' => $directionId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['direction_id']);
            $table->dropColumn(['service_id', 'direction_id']);
        });

        Schema::table('folders', function (Blueprint $table) {
            $table->dropForeign(['service_id']);
            $table->dropForeign(['direction_id']);
            $table->dropColumn(['service_id', 'direction_id']);
        });

        Schema::dropIfExists('access_scopes');

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['primary_service_id']);
            $table->dropColumn('primary_service_id');
        });

        Schema::dropIfExists('service_user');
        Schema::dropIfExists('services');
        Schema::dropIfExists('directions');
    }
};
