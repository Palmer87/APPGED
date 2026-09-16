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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('folders')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('file_name');
            $table->string('mime_type');
            $table->string('extension', 10);
            $table->unsignedBigInteger('size');

            $table->string('storage_disk')->default('private');
            $table->string('storage_path');

            $table->enum('status', ['active', 'archived'])->default('active');

            $table->softDeletes();
            $table->timestamps();
            $table->index('organization_id');
            $table->index('folder_id');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
