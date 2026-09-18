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
        Schema::create('folder_metadata_definition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('folders')->cascadeOnDelete();
            $table->foreignId('metadata_definition_id')->constrained('metadata_definitions')->cascadeOnDelete();
            $table->boolean('is_required')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->unique(['folder_id', 'metadata_definition_id']);
            $table->index('folder_id');
            $table->index('metadata_definition_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folder_metadata_definition');
    }
};
