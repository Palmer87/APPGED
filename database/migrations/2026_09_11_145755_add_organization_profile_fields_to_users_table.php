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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->string('job_title')->nullable();
            $table->string('status')->default('active');
            $table->timestamp('last_login_at')->nullable();

            $table->dropColumn(['name', 'email_verified_at', 'remember_token']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->dropForeign(['organization_id']);
            $table->dropColumn([
                'organization_id',
                'first_name',
                'last_name',
                'phone',
                'avatar',
                'job_title',
                'status',
                'last_login_at',
            ]);
        });
    }
};
